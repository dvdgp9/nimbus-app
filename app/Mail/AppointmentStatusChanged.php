<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        public Patient $patient,
        public string $action // 'confirmed' or 'cancelled'
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $actionText = $this->action === 'confirmed' ? 'confirmó' : 'canceló';
        
        return new Envelope(
            subject: "✅ {$this->patient->name} {$actionText} su cita",
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
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
