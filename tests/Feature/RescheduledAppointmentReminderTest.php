<?php

namespace Tests\Feature;

use App\Mail\TemplatedReminder;
use App\Models\Appointment;
use App\Models\MessageTemplate;
use App\Services\AcumbamailService;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feedback 2026-09-16: a patient receives the reminder, asks to change the
 * session, the professional drags it to next week in Google Calendar and no
 * reminder arrives for the new date. The sync only reset some statuses, and the
 * sender treated the email of the old date as "already sent" for the new one.
 */
class RescheduledAppointmentReminderTest extends TestCase
{
    private int $userId;

    private int $patientId;

    protected function setUp(): void
    {
        parent::setUp();

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
            $table->boolean('consent_email')->default(false);
            $table->boolean('consent_sms')->default(false);
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
            $table->string('nimbus_status')->default('pending');
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

        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('communications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('channel');
            $table->string('type');
            $table->string('recipient');
            $table->text('message_body');
            $table->string('subject')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->boolean('consent_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('shortlinks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('token')->unique();
            $table->string('action');
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
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
            'consent_email' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // --- Sync: moving the event in Google Calendar ---

    public function test_moving_a_reminded_appointment_makes_it_eligible_for_a_new_reminder(): void
    {
        $this->mock(GoogleCalendarService::class)->shouldNotReceive('removeEventTitlePrefix');
        $oldStart = $this->nextWeekday()->setTime(10, 0);
        $id = $this->appointment('AB12 - Sesión', $oldStart, [
            'nimbus_status' => 'reminder_sent',
            'reminder_sent_at' => now()->subHour(),
            'unconfirmed_alert_sent_at' => now()->subMinutes(30),
        ]);

        $this->sync('AB12 - Sesión', $oldStart->copy()->addWeek());

        $appointment = Appointment::findOrFail($id);
        $this->assertSame('pending', $appointment->nimbus_status);
        $this->assertNull($appointment->reminder_sent_at);
        $this->assertNull($appointment->unconfirmed_alert_sent_at);
        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertTrue($appointment->canSendReminder());
    }

    public function test_moving_a_confirmed_appointment_asks_for_confirmation_again(): void
    {
        $this->mock(GoogleCalendarService::class)
            ->shouldReceive('removeEventTitlePrefix')
            ->once()
            ->with('cal-work', 'evt-1', 'OK - ', 'professional@example.com', $this->userId)
            ->andReturn(true);
        $oldStart = $this->nextWeekday()->setTime(10, 0);
        $id = $this->appointment('OK - AB12 - Sesión', $oldStart, [
            'nimbus_status' => 'confirmed',
            'reminder_sent_at' => now()->subDay(),
            'confirmed_at' => now()->subHours(20),
        ]);

        $this->sync('OK - AB12 - Sesión', $oldStart->copy()->addWeek());

        $appointment = Appointment::findOrFail($id);
        $this->assertSame('pending', $appointment->nimbus_status);
        $this->assertNull($appointment->reminder_sent_at);
        $this->assertNull($appointment->confirmed_at);
        $this->assertSame($this->patientId, $appointment->patient_id);
        $this->assertTrue($appointment->canSendReminder());
    }

    public function test_moving_a_cancelled_appointment_never_revives_it(): void
    {
        $this->mock(GoogleCalendarService::class)->shouldNotReceive('removeEventTitlePrefix');
        $oldStart = $this->nextWeekday()->setTime(10, 0);
        $cancelledId = $this->appointment('AB12 - Sesión', $oldStart, [
            'nimbus_status' => 'cancelled',
            'cancelled_at' => now()->subHour(),
        ]);
        $acknowledgedId = $this->appointment('AB12 - Sesión', $oldStart, [
            'google_event_id' => 'evt-2',
            'nimbus_status' => 'cancelled_acknowledged',
            'cancelled_at' => now()->subHour(),
        ]);

        $sunday = $oldStart->copy()->endOfWeek(Carbon::SUNDAY)->setTime(10, 0);
        (new GoogleCalendarService)->syncAppointments([
            $this->googleEvent('evt-1', 'AB12 - Sesión', $sunday),
            $this->googleEvent('evt-2', 'AB12 - Sesión', $sunday),
        ], $this->userId, ['cal-work'], 720);

        foreach ([$cancelledId => 'cancelled', $acknowledgedId => 'cancelled_acknowledged'] as $id => $status) {
            $appointment = Appointment::findOrFail($id);
            $this->assertSame($status, $appointment->nimbus_status);
            $this->assertNotNull($appointment->cancelled_at);
            $this->assertFalse($appointment->canSendReminder());
        }
    }

    public function test_a_sync_without_a_date_change_keeps_the_reminder_state(): void
    {
        $this->mock(GoogleCalendarService::class)->shouldNotReceive('removeEventTitlePrefix');
        $start = $this->nextWeekday()->setTime(10, 0);
        $id = $this->appointment('OK - AB12 - Sesión', $start, [
            'nimbus_status' => 'confirmed',
            'reminder_sent_at' => now()->subDay(),
            'confirmed_at' => now()->subHours(20),
        ]);

        // Google sends RFC 3339 with offset; the DB holds "Y-m-d H:i:s".
        $this->sync('OK - AB12 - Sesión', $start);

        $appointment = Appointment::findOrFail($id);
        $this->assertSame('confirmed', $appointment->nimbus_status);
        $this->assertNotNull($appointment->reminder_sent_at);
        $this->assertNotNull($appointment->confirmed_at);
        $this->assertNull($appointment->rescheduled_at);
    }

    // --- Sender: the old date's delivery must not count for the new date ---

    public function test_the_reminder_for_the_old_date_does_not_block_the_one_for_the_new_date(): void
    {
        Mail::fake();
        $this->defaultTemplate('email');
        $appointment = $this->rescheduledAppointment(rescheduledAt: now()->subMinutes(20));
        $this->communication($appointment, 'email', 'sent', now()->subDays(2));

        $this->assertTrue(app(NotificationService::class)->sendReminder($appointment));

        Mail::assertSent(TemplatedReminder::class, 1);
        $this->assertSame(2, DB::table('communications')->where('channel', 'email')->where('status', 'sent')->count());
        $this->assertSame('reminder_sent', $appointment->fresh()->nimbus_status);
    }

    public function test_a_partial_delivery_after_the_move_is_still_retried_without_duplicates(): void
    {
        Mail::fake();
        $this->defaultTemplate('email');
        $this->defaultTemplate('sms');
        DB::table('patients')->where('id', $this->patientId)->update(['consent_sms' => true]);
        $this->mock(AcumbamailService::class)->shouldReceive('sendSMS')->once()->andReturn('sms-1');

        $appointment = $this->rescheduledAppointment(rescheduledAt: now()->subHour());
        $this->communication($appointment, 'email', 'sent', now()->subMinutes(15));

        $this->assertTrue(app(NotificationService::class)->sendReminder($appointment));

        Mail::assertNothingSent();
        $this->assertSame('reminder_sent', $appointment->fresh()->nimbus_status);
    }

    public function test_failures_before_the_move_do_not_count_towards_the_retry_cap(): void
    {
        Mail::fake();
        $this->defaultTemplate('email');
        $appointment = $this->rescheduledAppointment(rescheduledAt: now()->subMinutes(10));
        foreach (range(1, NotificationService::MAX_DELIVERY_ATTEMPTS) as $minutesAgo) {
            $this->communication($appointment, 'email', 'failed', now()->subMinutes(30 + $minutesAgo));
        }

        $this->assertTrue(app(NotificationService::class)->sendReminder($appointment));

        Mail::assertSent(TemplatedReminder::class, 1);
    }

    // --- Helpers ---

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

    private function appointment(string $summary, Carbon $start, array $overrides = []): int
    {
        return DB::table('appointments')->insertGetId(array_merge([
            'google_event_id' => 'evt-1',
            'calendar_id' => 'cal-work',
            'summary' => $summary,
            'start_at' => $start,
            'end_at' => $start->copy()->addHour(),
            'timezone' => 'Europe/Madrid',
            'patient_id' => $this->patientId,
            'nimbus_status' => 'pending',
            'created_at' => now()->subDays(10),
            'updated_at' => now(),
        ], $overrides));
    }

    private function rescheduledAppointment(Carbon $rescheduledAt): Appointment
    {
        $id = $this->appointment('AB12 - Sesión', now()->addDay()->setTime(10, 0), [
            'rescheduled_at' => $rescheduledAt,
        ]);

        return Appointment::findOrFail($id);
    }

    private function communication(Appointment $appointment, string $channel, string $status, Carbon $at): void
    {
        DB::table('communications')->insert([
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patientId,
            'channel' => $channel,
            'type' => 'reminder',
            'recipient' => $channel === 'email' ? 'ana@example.com' : '+34600000000',
            'message_body' => 'Recordatorio de la fecha anterior',
            'status' => $status,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function defaultTemplate(string $channel): void
    {
        MessageTemplate::create([
            'user_id' => $this->userId,
            'name' => "Predeterminada {$channel}",
            'channel' => $channel,
            'subject' => $channel === 'email' ? 'Recordatorio' : null,
            'body' => 'Contenido',
            'is_default' => true,
        ]);
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
