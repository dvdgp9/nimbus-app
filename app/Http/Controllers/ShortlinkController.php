<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Shortlink;
use App\Services\GoogleCalendarService;
use App\Services\PublicLinkAuditService;
use App\Services\RescheduleLinkService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ShortlinkController extends Controller
{
    public function __construct(
        private GoogleCalendarService $calendarService,
        private PublicLinkAuditService $audit,
    ) {}

    /**
     * Show a safe, read-only confirmation step.
     *
     * Email security scanners may visit every URL in a message. This GET must
     * never change appointment state, update Google Calendar or send mail.
     */
    public function handle(Request $request, string $token)
    {
        $shortlink = $this->resolveShortlink($token);
        if (! $shortlink instanceof Shortlink) {
            return $shortlink;
        }

        $appointment = $shortlink->appointment;
        $action = $shortlink->action;

        // Rescheduling only leaves Nimbus for WhatsApp and does not mutate data.
        if ($action === 'reschedule') {
            $this->audit->record(
                'Public appointment link viewed',
                $request,
                $shortlink,
                $appointment,
                'redirected_to_reschedule'
            );

            return redirect()->away(RescheduleLinkService::forAppointment($appointment));
        }

        if ($completedResponse = $this->completedStateResponse($appointment, $action)) {
            $this->audit->record(
                'Public appointment link viewed',
                $request,
                $shortlink,
                $appointment,
                'state_already_resolved'
            );

            return $completedResponse;
        }

        $content = match ($action) {
            'confirm' => [
                'title' => 'Gestiona tu cita',
                'message' => 'Revisa la fecha y la hora y elige qué necesitas hacer.',
                'button' => 'Confirmar asistencia',
                'buttonClass' => 'shortlink-action-confirm',
            ],
            'cancel' => [
                'title' => '¿Quieres cancelar esta sesión?',
                'message' => 'La cancelación se comunicará a tu psicóloga. Comprueba los datos antes de continuar.',
                'button' => 'Cancelar definitivamente',
                'buttonClass' => 'shortlink-action-cancel',
            ],
            'acknowledge_cancellation' => [
                'title' => 'Confirmar que has visto la cancelación',
                'message' => 'Al continuar, la cancelación quedará registrada y la cita se moverá al domingo en tu calendario.',
                'button' => 'Registrar cancelación',
                'buttonClass' => 'shortlink-action-acknowledge',
            ],
            default => null,
        };

        if (! $content) {
            $this->audit->record(
                'Public appointment link viewed',
                $request,
                $shortlink,
                $appointment,
                'invalid_action'
            );

            return $this->invalidActionResponse();
        }

        $this->audit->record(
            'Public appointment link viewed',
            $request,
            $shortlink,
            $appointment,
            'confirmation_shown'
        );

        return view('shortlinks.confirm-action', [
            ...$content,
            'appointment' => $appointment,
            'token' => $shortlink->token,
            'cancelUrl' => $action === 'confirm'
                ? $this->relatedActionUrl($appointment, 'cancel')
                : null,
            'rescheduleUrl' => $action === 'confirm'
                ? $this->relatedActionUrl($appointment, 'reschedule')
                : null,
        ]);
    }

    /**
     * Execute an action only after the person submits the confirmation form.
     */
    public function execute(Request $request, string $token)
    {
        $shortlink = $this->resolveShortlink($token);
        if (! $shortlink instanceof Shortlink) {
            return $shortlink;
        }

        $appointment = $shortlink->appointment;
        $action = $shortlink->action;

        $this->audit->record(
            'Public appointment action submitted',
            $request,
            $shortlink,
            $appointment,
            'submitted'
        );

        if ($action === 'reschedule') {
            return redirect()->away(RescheduleLinkService::forAppointment($appointment));
        }

        if ($completedResponse = $this->completedStateResponse($appointment, $action)) {
            $this->audit->record(
                'Public appointment action not executed',
                $request,
                $shortlink,
                $appointment,
                'state_already_resolved'
            );

            return $completedResponse;
        }

        switch ($action) {
            case 'confirm':
                $appointment->confirm();
                $shortlink->markAsUsed($request->ip() ?? '', (string) $request->userAgent());
                $this->audit->record(
                    'Public appointment action executed',
                    $request,
                    $shortlink,
                    $appointment,
                    'executed'
                );

                return view('shortlinks.success', [
                    'title' => 'Cita confirmada',
                    'message' => 'Tu sesión ha quedado confirmada.',
                    'appointment' => $appointment,
                    'action' => 'confirm',
                ]);

            case 'cancel':
                $appointment->cancel();
                $shortlink->markAsUsed($request->ip() ?? '', (string) $request->userAgent());
                $this->audit->record(
                    'Public appointment action executed',
                    $request,
                    $shortlink,
                    $appointment,
                    'executed'
                );

                return view('shortlinks.success', [
                    'title' => 'Cita cancelada',
                    'message' => 'Tu sesión ha quedado cancelada. Hemos avisado a tu psicóloga.',
                    'appointment' => $appointment,
                    'action' => 'cancel',
                ]);

            case 'acknowledge_cancellation':
                $response = $this->handleAcknowledgeCancellation($appointment);
                $shortlink->markAsUsed($request->ip() ?? '', (string) $request->userAgent());
                $this->audit->record(
                    'Public appointment action executed',
                    $request,
                    $shortlink,
                    $appointment,
                    'executed'
                );

                return $response;

            default:
                $this->audit->record(
                    'Public appointment action not executed',
                    $request,
                    $shortlink,
                    $appointment,
                    'invalid_action'
                );

                return $this->invalidActionResponse();
        }
    }

    private function resolveShortlink(string $token): Shortlink|View
    {
        $shortlink = Shortlink::where('token', $token)->first();

        if (! $shortlink) {
            return view('shortlinks.error', [
                'message' => 'Enlace no válido',
                'detail' => 'Este enlace no existe o ha sido eliminado.',
            ]);
        }

        if ($shortlink->isExpired()) {
            return view('shortlinks.error', [
                'message' => 'Enlace caducado',
                'detail' => 'Este enlace ha expirado. Por favor, contacta con nosotros.',
            ]);
        }

        if (! $shortlink->appointment) {
            return view('shortlinks.error', [
                'message' => 'Cita no encontrada',
                'detail' => 'No se pudo encontrar la cita asociada.',
            ]);
        }

        return $shortlink;
    }

    private function completedStateResponse(Appointment $appointment, string $action): ?View
    {
        $isCancelled = in_array($appointment->nimbus_status, ['cancelled', 'cancelled_acknowledged'], true);

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

        return null;
    }

    private function invalidActionResponse(): View
    {
        return view('shortlinks.error', [
            'message' => 'Acción no válida',
            'detail' => 'La acción solicitada no es válida.',
        ]);
    }

    private function relatedActionUrl(Appointment $appointment, string $action): ?string
    {
        $link = $appointment->shortlinks()
            ->where('action', $action)
            ->latest('id')
            ->first();

        return $link?->isValid() ? $link->getUrl() : null;
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
