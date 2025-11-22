<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventsController extends Controller
{
    public function __construct(
        private GoogleCalendarService $calendar,
        private NotificationService $notifications,
    ) {}

    public function index(Request $request)
    {
        $email = $request->query('account');
        // If not provided, try first connected account of current user
        if (!$email) {
            $row = DB::table('google_tokens')
                ->where('user_id', auth()->id())
                ->orderByDesc('updated_at')
                ->first();
            $email = $row->account_email ?? null;
        }

        // Get calendar IDs for current user's enabled calendars
        $calendarIds = DB::table('connected_calendars')
            ->where('user_id', auth()->id())
            ->where('enabled', 1)
            ->pluck('calendar_id')
            ->all();

        // Get ALL appointments from user's calendars (next 2 weeks)
        // Don't filter by patient ownership - show all events
        $appointments = Appointment::with('patient')
            ->when($calendarIds, function ($query) use ($calendarIds) {
                $query->whereIn('calendar_id', $calendarIds);
            })
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
            ->where('user_id', auth()->id())
            ->where('account_email', $email)
            ->where('enabled', 1)
            ->pluck('calendar_id')
            ->all();

        try {
            $hoursAhead = 336; // 2 semanas
            $events = $this->calendar->listUpcomingEvents($email, $hoursAhead, $calendarIds ?: null);
            $count = $this->calendar->syncAppointments($events, auth()->id(), $calendarIds ?: null, $hoursAhead);
            return back()->with('status', "Sincronizados {$count} eventos de las próximas 2 semanas");
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
    }

    /**
     * Send reminders for upcoming appointments (next 24h) for current user.
     */
    public function sendReminders(Request $request)
    {
        // For now: fixed 24 hours window
        $hoursAhead = 24;

        $appointments = Appointment::needsReminder($hoursAhead)
            ->withPatient()
            ->whereHas('patient', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->get();

        if ($appointments->isEmpty()) {
            return back()->with('status', 'No hay citas que necesiten recordatorio en las próximas 24 horas.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($appointments as $appointment) {
            if (!$appointment->patient) {
                continue;
            }

            $ok = $this->notifications->sendReminder($appointment);
            if ($ok) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $message = "Recordatorios enviados: {$sent}";
        if ($failed > 0) {
            $message .= " | Fallidos: {$failed}";
        }

        return back()->with('status', $message);
    }
}
