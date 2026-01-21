<?php

namespace App\Services;

use Exception;
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
            $response = Http::asForm()->post($this->apiUrl . 'sendSMS/', [
                'auth_token' => $this->authToken,
                'messages' => json_encode([
                    [
                        'recipient' => $this->formatPhoneNumber($to),
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
            
            if (!$messageResult || $messageResult['status'] !== 0) {
                $error = $messageResult['error'] ?? 'Unknown error';
                throw new Exception("Acumbamail SMS error: {$error}");
            }

            $smsId = (string) $messageResult['id'];

            Log::info("SMS sent via Acumbamail", [
                'id' => $smsId,
                'to' => $to,
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

            return (float) $response->body();
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
