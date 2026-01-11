<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\DB;

class GoogleClientFactory
{
    public static function make(?string $accountEmail = null, ?int $userId = null): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        // Set scopes - Calendar scope is required for listing calendars and events
        $scopes = [
            'openid',
            'email',
            'profile',
            'https://www.googleapis.com/auth/calendar',
        ];
        
        // Allow env override if needed
        if (env('GOOGLE_SCOPES')) {
            $scopes = preg_split('/\s+/', (string) env('GOOGLE_SCOPES'));
        }
        $client->setScopes($scopes);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if ($accountEmail) {
            $query = DB::table('google_tokens')->where('account_email', $accountEmail);
            if ($userId) {
                $query->where('user_id', $userId);
            }
            $row = $query->first();
            if ($row && $row->access_token) {
                $client->setAccessToken(json_decode($row->access_token, true) ?: $row->access_token);
                if ($client->isAccessTokenExpired() && $row->refresh_token) {
                    try {
                        $client->fetchAccessTokenWithRefreshToken($row->refresh_token);
                        $newToken = $client->getAccessToken();
                        DB::table('google_tokens')->where('id', $row->id)->update([
                            'access_token' => json_encode($newToken),
                            'expires_at' => now()->addSeconds($newToken['expires_in'] ?? 0),
                            'updated_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        // If refresh fails (e.g., invalid_grant), clear the token to force reconnection
                        DB::table('google_tokens')->where('id', $row->id)->delete();
                        throw $e;
                    }
                }
            }
        }

        return $client;
    }
}
