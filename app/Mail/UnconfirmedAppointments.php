<?php

namespace App\Mail;

use App\Services\PatientWhatsAppLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * N3: digest of sessions whose reminder went out but that the patient has
 * neither confirmed nor cancelled, sent when they are about a day away.
 */
class UnconfirmedAppointments extends Mailable
{
    use Queueable, SerializesModels;

    /** @var Collection<int, array<string, mixed>> */
    public Collection $sessions;

    /**
     * @param  Collection<int, \App\Models\Appointment>  $appointments
     */
    public function __construct(Collection $appointments, public int $hoursAhead)
    {
        $this->sessions = $appointments->map(fn ($appointment) => [
            'patient_name' => $appointment->patient?->name ?? 'Paciente sin nombre',
            'date' => $appointment->formatted_date,
            'time' => $appointment->formatted_time,
            'phone' => $appointment->patient?->phone,
            // Null when the patient has no phone: the session is still listed,
            // just without the button.
            'whatsapp_url' => PatientWhatsAppLinkService::confirmationNudge($appointment),
        ]);
    }

    public function envelope(): Envelope
    {
        $count = $this->sessions->count();

        return new Envelope(
            subject: $count === 1
                ? '🔔 1 sesión sin confirmar para mañana'
                : "🔔 {$count} sesiones sin confirmar para mañana",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.unconfirmed-appointments',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
