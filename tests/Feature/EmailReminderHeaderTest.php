<?php

namespace Tests\Feature;

use App\Mail\AppointmentReminder;
use App\Mail\TemplatedReminder;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Tests\TestCase;

class EmailReminderHeaderTest extends TestCase
{
    public function test_both_reminder_emails_show_an_absolute_logo_and_hide_the_header_name(): void
    {
        config([
            'app.url' => 'https://nimbus.test',
            'filesystems.disks.public.url' => 'https://nimbus.test/storage',
        ]);

        [$appointment, $patient] = $this->emailModels('email-logos/laura.png');

        foreach ($this->renderBothMailables($appointment, $patient) as $html) {
            $this->assertStringContainsString('data-email-header="logo"', $html);
            $this->assertStringContainsString('src="https://nimbus.test/storage/email-logos/laura.png"', $html);
            $this->assertStringNotContainsString('data-email-header="name"', $html);
        }
    }

    public function test_both_reminder_emails_show_the_professional_name_when_there_is_no_logo(): void
    {
        [$appointment, $patient] = $this->emailModels();

        foreach ($this->renderBothMailables($appointment, $patient) as $html) {
            $this->assertStringContainsString('data-email-header="name"', $html);
            $this->assertStringContainsString('Laura Martínez', $html);
            $this->assertStringNotContainsString('data-email-header="logo"', $html);
        }
    }

    public function test_both_reminder_emails_use_the_custom_sender_name_and_global_address(): void
    {
        config(['mail.from.address' => 'recordatorios@nimbus.test']);
        [$appointment, $patient] = $this->emailModels();
        $patient->user->email_sender_name = 'Consulta Laura';

        foreach ($this->mailables($appointment, $patient) as $mailable) {
            $from = $mailable->envelope()->from;

            $this->assertSame('recordatorios@nimbus.test', $from->address);
            $this->assertSame('Consulta Laura', $from->name);
        }
    }

    public function test_both_reminder_emails_fall_back_to_the_profile_name_for_the_sender(): void
    {
        config(['mail.from.address' => 'recordatorios@nimbus.test']);
        [$appointment, $patient] = $this->emailModels();

        foreach ($this->mailables($appointment, $patient) as $mailable) {
            $from = $mailable->envelope()->from;

            $this->assertSame('recordatorios@nimbus.test', $from->address);
            $this->assertSame('Laura Martínez', $from->name);
        }
    }

    public function test_both_reminder_emails_render_green_confirm_and_red_cancel_buttons(): void
    {
        [$appointment, $patient] = $this->emailModels();

        foreach ($this->renderBothMailables($appointment, $patient) as $html) {
            $this->assertMatchesRegularExpression(
                '/data-email-action="confirm"[^>]*style="[^"]*background:#2e7d32[^"]*color:#ffffff/',
                $html,
            );
            $this->assertMatchesRegularExpression(
                '/data-email-action="cancel"[^>]*style="[^"]*background:#c62828[^"]*color:#ffffff/',
                $html,
            );
        }
    }

    /**
     * @return array{Appointment, Patient}
     */
    private function emailModels(?string $logoPath = null): array
    {
        $user = new User([
            'name' => 'Laura Martínez',
            'email_logo_path' => $logoPath,
        ]);

        $patient = new Patient([
            'name' => 'Clara Robles',
            'email' => 'clara@example.com',
        ]);
        $patient->setRelation('user', $user);

        $appointment = new Appointment([
            'summary' => 'Sesión de seguimiento',
            'start_at' => '2025-01-27 10:00:00',
            'timezone' => 'Europe/Madrid',
        ]);

        return [$appointment, $patient];
    }

    /**
     * @return array<string>
     */
    private function renderBothMailables(Appointment $appointment, Patient $patient): array
    {
        return array_map(
            fn ($mailable) => $mailable->render(),
            $this->mailables($appointment, $patient),
        );
    }

    /**
     * @return array{AppointmentReminder, TemplatedReminder}
     */
    private function mailables(Appointment $appointment, Patient $patient): array
    {
        $links = [
            'confirmUrl' => 'https://nimbus.test/confirm',
            'cancelUrl' => 'https://nimbus.test/cancel',
            'rescheduleUrl' => 'https://nimbus.test/reschedule',
        ];

        return [
            new AppointmentReminder($appointment, $patient, $links),
            new TemplatedReminder(
                $appointment,
                $patient,
                'Recordatorio de sesión',
                "Hola Clara,\n\n[BOTON_CONFIRMAR]\n\n[BOTON_CANCELAR]",
                $links,
            ),
        ];
    }
}
