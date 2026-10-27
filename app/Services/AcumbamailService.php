<?php

namespace App\Services;

use App\Exceptions\SmsDeliveryUncertainException;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AcumbamailService
{
    protected string $authToken;

    protected string $sender;

    protected string $apiUrl = 'https://acumbamail.com/api/1/';

    /** Total HTTP attempts (1 initial + retries) for transient Acumbamail failures. */
    protected int $maxAttempts = 3;

    /** Base backoff between attempts, in milliseconds (grows linearly per attempt). */
    protected int $retryBackoffMs = 500;

    /** HTTP request timeout in seconds. */
    protected int $timeout = 15;

    /** Slow sends must not be repeated just because their response was delayed. */
    protected int $sendTimeout = 60;

    public function __construct()
    {
        $this->authToken = config('services.acumbamail.auth_token');
        $this->sender = config('services.acumbamail.sender', 'Nimbus');

        if (! $this->authToken) {
            throw new Exception('Acumbamail auth_token not configured');
        }
    }

    /**
     * Send SMS via Acumbamail API
     */
    public function sendSMS(string $to, string $message): string
    {
        try {
            $recipient = self::formatPhoneNumber($to);

            // Encode the payload as raw UTF-8. Without JSON_UNESCAPED_UNICODE,
            // json_encode() rewrites emoji as escaped UTF-16 surrogate-pair
            // sequences (backslash-u...), which Acumbamail's gateway rejects
            // with an nginx 404. Sending raw UTF-8 matches Acumbamail's official
            // PHP library and lets emoji through. Accents were always accepted,
            // so they were never the problem.
            // This is a side effect, unlike balance/report lookups. A timeout
            // does not prove rejection: Acumbamail may still deliver this SMS.
            $response = Http::asForm()->connectTimeout(10)->timeout($this->sendTimeout)
                ->post($this->apiUrl.'sendSMS/', [
                    'auth_token' => $this->authToken,
                    'messages' => json_encode([
                        [
                            'recipient' => $recipient,
                            'body' => $message,
                            'sender' => $this->sender,
                        ],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);

            $data = $response->json();

            $messageResult = $data['messages'][0] ?? null;
            if (! is_array($messageResult)) {
                $messageResult = [];
            }
            $status = $messageResult['status'] ?? null;

            // Only an explicit per-message rejection establishes that it is
            // safe for a later scheduler tick to try again.
            if ((is_int($status) || (is_string($status) && ctype_digit($status)))
                && (int) $status !== 0 && ! empty($messageResult['error'])) {
                throw new Exception('Acumbamail SMS rejected: '.$messageResult['error']);
            }

            if (! $response->successful() || ! in_array($status, [0, '0'], true)
                || empty($messageResult['id'])) {
                throw new SmsDeliveryUncertainException(
                    'Acumbamail did not confirm the send (HTTP '.$response->status().'). Check the SMS report before retrying.'
                );
            }

            $smsId = (string) $messageResult['id'];

            Log::info('SMS sent via Acumbamail', [
                'id' => $smsId,
                'to' => $recipient,
                'status' => (int) $messageResult['status'],
                'credits' => $messageResult['credits'] ?? null,
            ]);

            return $smsId;
        } catch (ConnectionException $e) {
            throw new SmsDeliveryUncertainException('Acumbamail connection timed out or was interrupted; delivery is unconfirmed.', 0, $e);
        } catch (Exception $e) {
            Log::error('Acumbamail SMS error: '.$e->getMessage(), [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** Read-only report used to recover sends whose HTTP response was lost. */
    public function getSmsReport(CarbonInterface $from, CarbonInterface $until): array
    {
        $response = $this->postWithRetry('getSMSQuickSubscriberReport/', [
            'auth_token' => $this->authToken,
            'start_date' => $from->format('Y-m-d H:i'),
            'end_date' => $until->format('Y-m-d H:i'),
        ]);
        $data = $response->json();

        if (! $response->successful() || ! is_array($data) || ! array_is_list($data)) {
            throw new Exception('Acumbamail SMS report unavailable (HTTP '.$response->status().').');
        }

        return $data;
    }

    /**
     * Get the remaining SMS balance, or null when Acumbamail cannot be reached.
     *
     * The API answers {"Creditos": 70132, "sms_espana": 701}: credits are an
     * internal unit and 100 of them pay for one single-segment SMS to Spain.
     * `sms` is what a human means by "SMS left", so thresholds use it.
     *
     * @return array{credits: float, sms: int}|null
     */
    public function getSmsBalance(): ?array
    {
        try {
            $response = $this->postWithRetry('getCreditsSMS/', [
                'auth_token' => $this->authToken,
            ]);

            if (! $response->successful()) {
                Log::error('Acumbamail API error in getCreditsSMS: '.$response->body());

                return null;
            }

            $data = $response->json();
            $credits = (float) ($data['Creditos'] ?? 0);

            return [
                'credits' => $credits,
                'sms' => isset($data['sms_espana']) ? (int) $data['sms_espana'] : (int) floor($credits / 100),
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch Acumbamail credits: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Retry read-only balance/report requests on transient failures. Never
     * use this for sendSMS: production confirmed duplicate deliveries after
     * retries of requests whose responses timed out (2026-10-18 onwards).
     */
    protected function postWithRetry(string $path, array $payload): Response
    {
        $response = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                $response = Http::asForm()
                    ->timeout($this->timeout)
                    ->post($this->apiUrl.$path, $payload);

                if ($attempt < $this->maxAttempts && $this->isTransientStatus($response->status())) {
                    Log::warning("Acumbamail transient HTTP {$response->status()} on {$path}, retrying ({$attempt}/{$this->maxAttempts})");
                    usleep($this->retryBackoffMs * 1000 * $attempt);

                    continue;
                }

                return $response;
            } catch (ConnectionException $e) {
                if ($attempt >= $this->maxAttempts) {
                    throw $e;
                }
                Log::warning("Acumbamail connection error on {$path}, retrying ({$attempt}/{$this->maxAttempts}): ".$e->getMessage());
                usleep($this->retryBackoffMs * 1000 * $attempt);
            }
        }

        return $response;
    }

    /**
     * Whether an HTTP status from Acumbamail is worth retrying. 404 is included
     * on purpose: their nginx returns it transiently for a valid endpoint.
     */
    protected function isTransientStatus(int $status): bool
    {
        return in_array($status, [404, 408, 429], true) || $status >= 500;
    }

    /**
     * Format phone number to international format
     */
    public static function formatPhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if (! $digits) {
            throw new InvalidArgumentException('El teléfono no contiene ningún número.');
        }

        if ($hasPlus) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        if (strlen($digits) === 9) {
            return '+34'.$digits;
        }

        if (str_starts_with($digits, '34') && strlen($digits) === 11) {
            return '+'.$digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return '+'.$digits;
        }

        throw new InvalidArgumentException('Usa un teléfono español de 9 dígitos o un número internacional con prefijo.');
    }
}
