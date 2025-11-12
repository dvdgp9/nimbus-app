<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
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

        // Get appointments from database (next 2 weeks)
        $appointments = Appointment::with('patient')
            ->where('start_at', '>=', now())
            ->where('start_at', '<=', now()->addDays(14))
            ->orderBy('start_at', 'asc')
            ->get();

        return view('events.index', [
            'account' => $email,
            'appointments' => $appointments,
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

        try {
            $events = $this->calendar->listUpcomingEvents($email, 336, $calendarIds ?: null);
            $count = $this->calendar->syncAppointments($events);
            return back()->with('status', "Sincronizados {$count} eventos de las próximas 2 semanas");
        } catch (\Google\Service\Exception $e) {
            // Check if error is due to insufficient scopes
            $error = json_decode($e->getMessage(), true);
            if (isset($error['error']['code']) && $error['error']['code'] === 403) {
                // Delete the token to force re-authentication with new scopes
                DB::table('google_tokens')->where('account_email', $email)->delete();
                
                return redirect()->route('google.connect')
                    ->with('status', 'Es necesario volver a conectar tu cuenta de Google con los permisos correctos. Por favor, autoriza el acceso a Google Calendar.');
            }
            throw $e; // Re-throw if it's a different error
        }
    }
}
