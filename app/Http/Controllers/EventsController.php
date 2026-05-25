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

        $filter = $request->query('filter');

        // Get ALL appointments from user's calendars (next 30 days)
        // Don't filter by patient ownership - show all events
        $appointments = Appointment::with('patient')
            ->when($calendarIds, function ($query) use ($calendarIds) {
                $query->whereIn('calendar_id', $calendarIds);
            })
            ->when($filter === 'unassigned', function ($query) {
                $query->whereNull('patient_id')
                      ->whereNotIn('nimbus_status', ['cancelled', 'cancelled_acknowledged']);
            })
            ->where('start_at', '>=', now())
            ->where('start_at', '<=', now()->addDays(30))
            ->orderBy('start_at', 'asc')
            ->get();

        return view('events.index', [
            'account' => $email,
            'appointments' => $appointments,
            'filter' => $filter,
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
            $hoursAhead = 720; // 30 días
            $events = $this->calendar->listUpcomingEvents($email, $hoursAhead, $calendarIds ?: null, auth()->id());
            $count = $this->calendar->syncAppointments($events, auth()->id(), $calendarIds ?: null, $hoursAhead);
            return back()->with('status', "Sincronizados {$count} eventos de los próximos 30 días");
        } catch (\Google\Service\Exception $e) {
            $error = json_decode($e->getMessage(), true);
            if (isset($error['error']['code']) && $error['error']['code'] === 403) {
                // No borramos el token: mantenemos el registro hasta que la psicóloga
                // reconecte correctamente. Así no perdemos contexto si decide cancelar.
                return redirect()->route('google.connect')->withErrors([
                    'google' => 'Google rechazó la sincronización por permisos insuficientes (error 403). Vuelve a conectar tu cuenta y, en la pantalla de Google, asegúrate de marcar TODAS las casillas de permisos solicitados (lectura de calendarios y eventos). Sin esos permisos Nimbus no puede leer tus citas.',
                ]);
            }
            throw $e;
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
