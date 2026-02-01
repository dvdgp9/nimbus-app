<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AppointmentReminder extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        public Patient $patient,
        public array $links,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio: ' . $this->appointment->summary,
            from: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-reminder',
            with: [
                'appointment' => $this->appointment,
                'patient' => $this->patient,
                'confirmUrl' => $this->links['confirmUrl'],
                'cancelUrl' => $this->links['cancelUrl'],
                'rescheduleUrl' => $this->links['rescheduleUrl'],
            ],
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
