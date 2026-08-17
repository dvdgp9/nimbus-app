<?php

namespace App\Services;

use App\Models\Appointment;

/**
 * N3: wa.me link that opens a chat with the *patient* and a ready-to-send
 * message, so the professional can chase a missing confirmation straight from
 * the alert email. Note this is the mirror image of RescheduleLinkService,
 * which points at the professional's own number instead.
 */
class PatientWhatsAppLinkService
{
    public static function confirmationNudge(Appointment $appointment): ?string
    {
        $patient = $appointment->patient;

        if (! $patient || ! $patient->phone) {
            return null;
        }

        // wa.me takes digits only: no plus sign, no spaces.
        $number = preg_replace('/\D+/', '', $patient->phone);

        if (! $number) {
            return null;
        }

        $message = sprintf(
            '¡Hola %s! Te escribo para confirmar nuestra sesión del %s a las %s. ¿Podrás asistir?',
            explode(' ', trim($patient->name))[0],
            $appointment->start_at->format('d/m'),
            $appointment->start_at->format('H:i'),
        );

        return 'https://wa.me/' . $number . '?text=' . urlencode($message);
    }
}
