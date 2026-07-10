<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Shortlink;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
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

    public function test_an_expired_confirmation_link_still_confirms_the_appointment(): void
    {
        $appointment = $this->appointment();
        $link = $this->shortlink($appointment, 'confirm', now()->subDay());

        $this->get(route('shortlink.handle', $link->token))
            ->assertOk()
            ->assertSee('Cita confirmada');

        $this->assertSame('confirmed', $appointment->fresh()->nimbus_status);
    }

    public function test_confirming_an_already_confirmed_appointment_is_idempotent(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');
        $appointment = $this->appointment();
        $emailLink = $this->shortlink($appointment, 'confirm');
        $smsLink = $this->shortlink($appointment, 'confirm');

        $this->get(route('shortlink.handle', $emailLink->token))->assertOk();
        $originalConfirmedAt = $appointment->fresh()->confirmed_at;
        Carbon::setTestNow(now()->addHour());

        $this->get(route('shortlink.handle', $smsLink->token))
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

        $this->get(route('shortlink.handle', $emailLink->token))->assertOk();
        $originalCancelledAt = $appointment->fresh()->cancelled_at;
        Carbon::setTestNow(now()->addHour());

        $this->get(route('shortlink.handle', $smsLink->token))
            ->assertOk()
            ->assertSee('La sesión ya estaba cancelada');

        $this->assertTrue($appointment->fresh()->cancelled_at->equalTo($originalCancelledAt));
    }

    public function test_a_cancelled_appointment_cannot_be_confirmed_again_from_an_old_link(): void
    {
        $appointment = $this->appointment(['nimbus_status' => 'cancelled', 'cancelled_at' => now()]);
        $link = $this->shortlink($appointment, 'confirm');

        $this->get(route('shortlink.handle', $link->token))
            ->assertOk()
            ->assertSee('Esta sesión ya está cancelada')
            ->assertSee('contacta directamente con tu psicóloga para agendar una nueva cita')
            ->assertSee('la hora podría haberse ocupado');

        $this->assertSame('cancelled', $appointment->fresh()->nimbus_status);
    }

    private function appointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'google_event_id' => 'event-' . uniqid(),
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
