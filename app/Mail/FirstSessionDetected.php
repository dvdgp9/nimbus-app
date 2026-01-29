<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FirstSessionDetected extends Mailable
{
    use Queueable, SerializesModels;

    public string $createPatientUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        public array $patientData,
        array $prefillParams
    ) {
        $this->createPatientUrl = route('patients.create', $prefillParams);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🆕 Nueva primera sesión detectada - {$this->appointment->formatted_date}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.first-session-detected',
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
