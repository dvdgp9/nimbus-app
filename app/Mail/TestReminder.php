<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestReminder extends Mailable
{
    use Queueable, SerializesModels;

    public string $body;
    public string $subjectLine;

    public function __construct(string $subjectLine, string $body)
    {
        $this->subjectLine = $subjectLine;
        $this->body = $body;
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('email.message');
    }
}
