<?php

namespace Tests\Unit;

use App\Models\MessageTemplate;
use App\Services\ReminderTemplateResolution;
use Tests\TestCase;

class TemplateResolutionViewTest extends TestCase
{
    public function test_ready_resolution_shows_the_exact_template_name(): void
    {
        $resolution = new ReminderTemplateResolution(
            new MessageTemplate(['name' => 'Carta seguimiento BP']),
            'matched_code',
            'Plantilla: Carta seguimiento BP (código BP).'
        );

        $html = view('events.partials.template-resolution', [
            'channel' => 'Email',
            'resolution' => $resolution,
            'channelActive' => true,
        ])->render();

        $this->assertStringContainsString('Email', $html);
        $this->assertStringContainsString('Carta seguimiento BP', $html);
        $this->assertStringContainsString('Lista para enviar', $html);
    }

    public function test_blocked_resolution_shows_the_reason_instead_of_a_fallback(): void
    {
        $resolution = new ReminderTemplateResolution(
            null,
            'missing_code',
            'Código XYZ sin plantilla de email.'
        );

        $html = view('events.partials.template-resolution', [
            'channel' => 'Email',
            'resolution' => $resolution,
            'channelActive' => true,
        ])->render();

        $this->assertStringContainsString('Recordatorio bloqueado', $html);
        $this->assertStringContainsString('Código XYZ sin plantilla de email.', $html);
        $this->assertStringNotContainsString('correo genérico', $html);
    }

    public function test_inactive_channel_is_identified_without_reporting_a_template_error(): void
    {
        $resolution = new ReminderTemplateResolution(
            null,
            'missing_default',
            'Sin plantilla predeterminada de SMS.'
        );

        $html = view('events.partials.template-resolution', [
            'channel' => 'SMS',
            'resolution' => $resolution,
            'channelActive' => false,
        ])->render();

        $this->assertStringContainsString('Canal no activo', $html);
        $this->assertStringNotContainsString('Recordatorio bloqueado', $html);
    }
}
