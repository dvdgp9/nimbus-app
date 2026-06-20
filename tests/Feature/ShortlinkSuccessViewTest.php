<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Tests\TestCase;

class ShortlinkSuccessViewTest extends TestCase
{
    public function test_success_screen_shows_date_and_time_without_internal_event_title(): void
    {
        $appointment = new Appointment([
            'summary' => 'Lar 1 SSP',
            'start_at' => '2026-06-22 09:00:00',
            'end_at' => '2026-06-22 10:00:00',
            'timezone' => 'Europe/Madrid',
        ]);

        $html = view('shortlinks.success', [
            'title' => 'Cita confirmada',
            'message' => 'Tu cita ha sido confirmada.',
            'appointment' => $appointment,
            'action' => 'confirm',
        ])->render();

        $this->assertStringNotContainsString('Lar 1 SSP', $html);
        $this->assertStringNotContainsString('>Título<', $html);
        $this->assertStringContainsString('>Fecha<', $html);
        $this->assertStringContainsString('>Hora<', $html);
        $this->assertStringContainsString('09:00 (Europe/Madrid)', $html);
    }
}
