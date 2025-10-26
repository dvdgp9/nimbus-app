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
            $events = $this->calendar->listUpcomingEvents($email, 48);
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
        $events = $this->calendar->listUpcomingEvents($email, 48);
        $count = $this->calendar->syncAppointments($events);
        return back()->with('status', "Sincronizados {$count} eventos de las próximas 48h");
    }
}
