<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class YellowAppointmentReview extends Mailable
{
    use Queueable, SerializesModels;

    public string $confirmUrl;
    public string $cancelUrl;

    public function __construct(public Appointment $appointment)
    {
        $expiration = $appointment->start_at->copy()->addHour();

        $this->confirmUrl = URL::temporarySignedRoute(
            'professional-review.show',
            $expiration,
            ['appointment' => $appointment->id, 'decision' => 'confirm'],
        );
        $this->cancelUrl = URL::temporarySignedRoute(
            'professional-review.show',
            $expiration,
            ['appointment' => $appointment->id, 'decision' => 'cancel'],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Nimbus] Revisa la cita del {$this->appointment->formatted_date}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.yellow-appointment-review');
    }

    public function attachments(): array
    {
        return [];
    }
}
