<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the professional the shared Acumbamail balance is running out, so they
 * can top it up before reminders stop going out.
 */
class LowSmsBalance extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $sms, public int $threshold)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Quedan {$this->sms} SMS en Acumbamail",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-sms-balance',
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
