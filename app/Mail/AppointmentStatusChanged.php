<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Shortlink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $acknowledgeUrl = null;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        public Patient $patient,
        public string $action // 'confirmed' or 'cancelled'
    ) {
        // Generate acknowledge link for cancellations
        if ($action === 'cancelled') {
            $shortlink = Shortlink::createForAppointment($appointment, 'acknowledge_cancellation');
            $this->acknowledgeUrl = $shortlink->getUrl();
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $icon = $this->action === 'confirmed' ? '✅' : '❌';
        $actionText = $this->action === 'confirmed' ? 'confirmó' : 'canceló';
        $patientCode = $this->patient->code ?? 'SIN-CODIGO';
        
        return new Envelope(
            subject: "{$icon} [{$patientCode}] {$actionText} su cita",
            from: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-status-changed',
        );
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Priority' => '1',
                'X-MSMail-Priority' => 'High',
                'Importance' => 'High',
                'X-Mailer' => 'Nimbus Appointment System',
                'List-Unsubscribe' => '<mailto:' . config('mail.from.address') . '?subject=unsubscribe>',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
