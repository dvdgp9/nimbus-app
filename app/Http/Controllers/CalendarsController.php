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
            $row = DB::table('google_tokens')->orderByDesc('updated_at')->first();
            $email = $row->account_email ?? null;
        }
        if (!$email) {
            return redirect()->route('google.connect');
        }

        $all = $this->calendar->listCalendars($email);
        $enabled = DB::table('connected_calendars')
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
            // Deshabilitar todo primero
            DB::table('connected_calendars')->where('account_email', $email)->update(['enabled' => 0, 'updated_at' => now()]);

            // Upsert de los seleccionados (habilitar)
            foreach ($selected as $calId) {
                DB::table('connected_calendars')->updateOrInsert(
                    [ 'account_email' => $email, 'calendar_id' => $calId ],
                    [ 'enabled' => 1, 'updated_at' => now(), 'created_at' => now() ]
                );
            }
        });

        return redirect()->route('events.index', ['account' => $email])->with('status', 'Calendarios actualizados');
    }
}
