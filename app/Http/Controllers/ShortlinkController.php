<?php

namespace App\Http\Controllers;

use App\Models\Shortlink;
use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use App\Services\RescheduleLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ShortlinkController extends Controller
{
    public function __construct(
        private GoogleCalendarService $calendarService
    ) {}

    /**
     * Handle shortlink action (confirm/cancel/acknowledge_cancellation)
     */
    public function handle(Request $request, string $token)
    {
        // Find and validate shortlink
        $shortlink = Shortlink::where('token', $token)->first();

        if (!$shortlink) {
            return view('shortlinks.error', [
                'message' => 'Enlace no válido',
                'detail' => 'Este enlace no existe o ha sido eliminado.',
            ]);
        }

        // Check if expired
        if ($shortlink->isExpired()) {
            return view('shortlinks.error', [
                'message' => 'Enlace caducado',
                'detail' => 'Este enlace ha expirado. Por favor, contacta con nosotros.',
            ]);
        }

        // Get appointment
        $appointment = $shortlink->appointment;

        if (!$appointment) {
            return view('shortlinks.error', [
                'message' => 'Cita no encontrada',
                'detail' => 'No se pudo encontrar la cita asociada.',
            ]);
        }

        $action = $shortlink->action;

        // Rescheduling opens WhatsApp and does not consume the confirmation/cancellation response.
        if ($action === 'reschedule') {
            return redirect()->away(RescheduleLinkService::forAppointment($appointment));
        }

        $isCancelled = in_array($appointment->nimbus_status, ['cancelled', 'cancelled_acknowledged'], true);

        // Resolve patient actions from the appointment's current state, not
        // from whether one particular email/SMS link was clicked before.
        if ($action === 'confirm' && $isCancelled) {
            return view('shortlinks.error', [
                'message' => 'Esta sesión ya está cancelada',
                'detail' => 'Por favor, contacta directamente con tu psicóloga para agendar una nueva cita, ya que la hora podría haberse ocupado.',
            ]);
        }

        if ($action === 'confirm' && $appointment->nimbus_status === 'confirmed') {
            return view('shortlinks.success', [
                'title' => 'Sesión ya confirmada',
                'message' => 'La sesión ya estaba confirmada. No hace falta hacer nada más.',
                'appointment' => $appointment,
                'action' => 'confirm',
                'already_completed' => true,
            ]);
        }

        if ($action === 'cancel' && $isCancelled) {
            return view('shortlinks.success', [
                'title' => 'Sesión ya cancelada',
                'message' => 'La sesión ya estaba cancelada. No hace falta hacer nada más.',
                'appointment' => $appointment,
                'action' => 'cancel',
                'already_completed' => true,
            ]);
        }

        switch ($action) {
            case 'confirm':
                $appointment->confirm();
                $shortlink->markAsUsed($request->ip() ?? '', (string) $request->userAgent());

                return view('shortlinks.success', [
                    'title' => 'Cita confirmada',
                    'message' => 'Tu sesión ha quedado confirmada.',
                    'appointment' => $appointment,
                    'action' => 'confirm',
                ]);

            case 'cancel':
                $appointment->cancel();
                $shortlink->markAsUsed($request->ip() ?? '', (string) $request->userAgent());

                return view('shortlinks.success', [
                    'title' => 'Cita cancelada',
                    'message' => 'Tu sesión ha quedado cancelada. Hemos avisado a tu psicóloga.',
                    'appointment' => $appointment,
                    'action' => 'cancel',
                ]);

            case 'acknowledge_cancellation':
                $response = $this->handleAcknowledgeCancellation($appointment);
                $shortlink->markAsUsed($request->ip() ?? '', (string) $request->userAgent());
                return $response;

            default:
                return view('shortlinks.error', [
                    'message' => 'Acción no válida',
                    'detail' => 'La acción solicitada no es válida.',
                ]);
        }
    }

    /**
     * Handle acknowledgement of cancellation by professional
     * Moves the appointment to Sunday of the same week
     */
    protected function handleAcknowledgeCancellation(Appointment $appointment)
    {
        // Calculate Sunday of the same week as the appointment
        $appointmentDate = $appointment->start_at;
        $sunday = $appointmentDate->copy()->endOfWeek(Carbon::SUNDAY);
        
        // If appointment is already on Sunday, keep same date
        if ($appointmentDate->isSunday()) {
            $sunday = $appointmentDate->copy();
        }

        // Move event in Google Calendar
        $moved = false;
        if ($appointment->google_event_id && $appointment->calendar_id && $appointment->patient) {
            $user = $appointment->patient->user;
            if ($user) {
                $googleToken = DB::table('google_tokens')
                    ->where('user_id', $user->id)
                    ->first();

                if ($googleToken) {
                    $moved = $this->calendarService->moveEventToDate(
                        $appointment->calendar_id,
                        $appointment->google_event_id,
                        $sunday,
                        $googleToken->account_email,
                        $user->id
                    );
                }
            }
        }

        // Update local appointment date
        $duration = $appointment->start_at->diffInMinutes($appointment->end_at);
        $newStart = $sunday->copy()->setTime($appointment->start_at->hour, $appointment->start_at->minute);
        $newEnd = $newStart->copy()->addMinutes($duration);

        $appointment->update([
            'start_at' => $newStart,
            'end_at' => $newEnd,
            'nimbus_status' => 'cancelled_acknowledged',
        ]);

        return view('shortlinks.success', [
            'title' => '✓ Cancelación confirmada',
            'message' => 'Has confirmado la cancelación. La cita se ha registrado como cancelada y se ha movido al domingo en tu calendario.',
            'appointment' => $appointment,
            'action' => 'acknowledge_cancellation',
        ]);
    }
}
