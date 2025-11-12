<?php

namespace App\Http\Controllers;

use App\Services\GoogleClientFactory;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function connect(Request $request)
    {
        return view('google.connect');
    }

    public function redirect(Request $request): RedirectResponse
    {
        $client = GoogleClientFactory::make();
        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return redirect()->route('google.connect')->withErrors(['google' => 'Missing authorization code']);
        }

        $client = GoogleClientFactory::make();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            Log::error('Google auth error', $token);
            return redirect()->route('google.connect')->withErrors(['google' => $token['error_description'] ?? 'Auth error']);
        }

        $client->setAccessToken($token);
        // Fetch account email
        $oauth2 = new GoogleOauth2($client);
        $me = $oauth2->userinfo->get();
        $email = $me->email ?? null;

        if (!$email) {
            return redirect()->route('google.connect')->withErrors(['google' => 'Could not retrieve account email']);
        }

        DB::table('google_tokens')->updateOrInsert(
            [
                'user_id' => auth()->id(),
                'account_email' => $email
            ],
            [
                'access_token' => json_encode($token),
                'refresh_token' => $token['refresh_token'] ?? DB::raw('refresh_token'),
                'expires_at' => now()->addSeconds($token['expires_in'] ?? 0),
                'token_type' => $token['token_type'] ?? null,
                'scope' => $token['scope'] ?? null,
                'raw_payload' => json_encode($token),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return view('google.connected', [
            'email' => $email,
        ]);
    }
}
