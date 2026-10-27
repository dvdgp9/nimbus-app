<?php

namespace App\Mail;

use App\Models\Communication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class UnconfirmedSmsDelivery extends Mailable
{
    public function __construct(public Communication $communication) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Nimbus] Revisar SMS de '.$this->communication->patient->name);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.unconfirmed-sms-delivery');
    }
}
