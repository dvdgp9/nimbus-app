<?php

namespace App\Services;

use Twilio\Rest\Client;
use Exception;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected Client $client;
    protected string $fromNumber;
    protected string $whatsappNumber;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from');
        $this->whatsappNumber = config('services.twilio.whatsapp_from');

        if (!$sid || !$token) {
            throw new Exception('Twilio credentials not configured');
        }

        $this->client = new Client($sid, $token);
    }

    /**
     * Send SMS
     */
    public function sendSMS(string $to, string $message): string
    {
        try {
            $response = $this->client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]
            );

            Log::info("SMS sent via Twilio", [
                'sid' => $response->sid,
                'to' => $to,
                'status' => $response->status,
            ]);

            return $response->sid;
        } catch (Exception $e) {
            Log::error("Twilio SMS error: " . $e->getMessage(), [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send WhatsApp message
     *
     * If $payload is a string, it will be sent as a freeform body (only valid
     * inside the WhatsApp 24h session window).
     * If $payload is an array with 'contentSid' and optional 'contentVariables',
     * it will be sent as a WhatsApp Content Template, which is required
     * outside the 24h window.
     */
    public function sendWhatsApp(string $to, string|array $payload): string
    {
        try {
            // Twilio requires whatsapp: prefix
            $from = 'whatsapp:' . $this->whatsappNumber;
            $to = 'whatsapp:' . $to;

            $params = [
                'from' => $from,
            ];

            if (is_array($payload) && isset($payload['contentSid'])) {
                // Send as Content Template
                $params['contentSid'] = $payload['contentSid'];

                if (!empty($payload['contentVariables'])) {
                    $params['contentVariables'] = json_encode($payload['contentVariables']);
                }
            } else {
                // Fallback: freeform body (string)
                $params['body'] = (string) $payload;
            }

            $response = $this->client->messages->create($to, $params);

            Log::info("WhatsApp sent via Twilio", [
                'sid' => $response->sid,
                'to' => $to,
                'status' => $response->status,
            ]);

            return $response->sid;
        } catch (Exception $e) {
            Log::error("Twilio WhatsApp error: " . $e->getMessage(), [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get message status
     */
    public function getMessageStatus(string $sid): ?string
    {
        try {
            $message = $this->client->messages($sid)->fetch();
            return $message->status;
        } catch (Exception $e) {
            Log::error("Failed to fetch Twilio message status: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify phone number format
     */
    public static function formatPhoneNumber(string $phone): string
    {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Ensure it starts with +
        if (!str_starts_with($phone, '+')) {
            // Assume Spain by default
            $phone = '+34' . ltrim($phone, '0');
        }
        
        return $phone;
    }
}
