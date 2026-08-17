<?php

namespace Tests\Feature;

use App\Console\Commands\SyncCalendars;
use App\Mail\UnknownPatientCode;
use App\Models\Appointment;
use App\Services\FirstSessionService;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use App\Services\YellowAppointmentReviewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * N2: appointments whose title has no readable patient code used to produce
 * nothing but a log line. They must warn the professional too, without turning
 * every personal event in the work calendar into an email.
 */
class UnrecognizedAppointmentNoticeTest extends TestCase
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
            ['user_id' => 1, 'calendar_id' => 'cal-work', 'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 1, 'calendar_id' => 'cal-off', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_it_warns_about_a_title_with_no_readable_code(): void
    {
        $appointment = $this->appointment('🗓 Sesión con Ana', now()->addDays(3));

        $notified = $this->runSync();

        $this->assertSame([[$appointment->id, null]], $notified);
    }

    public function test_it_ignores_all_day_events(): void
    {
        $this->appointment('🏖 Vacaciones', now()->addDays(3), now()->addDays(4));

        $this->assertSame([], $this->runSync());
    }

    public function test_it_waits_until_an_uncoded_event_is_inside_the_window(): void
    {
        $this->appointment('🗓 Sesión con Ana', now()->addDays(20));

        $this->assertSame([], $this->runSync());
    }

    public function test_coded_appointments_keep_warning_beyond_that_window(): void
    {
        $appointment = $this->appointment('A123 - Sesión', now()->addDays(20));

        $this->assertSame([[$appointment->id, 'A123']], $this->runSync());
    }

    public function test_it_does_not_warn_twice(): void
    {
        $this->appointment('🗓 Sesión con Ana', now()->addDays(3))
            ->update(['unknown_patient_notified' => true]);

        $this->assertSame([], $this->runSync());
    }

    public function test_it_ignores_first_sessions_and_disabled_calendars(): void
    {
        $this->appointment('Primera sesión con Marta', now()->addDays(3));
        $this->appointment('🗓 Sesión con Ana', now()->addDays(3), null, 'cal-off');

        $this->assertSame([], $this->runSync());
    }

    public function test_it_ignores_appointments_that_already_have_a_patient(): void
    {
        $this->appointment('🗓 Sesión con Ana', now()->addDays(3))
            ->update(['patient_id' => 42]);

        $this->assertSame([], $this->runSync());
    }

    public function test_the_uncoded_email_explains_how_to_name_the_event(): void
    {
        $appointment = $this->appointment('🗓 Sesión con Ana', now()->addDays(3));
        $mail = new UnknownPatientCode($appointment, null);

        $html = $mail->render();

        $this->assertStringContainsString('no conseguimos identificar al paciente', $html);
        $this->assertStringContainsString('empiece por el código del paciente', $html);
        $this->assertStringContainsString('➕ Crear Paciente', $html);
        $this->assertStringContainsString('Cita sin paciente identificable', $mail->envelope()->subject);
    }

    public function test_the_urgent_variant_also_works_without_a_code(): void
    {
        $appointment = $this->appointment('🗓 Sesión con Ana', now()->addHours(71));

        $mail = new UnknownPatientCode($appointment, null, escalated: true);

        $this->assertStringContainsString('seguimos sin poder identificar al paciente', $mail->render());
        $this->assertStringContainsString('Urgente', $mail->envelope()->subject);
    }

    /**
     * Runs the real selection and guard logic of the sync command against the
     * database, with the mailing side replaced by a recorder.
     */
    protected function runSync(): array
    {
        $recorder = new class extends NotificationService
        {
            public array $notified = [];

            public function notifyUnknownPatientCode(Appointment $appointment, ?string $patientCode): bool
            {
                $this->notified[] = [$appointment->id, $patientCode];

                return true;
            }
        };

        $command = new SyncCalendars(
            app(GoogleCalendarService::class),
            app(FirstSessionService::class),
            $recorder,
            app(YellowAppointmentReviewService::class),
        );

        $method = new \ReflectionMethod($command, 'processUnknownPatientCodes');
        $method->invoke($command, 1);

        return $recorder->notified;
    }

    protected function appointment(
        string $summary,
        \DateTimeInterface $startAt,
        ?\DateTimeInterface $endAt = null,
        string $calendarId = 'cal-work',
    ): Appointment {
        return Appointment::create([
            'google_event_id' => 'evt-'.uniqid(),
            'calendar_id' => $calendarId,
            'summary' => $summary,
            'start_at' => $startAt,
            'end_at' => $endAt ?: (clone $startAt)->modify('+55 minutes'),
            'timezone' => 'Europe/Madrid',
            'nimbus_status' => 'pending',
        ]);
    }
}
