<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WeekendCalendarPreferenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('include_weekends')->default(true);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('google_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('account_email');
            $table->timestamps();
        });

        Schema::create('connected_calendars', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('account_email');
            $table->string('calendar_id');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code')->nullable();
            $table->string('name');
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
            $table->timestamp('last_synced_at')->nullable();
            $table->text('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function test_disabling_weekends_marks_existing_weekend_appointments_without_deleting_them(): void
    {
        $user = $this->user(includeWeekends: true);
        $this->calendar($user);

        $saturday = now()->next(Carbon::SATURDAY)->setTime(10, 0);
        $monday = $saturday->copy()->next(Carbon::MONDAY)->setTime(10, 0);

        $weekendId = $this->appointment('evt-weekend', 'cal-work', $saturday);
        $weekdayId = $this->appointment('evt-weekday', 'cal-work', $monday);
        $otherCalendarId = $this->appointment('evt-other', 'cal-other', $saturday);

        $response = $this->actingAs($user)->post(route('calendars.store'), [
            'account' => 'professional@example.com',
            'calendars' => ['cal-work'],
        ]);

        $response->assertRedirect(route('events.index', ['account' => 'professional@example.com']));

        $this->assertFalse($user->fresh()->include_weekends);
        $this->assertDatabaseHas('appointments', [
            'id' => $weekendId,
            'excluded_by_weekend_preference' => true,
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $weekdayId,
            'excluded_by_weekend_preference' => false,
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $otherCalendarId,
            'excluded_by_weekend_preference' => false,
        ]);
        $this->assertSame(3, DB::table('appointments')->count(), 'The preference must never delete appointment history.');
    }

    public function test_enabling_weekends_restores_existing_weekend_appointments(): void
    {
        $user = $this->user(includeWeekends: false);
        $this->calendar($user);
        $saturday = now()->next(Carbon::SATURDAY)->setTime(10, 0);
        $weekendId = $this->appointment('evt-weekend', 'cal-work', $saturday, excluded: true);

        $this->actingAs($user)->post(route('calendars.store'), [
            'account' => 'professional@example.com',
            'calendars' => ['cal-work'],
            'include_weekends' => '1',
        ])->assertRedirect();

        $this->assertTrue($user->fresh()->include_weekends);
        $this->assertDatabaseHas('appointments', [
            'id' => $weekendId,
            'excluded_by_weekend_preference' => false,
        ]);
    }

    public function test_calendar_sync_marks_weekend_events_from_an_opted_out_user_as_excluded(): void
    {
        $user = $this->user(includeWeekends: false);
        $saturday = now()->next(Carbon::SATURDAY)->setTime(10, 0);
        $monday = $saturday->copy()->next(Carbon::MONDAY)->setTime(10, 0);

        app(GoogleCalendarService::class)->syncAppointments([
            $this->googleEvent('evt-weekend', $saturday),
            $this->googleEvent('evt-weekday', $monday),
        ], $user->id, ['cal-work'], 720);

        $this->assertDatabaseHas('appointments', [
            'google_event_id' => 'evt-weekend',
            'excluded_by_weekend_preference' => true,
        ]);
        $this->assertDatabaseHas('appointments', [
            'google_event_id' => 'evt-weekday',
            'excluded_by_weekend_preference' => false,
        ]);
    }

    public function test_excluded_appointment_cannot_send_a_reminder(): void
    {
        $saturday = now()->next(Carbon::SATURDAY)->setTime(10, 0);
        $id = $this->appointment('evt-weekend', 'cal-work', $saturday, excluded: true);

        $appointment = Appointment::findOrFail($id);

        $this->assertFalse($appointment->canSendReminder());
        $this->assertStringContainsString(
            'excluded_by_weekend_preference',
            Appointment::query()->needsReminder()->toSql(),
        );
    }

    public function test_calendar_settings_show_the_weekend_control(): void
    {
        $user = $this->user(includeWeekends: false);
        $this->calendar($user);

        $this->mock(GoogleCalendarService::class, function ($mock): void {
            $mock->shouldReceive('listCalendars')
                ->once()
                ->andReturn([[
                    'id' => 'cal-work',
                    'summary' => 'Consultation calendar',
                    'primary' => true,
                    'timeZone' => 'Europe/Madrid',
                ]]);
        });

        $this->actingAs($user)
            ->get(route('calendars.index'))
            ->assertOk()
            ->assertSee('Incluir sábados y domingos')
            ->assertSee('no aparecerán ni generarán recordatorios o alertas');
    }

    public function test_weekend_preference_migrations_are_reversible(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('include_weekends');
        });
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('excluded_by_weekend_preference');
        });

        $userMigration = require database_path('migrations/2026_08_29_100000_add_include_weekends_to_users_table.php');
        $appointmentMigration = require database_path('migrations/2026_08_29_110000_add_weekend_exclusion_to_appointments_table.php');

        $userMigration->up();
        $appointmentMigration->up();

        $this->assertTrue(Schema::hasColumn('users', 'include_weekends'));
        $this->assertTrue(Schema::hasColumn('appointments', 'excluded_by_weekend_preference'));

        $appointmentMigration->down();
        $userMigration->down();

        $this->assertFalse(Schema::hasColumn('users', 'include_weekends'));
        $this->assertFalse(Schema::hasColumn('appointments', 'excluded_by_weekend_preference'));
    }

    protected function user(bool $includeWeekends): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Professional',
            'email' => 'professional@example.com',
            'password' => bcrypt('secret'),
            'include_weekends' => $includeWeekends,
            'onboarding_completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('google_tokens')->insert([
            'user_id' => $id,
            'account_email' => 'professional@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    protected function calendar(User $user): void
    {
        DB::table('connected_calendars')->insert([
            'user_id' => $user->id,
            'account_email' => 'professional@example.com',
            'calendar_id' => 'cal-work',
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function appointment(
        string $eventId,
        string $calendarId,
        Carbon $start,
        bool $excluded = false,
    ): int {
        return DB::table('appointments')->insertGetId([
            'google_event_id' => $eventId,
            'calendar_id' => $calendarId,
            'summary' => 'Session',
            'start_at' => $start,
            'end_at' => $start->copy()->addHour(),
            'timezone' => 'Europe/Madrid',
            'nimbus_status' => 'pending',
            'excluded_by_weekend_preference' => $excluded,
            'created_at' => now()->subDays(2),
            'updated_at' => now(),
        ]);
    }

    protected function googleEvent(string $eventId, Carbon $start): array
    {
        return [
            'google_event_id' => $eventId,
            'calendar_id' => 'cal-work',
            'summary' => 'Session',
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
