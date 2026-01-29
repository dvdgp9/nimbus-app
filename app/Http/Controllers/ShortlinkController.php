<?php

namespace App\Http\Controllers;

use App\Models\Shortlink;
use App\Models\Appointment;
use App\Services\GoogleCalendarService;
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

        // Execute action
        $action = $shortlink->action;
        
        switch ($action) {
            case 'confirm':
                $appointment->confirm();
                
                return view('shortlinks.success', [
                    'title' => '✅ Cita confirmada',
                    'message' => 'Tu cita ha sido confirmada exitosamente.',
                    'appointment' => $appointment,
                ]);

            case 'cancel':
                $appointment->cancel();
                
                return view('shortlinks.success', [
                    'title' => '❌ Cita cancelada',
                    'message' => 'Tu cita ha sido cancelada. Te confirmaremos la cancelación por email.',
                    'appointment' => $appointment,
                ]);

            case 'acknowledge_cancellation':
                return $this->handleAcknowledgeCancellation($appointment);

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
            'message' => $moved 
                ? "Has confirmado la cancelación. La cita se ha movido al domingo {$sunday->format('d/m/Y')} en tu calendario."
                : "Has confirmado la cancelación. La cita se ha registrado como cancelada.",
            'appointment' => $appointment,
        ]);
    }
}
