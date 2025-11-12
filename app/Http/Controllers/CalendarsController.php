<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarsController extends Controller
{
    public function __construct(private GoogleCalendarService $calendar) {}

    public function index(Request $request)
    {
        $email = $request->query('account');
        if (!$email) {
            $row = DB::table('google_tokens')
                ->where('user_id', auth()->id())
                ->orderByDesc('updated_at')
                ->first();
            $email = $row->account_email ?? null;
        }
        if (!$email) {
            return redirect()->route('google.connect');
        }

        try {
            $all = $this->calendar->listCalendars($email);
        } catch (\Google\Service\Exception $e) {
            // Check if error is due to insufficient scopes
            $error = json_decode($e->getMessage(), true);
            if (isset($error['error']['code']) && $error['error']['code'] === 403) {
                // Delete the token to force re-authentication with new scopes
                DB::table('google_tokens')
                    ->where('user_id', auth()->id())
                    ->where('account_email', $email)
                    ->delete();
                
                return redirect()->route('google.connect')
                    ->with('status', 'Es necesario volver a conectar tu cuenta de Google con los permisos correctos. Por favor, autoriza el acceso a Google Calendar.');
            }
            throw $e; // Re-throw if it's a different error
        }
        
        $enabled = DB::table('connected_calendars')
            ->where('user_id', auth()->id())
            ->where('account_email', $email)
            ->pluck('enabled', 'calendar_id')
            ->toArray();

        return view('calendars.index', [
            'account' => $email,
            'calendars' => $all,
            'enabled' => $enabled,
        ]);
    }

    public function store(Request $request)
    {
        $email = $request->input('account');
        $selected = $request->input('calendars', []);
        if (!$email) {
            return back()->withErrors(['account' => 'Falta la cuenta.']);
        }

        DB::transaction(function () use ($email, $selected) {
            // Deshabilitar todo primero (solo del usuario actual)
            DB::table('connected_calendars')
                ->where('user_id', auth()->id())
                ->where('account_email', $email)
                ->update(['enabled' => 0, 'updated_at' => now()]);

            // Upsert de los seleccionados (habilitar)
            foreach ($selected as $calId) {
                DB::table('connected_calendars')->updateOrInsert(
                    [
                        'user_id' => auth()->id(),
                        'account_email' => $email,
                        'calendar_id' => $calId
                    ],
                    [ 'enabled' => 1, 'updated_at' => now(), 'created_at' => now() ]
                );
            }
        });

        return redirect()->route('events.index', ['account' => $email])->with('status', 'Calendarios actualizados');
    }
}
