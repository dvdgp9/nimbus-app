<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnknownPatientCode extends Mailable
{
    use Queueable, SerializesModels;

    public string $createPatientUrl;
    public string $patientCode;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        string $patientCode
    ) {
        $this->patientCode = $patientCode;
        $this->createPatientUrl = route('patients.create', ['code' => $patientCode]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Código de paciente no encontrado: {$this->patientCode}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.unknown-patient-code',
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
