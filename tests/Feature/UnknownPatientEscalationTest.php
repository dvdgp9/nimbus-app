<?php

namespace Tests\Feature;

use App\Mail\UnknownPatientCode;
use App\Models\Appointment;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * N1: the unknown patient code notice must insist once the session is close,
 * instead of being a single fire-and-forget email.
 */
class UnknownPatientEscalationTest extends TestCase
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
            $table->string('timezone')->default('Europe/Madrid');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('nimbus_status')->default('pending');
            $table->boolean('unknown_patient_notified')->default(false);
            $table->timestamp('unknown_patient_escalated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('connected_calendars', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('calendar_id');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Psicóloga',
            'email' => 'psicologa@ejemplo.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('connected_calendars')->insert([
            'user_id' => 1,
            'calendar_id' => 'cal-work',
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_escalates_an_unresolved_code_inside_the_window(): void
    {
        Mail::fake();
        $appointment = $this->appointment(now()->addHours(71));

        $this->assertTrue(app(NotificationService::class)->escalateUnknownPatientCode($appointment, 'A123'));

        Mail::assertSent(UnknownPatientCode::class, function (UnknownPatientCode $mail): bool {
            return $mail->escalated === true
                && $mail->hasTo('psicologa@ejemplo.com');
        });
        $this->assertNotNull($appointment->fresh()->unknown_patient_escalated_at);
    }

    public function test_it_does_not_escalate_before_the_window(): void
    {
        Mail::fake();
        $appointment = $this->appointment(now()->addHours(80));

        $this->assertFalse(app(NotificationService::class)->escalateUnknownPatientCode($appointment, 'A123'));

        Mail::assertNothingSent();
        $this->assertNull($appointment->fresh()->unknown_patient_escalated_at);
    }

    public function test_it_escalates_only_once(): void
    {
        Mail::fake();
        $appointment = $this->appointment(now()->addHours(71));
        $service = app(NotificationService::class);

        $this->assertTrue($service->escalateUnknownPatientCode($appointment, 'A123'));
        $this->assertFalse($service->escalateUnknownPatientCode($appointment->fresh(), 'A123'));

        Mail::assertSentCount(1);
    }

    public function test_it_does_not_escalate_once_the_patient_exists(): void
    {
        Mail::fake();
        $appointment = $this->appointment(now()->addHours(71));
        $appointment->update(['patient_id' => 42]);

        $this->assertFalse(app(NotificationService::class)->escalateUnknownPatientCode($appointment, 'A123'));

        Mail::assertNothingSent();
    }

    public function test_it_does_not_escalate_without_a_first_notice(): void
    {
        Mail::fake();
        $appointment = $this->appointment(now()->addHours(71));
        $appointment->update(['unknown_patient_notified' => false]);

        $this->assertFalse(app(NotificationService::class)->escalateUnknownPatientCode($appointment, 'A123'));

        Mail::assertNothingSent();
    }

    public function test_it_does_not_escalate_a_past_session(): void
    {
        Mail::fake();
        $appointment = $this->appointment(now()->subHour());

        $this->assertFalse(app(NotificationService::class)->escalateUnknownPatientCode($appointment, 'A123'));

        Mail::assertNothingSent();
    }

    public function test_a_failed_send_leaves_the_appointment_ready_for_the_next_sync(): void
    {
        $appointment = $this->appointment(now()->addHours(71));

        Mail::shouldReceive('to')->once()->andThrow(new \Exception('SMTP down'));

        $this->assertFalse(app(NotificationService::class)->escalateUnknownPatientCode($appointment, 'A123'));
        $this->assertNull($appointment->fresh()->unknown_patient_escalated_at);
    }

    public function test_both_variants_render_with_their_own_copy(): void
    {
        $appointment = $this->appointment(now()->addHours(71));

        $first = (new UnknownPatientCode($appointment, 'A123'))->render();
        $urgent = (new UnknownPatientCode($appointment, 'A123', escalated: true))->render();

        $this->assertStringContainsString('código de paciente que no existe', $first);
        $this->assertStringNotContainsString('sigue sin paciente registrado', $first);

        $this->assertStringContainsString('sigue sin paciente registrado', $urgent);
        $this->assertStringContainsString('72', $urgent);
        $this->assertStringContainsString('A123', $urgent);
    }

    public function test_the_urgent_subject_names_the_session_date(): void
    {
        $appointment = $this->appointment(now()->addHours(71));

        $subject = (new UnknownPatientCode($appointment, 'A123', escalated: true))->envelope()->subject;

        $this->assertStringContainsString('Urgente', $subject);
        $this->assertStringContainsString($appointment->formatted_date, $subject);
    }

    protected function appointment(\DateTimeInterface $startAt): Appointment
    {
        return Appointment::create([
            'google_event_id' => 'evt-'.uniqid(),
            'calendar_id' => 'cal-work',
            'summary' => 'A123 - Sesión',
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify('+55 minutes'),
            'timezone' => 'Europe/Madrid',
            'nimbus_status' => 'pending',
            'unknown_patient_notified' => true,
        ]);
    }
}
