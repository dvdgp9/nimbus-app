<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\DB;

class GoogleClientFactory
{
    public static function make(?string $accountEmail = null): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $scopes = preg_split('/\s+/', (string) env('GOOGLE_SCOPES', 'openid email profile https://www.googleapis.com/auth/calendar.readonly'));
        $client->setScopes($scopes);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if ($accountEmail) {
            $row = DB::table('google_tokens')->where('account_email', $accountEmail)->first();
            if ($row && $row->access_token) {
                $client->setAccessToken(json_decode($row->access_token, true) ?: $row->access_token);
                if ($client->isAccessTokenExpired() && $row->refresh_token) {
                    $client->fetchAccessTokenWithRefreshToken($row->refresh_token);
                    $newToken = $client->getAccessToken();
                    DB::table('google_tokens')->where('id', $row->id)->update([
                        'access_token' => json_encode($newToken),
                        'expires_at' => now()->addSeconds($newToken['expires_in'] ?? 0),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        return $client;
    }
}
