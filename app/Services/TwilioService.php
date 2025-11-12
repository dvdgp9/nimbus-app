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
     */
    public function sendWhatsApp(string $to, string $message): string
    {
        try {
            // Twilio requires whatsapp: prefix
            $from = 'whatsapp:' . $this->whatsappNumber;
            $to = 'whatsapp:' . $to;

            $response = $this->client->messages->create(
                $to,
                [
                    'from' => $from,
                    'body' => $message,
                ]
            );

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
