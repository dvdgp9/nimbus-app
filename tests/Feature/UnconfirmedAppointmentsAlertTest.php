<?php

namespace Tests\Feature;

use App\Mail\UnconfirmedAppointments;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * N3: the professional must learn, a day before the session, which patients
 * never answered the reminder — with a WhatsApp button per patient.
 */
class UnconfirmedAppointmentsAlertTest extends TestCase
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
            $table->boolean('excluded_by_weekend_preference')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('unconfirmed_alert_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
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
    }

    public function test_it_alerts_about_a_session_the_patient_never_answered(): void
    {
        Mail::fake();
        $appointment = $this->awaitingAppointment('Ana Ruiz', '+34600111222');

        $this->artisan('nimbus:notify-unconfirmed')->assertSuccessful();

        Mail::assertSent(UnconfirmedAppointments::class, fn ($mail) => $mail->hasTo('psicologa@ejemplo.com'));
        $this->assertNotNull($appointment->fresh()->unconfirmed_alert_sent_at);
    }

    public function test_confirmed_and_cancelled_sessions_are_left_alone(): void
    {
        Mail::fake();
        $this->awaitingAppointment('Ana Ruiz', '+34600111222')
            ->update(['nimbus_status' => 'confirmed', 'confirmed_at' => now()]);
        $this->awaitingAppointment('Luis Gil', '+34600111333')
            ->update(['nimbus_status' => 'cancelled', 'cancelled_at' => now()]);

        $this->artisan('nimbus:notify-unconfirmed')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_a_session_whose_reminder_never_went_out_is_not_included(): void
    {
        Mail::fake();
        $this->awaitingAppointment('Ana Ruiz', '+34600111222')
            ->update(['nimbus_status' => 'pending', 'reminder_sent_at' => null]);

        $this->artisan('nimbus:notify-unconfirmed')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_a_session_further_out_than_the_window_is_not_included(): void
    {
        Mail::fake();
        $this->awaitingAppointment('Ana Ruiz', '+34600111222', now()->addHours(40));

        $this->artisan('nimbus:notify-unconfirmed')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_a_session_excluded_by_the_weekend_preference_is_not_included(): void
    {
        Mail::fake();
        $this->awaitingAppointment('Ana Ruiz', '+34600111222')
            ->update(['excluded_by_weekend_preference' => true]);

        $this->artisan('nimbus:notify-unconfirmed')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_two_silent_patients_produce_a_single_digest(): void
    {
        Mail::fake();
        $this->awaitingAppointment('Ana Ruiz', '+34600111222');
        $this->awaitingAppointment('Luis Gil', '+34600111333');

        $this->artisan('nimbus:notify-unconfirmed')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(UnconfirmedAppointments::class, fn ($mail) => $mail->sessions->count() === 2);
    }

    public function test_it_does_not_alert_twice(): void
    {
        Mail::fake();
        $this->awaitingAppointment('Ana Ruiz', '+34600111222');

        $this->artisan('nimbus:notify-unconfirmed')->assertSuccessful();
        $this->artisan('nimbus:notify-unconfirmed')->assertSuccessful();

        Mail::assertSentCount(1);
    }

    public function test_a_dry_run_neither_sends_nor_marks(): void
    {
        Mail::fake();
        $appointment = $this->awaitingAppointment('Ana Ruiz', '+34600111222');

        $this->artisan('nimbus:notify-unconfirmed --dry-run')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($appointment->fresh()->unconfirmed_alert_sent_at);
    }

    public function test_the_email_carries_a_ready_to_send_whatsapp_button(): void
    {
        $appointment = $this->awaitingAppointment('Ana Ruiz', '+34 600 111 222');

        $html = (new UnconfirmedAppointments(collect([$appointment->fresh()]), 24))->render();

        $this->assertStringContainsString('Ana Ruiz', $html);
        $this->assertStringContainsString('Escribir por WhatsApp', $html);
        // Digits only, no plus sign, and the message already written.
        $this->assertStringContainsString('https://wa.me/34600111222?text=', $html);
        $this->assertStringContainsString('%C2%A1Hola+Ana%21', $html);
    }

    public function test_a_patient_without_a_phone_is_still_listed_without_a_button(): void
    {
        $appointment = $this->awaitingAppointment('Ana Ruiz', null);

        $html = (new UnconfirmedAppointments(collect([$appointment->fresh()]), 24))->render();

        $this->assertStringContainsString('Ana Ruiz', $html);
        $this->assertStringNotContainsString('Escribir por WhatsApp', $html);
        $this->assertStringContainsString('no tiene teléfono guardado', $html);
    }

    public function test_the_subject_counts_the_sessions(): void
    {
        $one = $this->awaitingAppointment('Ana Ruiz', '+34600111222');
        $two = $this->awaitingAppointment('Luis Gil', '+34600111333');

        $this->assertSame(
            '🔔 1 sesión sin confirmar para mañana',
            (new UnconfirmedAppointments(collect([$one]), 24))->envelope()->subject,
        );
        $this->assertSame(
            '🔔 2 sesiones sin confirmar para mañana',
            (new UnconfirmedAppointments(collect([$one, $two]), 24))->envelope()->subject,
        );
    }

    protected function awaitingAppointment(string $patientName, ?string $phone, ?\DateTimeInterface $startAt = null): Appointment
    {
        $patient = Patient::create([
            'user_id' => 1,
            'code' => 'P'.uniqid(),
            'name' => $patientName,
            'phone' => $phone,
        ]);

        $startAt = $startAt ?: now()->addHours(20);

        return Appointment::create([
            'google_event_id' => 'evt-'.uniqid(),
            'calendar_id' => 'cal-work',
            'summary' => $patient->code.' - Sesión',
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify('+55 minutes'),
            'timezone' => 'Europe/Madrid',
            'patient_id' => $patient->id,
            'nimbus_status' => 'reminder_sent',
            'reminder_sent_at' => now()->subDay(),
        ]);
    }
}
