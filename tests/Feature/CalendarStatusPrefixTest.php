<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Shortlink;
use App\Services\GoogleCalendarService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Feedback 2026-09-15: confirmed sessions carry "OK - " in Google Calendar, but a
 * cancellation or a request to change the date left no trace there. Now a
 * cancellation writes "X - " and the "Cambiar cita" button writes "? - ".
 */
class CalendarStatusPrefixTest extends TestCase
{
    private int $userId;

    private int $patientId;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('include_weekends')->default(true);
            $table->timestamps();
        });

        Schema::create('google_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('account_email');
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('google_event_id')->unique();
            $table->string('calendar_id');
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('timezone')->default('Europe/Madrid');
            $table->string('hangout_link')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('message_code')->nullable();
            $table->string('google_color_id')->nullable();
            // Same ENUM as production: every status written here must exist there.
            $table->enum('nimbus_status', [
                'pending', 'reminder_sent', 'confirmed', 'cancelled', 'rescheduled', 'completed',
            ])->default('pending');
            $table->boolean('excluded_by_weekend_preference')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('unconfirmed_alert_sent_at')->nullable();
            $table->timestamp('rescheduled_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('raw_payload')->nullable();
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

        $this->userId = DB::table('users')->insertGetId([
            'name' => 'Psicóloga',
            'email' => 'professional@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('google_tokens')->insert([
            'user_id' => $this->userId,
            'account_email' => 'professional@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patientId = DB::table('patients')->insertGetId([
            'user_id' => $this->userId,
            'code' => 'AB12',
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'phone' => '+34600000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // --- Cancelling ---

    public function test_cancelling_marks_the_event_with_x(): void
    {
        $this->expectTitlePrefix('X - ');
        $appointment = $this->appointment(['nimbus_status' => 'reminder_sent']);

        $this->post(route('shortlink.execute', $this->shortlink($appointment, 'cancel')->token))
            ->assertOk()
            ->assertSee('Cita cancelada');

        $this->assertSame('cancelled', $appointment->fresh()->nimbus_status);
    }

    public function test_cancelling_a_confirmed_session_swaps_ok_for_x(): void
    {
        $this->expectTitlePrefix('X - ')->shouldNotReceive('removeEventTitlePrefix');
        $appointment = $this->appointment([
            'summary' => 'OK - AB12 - Sesión',
            'nimbus_status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->post(route('shortlink.execute', $this->shortlink($appointment, 'cancel')->token))
            ->assertOk();

        $this->assertSame('cancelled', $appointment->fresh()->nimbus_status);
    }

    // --- Asking to change the date ---

    public function test_the_manage_page_sends_cambiar_cita_as_an_explicit_post(): void
    {
        $this->expectNoTitleChange();
        $appointment = $this->appointment(['nimbus_status' => 'reminder_sent']);
        $confirm = $this->shortlink($appointment, 'confirm');
        $reschedule = $this->shortlink($appointment, 'reschedule');

        $this->get(route('shortlink.handle', $confirm->token))
            ->assertOk()
            ->assertSee('Cambiar cita')
            ->assertSee('action="'.route('shortlink.execute', $reschedule->token).'"', false)
            ->assertDontSee('href="'.route('shortlink.handle', $reschedule->token).'"', false);
    }

    public function test_opening_the_reschedule_link_only_redirects_to_whatsapp(): void
    {
        // Email link scanners open every URL: a GET must never mark the event.
        $this->expectNoTitleChange();
        $appointment = $this->appointment(['nimbus_status' => 'reminder_sent']);

        $this->get(route('shortlink.handle', $this->shortlink($appointment, 'reschedule')->token))
            ->assertRedirectContains('https://wa.me/');

        $this->assertSame('reminder_sent', $appointment->fresh()->nimbus_status);
    }

    public function test_pressing_cambiar_cita_marks_the_event_with_a_question_mark(): void
    {
        $this->expectTitlePrefix('? - ');
        $appointment = $this->appointment(['nimbus_status' => 'reminder_sent']);

        $this->post(route('shortlink.execute', $this->shortlink($appointment, 'reschedule')->token))
            ->assertRedirectContains('https://wa.me/');

        $this->assertSame('rescheduled', $appointment->fresh()->nimbus_status);
    }

    public function test_pressing_cambiar_cita_on_a_cancelled_session_keeps_the_x(): void
    {
        $this->expectNoTitleChange();
        $appointment = $this->appointment([
            'summary' => 'X - AB12 - Sesión',
            'nimbus_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->post(route('shortlink.execute', $this->shortlink($appointment, 'reschedule')->token))
            ->assertRedirectContains('https://wa.me/');

        $this->assertSame('cancelled', $appointment->fresh()->nimbus_status);
    }

    public function test_a_patient_who_asked_to_change_can_still_confirm(): void
    {
        $this->expectTitlePrefix('OK - ');
        $appointment = $this->appointment([
            'summary' => '? - AB12 - Sesión',
            'nimbus_status' => 'rescheduled',
        ]);
        $confirm = $this->shortlink($appointment, 'confirm');

        $this->get(route('shortlink.handle', $confirm->token))
            ->assertOk()
            ->assertSee('Confirmar asistencia');
        $this->post(route('shortlink.execute', $confirm->token))
            ->assertOk()
            ->assertSee('Cita confirmada');

        $this->assertSame('confirmed', $appointment->fresh()->nimbus_status);
    }

    // --- Sync ---

    public function test_the_sync_keeps_the_patient_behind_x_and_question_mark_titles(): void
    {
        $start = $this->nextWeekday()->setTime(10, 0);

        (new GoogleCalendarService)->syncAppointments([
            $this->googleEvent('evt-x', 'X - AB12 - Sesión', $start),
            $this->googleEvent('evt-q', '? - AB12', $start->copy()->addHours(2)),
        ], $this->userId, ['cal-work'], 720);

        $cancelled = Appointment::where('google_event_id', 'evt-x')->firstOrFail();
        $wantsChange = Appointment::where('google_event_id', 'evt-q')->firstOrFail();
        $this->assertSame($this->patientId, $cancelled->patient_id);
        $this->assertSame($this->patientId, $wantsChange->patient_id);
        // "? - AB12" must read like "AB12": no message code, so the default template applies.
        $this->assertNull($wantsChange->message_code);
    }

    public function test_moving_a_session_that_asked_to_change_clears_the_question_mark(): void
    {
        $this->mock(GoogleCalendarService::class)
            ->shouldReceive('removeEventTitlePrefix')
            ->once()
            ->with('cal-work', 'evt-1', '? - ', 'professional@example.com', $this->userId)
            ->andReturn(true);
        $oldStart = $this->nextWeekday()->setTime(10, 0);
        $appointment = $this->appointment([
            'summary' => '? - AB12 - Sesión',
            'start_at' => $oldStart,
            'end_at' => $oldStart->copy()->addHour(),
            'nimbus_status' => 'rescheduled',
            'reminder_sent_at' => now()->subDay(),
        ]);

        $this->sync('? - AB12 - Sesión', $oldStart->copy()->addWeek());

        $appointment->refresh();
        $this->assertSame('pending', $appointment->nimbus_status);
        $this->assertNull($appointment->reminder_sent_at);
        $this->assertSame($this->patientId, $appointment->patient_id);
    }

    public function test_parking_a_cancelled_session_on_sunday_keeps_the_x(): void
    {
        $this->mock(GoogleCalendarService::class)->shouldNotReceive('removeEventTitlePrefix');
        $oldStart = $this->nextWeekday()->setTime(10, 0);
        $appointment = $this->appointment([
            'summary' => 'X - AB12 - Sesión',
            'start_at' => $oldStart,
            'end_at' => $oldStart->copy()->addHour(),
            'nimbus_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->sync('X - AB12 - Sesión', $oldStart->copy()->endOfWeek(Carbon::SUNDAY)->setTime(10, 0));

        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->nimbus_status);
        $this->assertSame($this->patientId, $appointment->patient_id);
    }

    // --- Helpers ---

    private function expectTitlePrefix(string $prefix): MockInterface
    {
        $calendar = $this->mock(GoogleCalendarService::class);
        $calendar->shouldReceive('updateEventTitle')
            ->once()
            ->with('cal-work', 'evt-1', $prefix, 'professional@example.com', $this->userId)
            ->andReturn(true);

        return $calendar;
    }

    private function expectNoTitleChange(): void
    {
        $this->mock(GoogleCalendarService::class)->shouldNotReceive('updateEventTitle');
    }

    private function sync(string $summary, Carbon $start): void
    {
        (new GoogleCalendarService)->syncAppointments(
            [$this->googleEvent('evt-1', $summary, $start)],
            $this->userId,
            ['cal-work'],
            720,
        );
    }

    private function nextWeekday(): Carbon
    {
        return now()->addDays(3)->isWeekend()
            ? now()->next(Carbon::TUESDAY)
            : now()->addDays(3);
    }

    private function appointment(array $overrides = []): Appointment
    {
        $start = $this->nextWeekday()->setTime(10, 0);

        return Appointment::create(array_merge([
            'google_event_id' => 'evt-1',
            'calendar_id' => 'cal-work',
            'summary' => 'AB12 - Sesión',
            'start_at' => $start,
            'end_at' => $start->copy()->addHour(),
            'timezone' => 'Europe/Madrid',
            'patient_id' => $this->patientId,
            'nimbus_status' => 'pending',
        ], $overrides));
    }

    private function shortlink(Appointment $appointment, string $action): Shortlink
    {
        return Shortlink::createForAppointment($appointment, $action);
    }

    private function googleEvent(string $eventId, string $summary, Carbon $start): array
    {
        return [
            'google_event_id' => $eventId,
            'calendar_id' => 'cal-work',
            'summary' => $summary,
            'description' => null,
            'start_at' => $start->toRfc3339String(),
            'end_at' => $start->copy()->addHour()->toRfc3339String(),
            'timezone' => 'Europe/Madrid',
            'hangout_link' => null,
            'color_id' => null,
            'raw' => new \stdClass,
        ];
    }
}
