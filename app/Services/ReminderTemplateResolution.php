<?php

namespace App\Services;

use App\Models\MessageTemplate;

class ReminderTemplateResolution
{
    public function __construct(
        public readonly ?MessageTemplate $template,
        public readonly string $status,
        public readonly string $message,
    ) {}

    public function isReady(): bool
    {
        return $this->template !== null;
    }
}
