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

    /**
     * N2: $patientCode is null when the event title has no readable code at all.
     * Those used to produce nothing but a log line.
     */
    public ?string $patientCode;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        ?string $patientCode,
        public bool $escalated = false
    ) {
        $this->patientCode = $patientCode;
        $this->createPatientUrl = $patientCode
            ? route('patients.create', ['code' => $patientCode])
            : route('patients.create');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $date = $this->appointment->formatted_date;

        if ($this->escalated) {
            return new Envelope(
                subject: $this->patientCode
                    ? "🚨 Urgente: la sesión del {$date} sigue sin paciente ({$this->patientCode})"
                    : "🚨 Urgente: la sesión del {$date} sigue sin paciente",
            );
        }

        return new Envelope(
            subject: $this->patientCode
                ? "⚠️ Código de paciente no encontrado: {$this->patientCode}"
                : "⚠️ Cita sin paciente identificable: {$date}",
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
