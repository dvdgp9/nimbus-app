<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Services\RescheduleLinkService;
use Tests\TestCase;

class RescheduleLinkServiceTest extends TestCase
{
    public function test_it_builds_the_configured_whatsapp_link_for_the_appointment(): void
    {
        config(['services.whatsapp.reschedule_number' => '34600111222']);
        $appointment = new Appointment([
            'start_at' => '2026-06-25 10:00:00',
        ]);

        $url = RescheduleLinkService::forAppointment($appointment);

        $this->assertSame(
            'https://wa.me/34600111222?text=Hola%21+Me+gustar%C3%ADa+cambiar+la+cita+del+25%2F06',
            $url
        );
    }
}
