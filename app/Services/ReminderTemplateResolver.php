<?php

namespace App\Services;

use App\Models\Appointment;

class ReminderTemplateResolver
{
    public function resolve(
        Appointment $appointment,
        string $channel,
        ?int $expectedUserId = null
    ): ReminderTemplateResolution {
        $patient = $appointment->patient;
        if (! $patient) {
            return $this->blocked('no_patient', 'Sin paciente asignado.');
        }

        $user = $patient->user;
        if (! $user) {
            return $this->blocked('no_user', 'El paciente no pertenece a ninguna cuenta.');
        }

        if ($expectedUserId !== null && $user->id !== $expectedUserId) {
            return $this->blocked('wrong_owner', 'El paciente no pertenece a tu cuenta.');
        }

        $query = $user->messageTemplates()
            ->forChannel($channel)
            ->orderBy('id');

        $messageCode = $this->normaliseCode($appointment->message_code);
        if ($messageCode !== null) {
            $template = (clone $query)->where('code', $messageCode)->first();

            if (! $template) {
                return $this->blocked(
                    'missing_code',
                    "Código {$messageCode} sin plantilla de {$this->channelLabel($channel)}."
                );
            }

            return new ReminderTemplateResolution(
                $template,
                'matched_code',
                "Plantilla: {$template->name} (código {$messageCode})."
            );
        }

        $template = $query->default()->first();
        if (! $template) {
            return $this->blocked(
                'missing_default',
                "Sin plantilla predeterminada de {$this->channelLabel($channel)}."
            );
        }

        return new ReminderTemplateResolution(
            $template,
            'default',
            "Plantilla predeterminada: {$template->name}."
        );
    }

    private function blocked(string $status, string $message): ReminderTemplateResolution
    {
        return new ReminderTemplateResolution(null, $status, $message);
    }

    private function normaliseCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code !== '' ? $code : null;
    }

    private function channelLabel(string $channel): string
    {
        return match ($channel) {
            'email' => 'email',
            'sms' => 'SMS',
            default => $channel,
        };
    }
}
