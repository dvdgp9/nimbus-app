<?php

namespace Tests\Unit;

use App\Models\Appointment;
use Tests\TestCase;

class AppointmentFormattedDateTest extends TestCase
{
    public function test_formatted_date_is_spanish_capitalized_and_does_not_include_the_year(): void
    {
        $appointment = new Appointment([
            'start_at' => '2025-01-27 10:00:00',
        ]);

        $this->assertSame('Lunes 27 de enero', $appointment->formatted_date);
        $this->assertStringNotContainsString('2025', $appointment->formatted_date);
    }
}
