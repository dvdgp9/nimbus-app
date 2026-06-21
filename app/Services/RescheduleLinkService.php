<?php

namespace App\Services;

use App\Models\Appointment;

class RescheduleLinkService
{
    public static function forAppointment(Appointment $appointment): string
    {
        $message = sprintf(
            'Hola! Me gustaría cambiar la cita del %s',
            $appointment->start_at->format('d/m')
        );

        return 'https://wa.me/' . config('services.whatsapp.reschedule_number')
            . '?text=' . urlencode($message);
    }
}
