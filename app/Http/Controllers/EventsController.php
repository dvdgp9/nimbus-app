<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventsController extends Controller
{
    public function __construct(private GoogleCalendarService $calendar) {}

    public function index(Request $request)
    {
        $email = $request->query('account');
        // If not provided, try first connected account
        if (!$email) {
            $row = DB::table('google_tokens')->orderByDesc('updated_at')->first();
            $email = $row->account_email ?? null;
        }

        $events = [];
        if ($email) {
            $calendarIds = DB::table('connected_calendars')
                ->where('account_email', $email)
                ->where('enabled', 1)
                ->pluck('calendar_id')
                ->all();

            $events = $this->calendar->listUpcomingEvents($email, 48, $calendarIds ?: null);
        }

        return view('events.index', [
            'account' => $email,
            'events' => $events,
        ]);
    }

    public function sync(Request $request)
    {
        $email = $request->input('account');
        if (!$email) {
            return back()->withErrors(['account' => 'Selecciona una cuenta conectada']);
        }
        $calendarIds = DB::table('connected_calendars')
            ->where('account_email', $email)
            ->where('enabled', 1)
            ->pluck('calendar_id')
            ->all();

        $events = $this->calendar->listUpcomingEvents($email, 48, $calendarIds ?: null);
        $count = $this->calendar->syncAppointments($events);
        return back()->with('status', "Sincronizados {$count} eventos de las próximas 48h");
    }
}
