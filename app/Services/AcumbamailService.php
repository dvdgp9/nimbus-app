<?php

namespace App\Services;

use Exception;
use InvalidArgumentException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AcumbamailService
{
    protected string $authToken;
    protected string $sender;
    protected string $apiUrl = 'https://acumbamail.com/api/1/';

    public function __construct()
    {
        $this->authToken = config('services.acumbamail.auth_token');
        $this->sender = config('services.acumbamail.sender', 'Nimbus');

        if (!$this->authToken) {
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
            $response = Http::asForm()->post($this->apiUrl . 'sendSMS/', [
                'auth_token' => $this->authToken,
                'messages' => json_encode([
                    [
                        'recipient' => $recipient,
                        'body' => $message,
                        'sender' => $this->sender,
                    ]
                ]),
            ]);

            $data = $response->json();

            if (!$response->successful()) {
                throw new Exception('Acumbamail API error: ' . $response->body());
            }

            // Check individual message status
            $messageResult = $data['messages'][0] ?? null;
            
            if (!$messageResult || (int) ($messageResult['status'] ?? -1) !== 0) {
                $error = $messageResult['error'] ?? 'Unknown error';
                throw new Exception("Acumbamail SMS error: {$error}");
            }

            if (!isset($messageResult['id'])) {
                throw new Exception('Acumbamail SMS error: missing message id');
            }

            $smsId = (string) $messageResult['id'];

            Log::info("SMS sent via Acumbamail", [
                'id' => $smsId,
                'to' => $recipient,
                'status' => (int) $messageResult['status'],
                'credits' => $messageResult['credits'] ?? null,
            ]);

            return $smsId;
        } catch (Exception $e) {
            Log::error("Acumbamail SMS error: " . $e->getMessage(), [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get remaining SMS credits
     */
    public function getCredits(): ?float
    {
        try {
            $response = Http::asForm()->post($this->apiUrl . 'getCreditsSMS/', [
                'auth_token' => $this->authToken,
            ]);

            if (!$response->successful()) {
                Log::error("Acumbamail API error in getCreditsSMS: " . $response->body());
                return null;
            }

            $data = $response->json();
            
            // La API devuelve un JSON como {"Creditos": 248}
            return isset($data['Creditos']) ? (float) $data['Creditos'] : 0.0;
        } catch (Exception $e) {
            Log::error("Failed to fetch Acumbamail credits: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Format phone number to international format
     */
    public static function formatPhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if (!$digits) {
            throw new InvalidArgumentException('El teléfono no contiene ningún número.');
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        if (strlen($digits) === 9) {
            return '+34' . $digits;
        }

        if (str_starts_with($digits, '34') && strlen($digits) === 11) {
            return '+' . $digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return '+' . $digits;
        }

        throw new InvalidArgumentException('Usa un teléfono español de 9 dígitos o un número internacional con prefijo.');
    }
}
