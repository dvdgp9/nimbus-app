<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Shortlink;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShortlinkIdempotencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('google_event_id')->unique();
            $table->string('calendar_id');
            $table->string('summary');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('timezone');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('nimbus_status')->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shortlinks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('token')->unique();
            $table->string('action');
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->string('used_ip')->nullable();
            $table->string('used_user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_get_only_shows_the_confirmation_step_without_changing_the_appointment(): void
    {
        $appointment = $this->appointment();
        $link = $this->shortlink($appointment, 'confirm', now()->subDay());

        $this->get(route('shortlink.handle', $link->token))
            ->assertOk()
            ->assertSee('Gestiona tu cita')
            ->assertSee('Confirmar asistencia');

        $this->assertSame('pending', $appointment->fresh()->nimbus_status);
        $this->assertFalse($link->fresh()->used);
    }

    public function test_confirmation_page_can_manage_all_patient_actions_from_one_link(): void
    {
        $appointment = $this->appointment();
        $confirm = $this->shortlink($appointment, 'confirm');
        $cancel = $this->shortlink($appointment, 'cancel');
        $reschedule = $this->shortlink($appointment, 'reschedule');

        $this->get(route('shortlink.handle', $confirm->token))
            ->assertOk()
            ->assertSee('Gestiona tu cita')
            ->assertSee('Confirmar asistencia')
            ->assertSee('Cancelar cita')
            ->assertSee('Cambiar cita')
            ->assertSee(route('shortlink.handle', $cancel->token), false)
            ->assertSee(route('shortlink.handle', $reschedule->token), false);

        $this->assertSame('pending', $appointment->fresh()->nimbus_status);
    }

    public function test_post_explicitly_confirms_the_appointment(): void
    {
        $appointment = $this->appointment();
        $link = $this->shortlink($appointment, 'confirm', now()->subDay());

        $this->post(route('shortlink.execute', $link->token))
            ->assertOk()
            ->assertSee('Cita confirmada');

        $this->assertSame('confirmed', $appointment->fresh()->nimbus_status);
        $this->assertTrue($link->fresh()->used);
    }

    public function test_get_only_shows_the_cancellation_step_without_changing_the_appointment(): void
    {
        $appointment = $this->appointment();
        $link = $this->shortlink($appointment, 'cancel');

        $this->get(route('shortlink.handle', $link->token))
            ->assertOk()
            ->assertSee('¿Quieres cancelar esta sesión?')
            ->assertSee('Cancelar definitivamente');

        $this->assertSame('pending', $appointment->fresh()->nimbus_status);
        $this->assertFalse($link->fresh()->used);
    }

    public function test_post_explicitly_cancels_the_appointment(): void
    {
        $appointment = $this->appointment();
        $link = $this->shortlink($appointment, 'cancel');

        $this->post(route('shortlink.execute', $link->token))
            ->assertOk()
            ->assertSee('Cita cancelada');

        $this->assertSame('cancelled', $appointment->fresh()->nimbus_status);
        $this->assertTrue($link->fresh()->used);
    }

    public function test_confirming_an_already_confirmed_appointment_is_idempotent(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');
        $appointment = $this->appointment();
        $emailLink = $this->shortlink($appointment, 'confirm');
        $smsLink = $this->shortlink($appointment, 'confirm');

        $this->post(route('shortlink.execute', $emailLink->token))->assertOk();
        $originalConfirmedAt = $appointment->fresh()->confirmed_at;
        Carbon::setTestNow(now()->addHour());

        $this->post(route('shortlink.execute', $smsLink->token))
            ->assertOk()
            ->assertSee('La sesión ya estaba confirmada');

        $this->assertTrue($appointment->fresh()->confirmed_at->equalTo($originalConfirmedAt));
    }

    public function test_cancelling_an_already_cancelled_appointment_is_idempotent(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');
        $appointment = $this->appointment();
        $emailLink = $this->shortlink($appointment, 'cancel');
        $smsLink = $this->shortlink($appointment, 'cancel');

        $this->post(route('shortlink.execute', $emailLink->token))->assertOk();
        $originalCancelledAt = $appointment->fresh()->cancelled_at;
        Carbon::setTestNow(now()->addHour());

        $this->post(route('shortlink.execute', $smsLink->token))
            ->assertOk()
            ->assertSee('La sesión ya estaba cancelada');

        $this->assertTrue($appointment->fresh()->cancelled_at->equalTo($originalCancelledAt));
    }

    public function test_a_cancelled_appointment_cannot_be_confirmed_again_from_an_old_link(): void
    {
        $appointment = $this->appointment(['nimbus_status' => 'cancelled', 'cancelled_at' => now()]);
        $link = $this->shortlink($appointment, 'confirm');

        $this->post(route('shortlink.execute', $link->token))
            ->assertOk()
            ->assertSee('Esta sesión ya está cancelada')
            ->assertSee('contacta directamente con tu psicóloga para agendar una nueva cita')
            ->assertSee('la hora podría haberse ocupado');

        $this->assertSame('cancelled', $appointment->fresh()->nimbus_status);
    }

    public function test_acknowledgement_get_does_not_move_or_change_the_cancelled_appointment(): void
    {
        $appointment = $this->appointment([
            'nimbus_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        $originalStart = $appointment->start_at;
        $link = $this->shortlink($appointment, 'acknowledge_cancellation');

        $this->get(route('shortlink.handle', $link->token))
            ->assertOk()
            ->assertSee('Confirmar que has visto la cancelación')
            ->assertSee('Registrar cancelación');

        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->nimbus_status);
        $this->assertTrue($appointment->start_at->equalTo($originalStart));
        $this->assertFalse($link->fresh()->used);
    }

    public function test_acknowledgement_post_explicitly_records_the_cancellation(): void
    {
        $appointment = $this->appointment([
            'nimbus_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        $link = $this->shortlink($appointment, 'acknowledge_cancellation');

        $this->post(route('shortlink.execute', $link->token))
            ->assertOk()
            ->assertSee('Cancelación confirmada');

        $this->assertSame('cancelled_acknowledged', $appointment->fresh()->nimbus_status);
        $this->assertTrue($link->fresh()->used);
    }

    public function test_safe_get_writes_a_minimised_structured_audit_log(): void
    {
        Log::spy();
        $appointment = $this->appointment();
        $link = $this->shortlink($appointment, 'confirm');

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.42',
            'HTTP_USER_AGENT' => str_repeat('ScannerAgent/', 30),
        ])->get(route('shortlink.handle', $link->token))->assertOk();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($appointment, $link): bool {
                return $message === 'Public appointment link viewed'
                    && $context['appointment_id'] === $appointment->id
                    && $context['shortlink_id'] === $link->id
                    && $context['action'] === 'confirm'
                    && $context['http_method'] === 'GET'
                    && $context['result'] === 'confirmation_shown'
                    && strlen($context['ip_hash']) === 16
                    && $context['ip_hash'] !== '203.0.113.42'
                    && strlen($context['user_agent']) <= 180
                    && ! str_contains(json_encode($context), $appointment->summary);
            });
    }

    public function test_explicit_post_writes_submitted_and_executed_audit_logs(): void
    {
        $appointment = $this->appointment();
        $link = $this->shortlink($appointment, 'cancel');
        $records = [];

        Log::shouldReceive('info')
            ->twice()
            ->withArgs(function (string $message, array $context) use (&$records): bool {
                $records[$message] = $context;

                return true;
            });
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $this->post(route('shortlink.execute', $link->token))->assertOk();

        $this->assertSame('submitted', $records['Public appointment action submitted']['result']);
        $this->assertSame($appointment->id, $records['Public appointment action submitted']['appointment_id']);
        $this->assertSame('cancel', $records['Public appointment action submitted']['action']);
        $this->assertSame('executed', $records['Public appointment action executed']['result']);
        $this->assertSame('cancelled', $records['Public appointment action executed']['appointment_status']);
    }

    #[DataProvider('emailScannerUserAgents')]
    public function test_email_security_scanners_can_open_both_links_without_changing_state(
        string $userAgent
    ): void {
        $appointment = $this->appointment();
        $confirmLink = $this->shortlink($appointment, 'confirm');
        $cancelLink = $this->shortlink($appointment, 'cancel');

        $this->withServerVariables(['HTTP_USER_AGENT' => $userAgent])
            ->get(route('shortlink.handle', $confirmLink->token))
            ->assertOk();
        $this->withServerVariables(['HTTP_USER_AGENT' => $userAgent])
            ->get(route('shortlink.handle', $cancelLink->token))
            ->assertOk();

        $appointment->refresh();
        $this->assertSame('pending', $appointment->nimbus_status);
        $this->assertNull($appointment->confirmed_at);
        $this->assertNull($appointment->cancelled_at);
        $this->assertFalse($confirmLink->fresh()->used);
        $this->assertFalse($cancelLink->fresh()->used);
    }

    public static function emailScannerUserAgents(): array
    {
        return [
            'Microsoft Defender Safe Links' => [
                'Mozilla/5.0 (compatible; Microsoft Office SafeLinks; +https://security.microsoft.com)',
            ],
            'Google link protection' => [
                'Mozilla/5.0 (compatible; Google-Safety; +https://google.com/safebrowsing)',
            ],
        ];
    }

    private function appointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'google_event_id' => 'event-'.uniqid(),
            'calendar_id' => 'primary',
            'summary' => 'Sesión',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
            'timezone' => 'Europe/Madrid',
            'nimbus_status' => 'pending',
        ], $overrides));
    }

    private function shortlink(
        Appointment $appointment,
        string $action,
        ?Carbon $expiresAt = null
    ): Shortlink {
        return Shortlink::create([
            'appointment_id' => $appointment->id,
            'token' => Shortlink::generateToken(),
            'action' => $action,
            'expires_at' => $expiresAt ?? now()->addHours(72),
        ]);
    }
}
