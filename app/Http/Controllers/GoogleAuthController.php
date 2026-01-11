<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleClientFactory;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function loginRedirect(Request $request): RedirectResponse
    {
        session(['google_auth_intent' => 'login']);
        $client = GoogleClientFactory::make();
        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            $intent = session('google_auth_intent');
            session()->forget('google_auth_intent');
            if ($intent === 'login') {
                return redirect()->route('login')->withErrors(['google' => 'No se pudo completar el inicio de sesión con Google.']);
            }
            return redirect()->route('google.connect')->withErrors(['google' => 'Missing authorization code']);
        }

        $client = GoogleClientFactory::make();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            Log::error('Google auth error', $token);
            $intent = session('google_auth_intent');
            session()->forget('google_auth_intent');
            if ($intent === 'login') {
                return redirect()->route('login')->withErrors(['google' => $token['error_description'] ?? 'Error de autenticación']);
            }
            return redirect()->route('google.connect')->withErrors(['google' => $token['error_description'] ?? 'Auth error']);
        }

        $client->setAccessToken($token);
        $oauth2 = new GoogleOauth2($client);
        $me = $oauth2->userinfo->get();
        $email = $me->email ?? null;
        $googleId = $me->id ?? null;
        $name = $me->name ?? null;
        $avatar = $me->picture ?? null;

        if (!$email) {
            $intent = session('google_auth_intent');
            session()->forget('google_auth_intent');
            if ($intent === 'login') {
                return redirect()->route('login')->withErrors(['google' => 'No se pudo obtener el email de Google.']);
            }
            return redirect()->route('google.connect')->withErrors(['google' => 'Could not retrieve account email']);
        }

        $intent = session('google_auth_intent');
        session()->forget('google_auth_intent');

        if ($intent === 'login') {
            return $this->handleLoginCallback($email, $googleId, $name, $avatar, $token);
        }

        return $this->handleConnectCallback($email, $token);
    }

    protected function handleLoginCallback(string $email, ?string $googleId, ?string $name, ?string $avatar, array $token)
    {
        $user = User::where('google_id', $googleId)->first();
        
        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            if (!$user->google_id && $googleId) {
                $user->update(['google_id' => $googleId]);
            }
            if ($avatar && !$user->avatar) {
                $user->update(['avatar' => $avatar]);
            }
        } else {
            $user = User::create([
                'name' => $name ?? explode('@', $email)[0],
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'password' => null,
            ]);
        }

        Auth::login($user, true);

        DB::table('google_tokens')->updateOrInsert(
            [
                'user_id' => $user->id,
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

        return redirect()->intended(route('home'));
    }

    protected function handleConnectCallback(string $email, array $token)
    {
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
