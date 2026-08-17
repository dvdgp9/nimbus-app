<?php

namespace Tests\Unit;

use App\Mail\AppointmentReminder;
use App\Mail\TemplatedReminder;
use App\Models\Appointment;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\User;
use App\Services\AcumbamailService;
use App\Services\NotificationService;
use App\Services\ReminderTemplateResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReminderTemplateResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
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
            $table->string('summary');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('timezone')->default('Europe/Madrid');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('message_code')->nullable();
            $table->string('nimbus_status')->default('pending');
            $table->timestamp('reminder_sent_at')->nullable();
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
    }

    public function test_matching_code_selects_that_users_channel_template(): void
    {
        [$user, $appointment] = $this->appointmentForUser('BP');
        $this->template($user, 'Predeterminada', null, true);
        $coded = $this->template($user, 'Seguimiento BP', 'BP');

        $resolution = app(ReminderTemplateResolver::class)->resolve($appointment, 'email');

        $this->assertTrue($resolution->isReady());
        $this->assertSame('matched_code', $resolution->status);
        $this->assertTrue($coded->is($resolution->template));
    }

    public function test_unknown_code_is_blocked_instead_of_falling_back_to_default(): void
    {
        [$user, $appointment] = $this->appointmentForUser('NOEXISTE');
        $this->template($user, 'Predeterminada', null, true);

        $resolution = app(ReminderTemplateResolver::class)->resolve($appointment, 'email');

        $this->assertFalse($resolution->isReady());
        $this->assertSame('missing_code', $resolution->status);
        $this->assertNull($resolution->template);
        $this->assertStringContainsString('NOEXISTE', $resolution->message);
    }

    public function test_appointment_without_code_uses_the_users_default_template(): void
    {
        [$user, $appointment] = $this->appointmentForUser(null);
        $default = $this->template($user, 'Email principal', null, true);

        $resolution = app(ReminderTemplateResolver::class)->resolve($appointment, 'email');

        $this->assertTrue($resolution->isReady());
        $this->assertSame('default', $resolution->status);
        $this->assertTrue($default->is($resolution->template));
    }

    public function test_appointment_without_code_and_without_default_is_blocked(): void
    {
        [$user, $appointment] = $this->appointmentForUser(null);
        $this->template($user, 'No predeterminada', 'OTRA');

        $resolution = app(ReminderTemplateResolver::class)->resolve($appointment, 'email');

        $this->assertFalse($resolution->isReady());
        $this->assertSame('missing_default', $resolution->status);
        $this->assertNull($resolution->template);
    }

    public function test_template_resolution_is_isolated_between_users(): void
    {
        [, $appointment] = $this->appointmentForUser('BP');
        $otherUser = $this->user('other@example.com');
        $this->template($otherUser, 'Plantilla ajena', 'BP');

        $resolution = app(ReminderTemplateResolver::class)->resolve($appointment, 'email');

        $this->assertFalse($resolution->isReady());
        $this->assertSame('missing_code', $resolution->status);
    }

    public function test_dashboard_resolution_rejects_a_patient_from_another_user(): void
    {
        [$owner, $appointment] = $this->appointmentForUser(null);
        $this->template($owner, 'Email principal', null, true);
        $viewer = $this->user('viewer@example.com');

        $resolution = app(ReminderTemplateResolver::class)->resolve(
            $appointment,
            'email',
            $viewer->id
        );

        $this->assertFalse($resolution->isReady());
        $this->assertSame('wrong_owner', $resolution->status);
    }

    public function test_unknown_code_blocks_the_reminder_instead_of_sending_default_content(): void
    {
        Mail::fake();
        [$user, $appointment] = $this->appointmentForUser('DESCONOCIDO');
        $appointment->patient->update(['consent_email' => true]);
        $this->template($user, 'Predeterminada', null, true);

        $sent = app(NotificationService::class)->sendReminder($appointment);

        $this->assertFalse($sent);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('communications', 0);
        $this->assertDatabaseCount('shortlinks', 0);
        $this->assertSame('pending', $appointment->fresh()->nimbus_status);
    }

    public function test_matching_code_sends_only_the_resolved_custom_template(): void
    {
        Mail::fake();
        [$user, $appointment] = $this->appointmentForUser('BP');
        $appointment->patient->update(['consent_email' => true]);
        $this->template($user, 'Carta BP', 'BP');

        $sent = app(NotificationService::class)->sendReminder($appointment);

        $this->assertTrue($sent);
        Mail::assertSent(TemplatedReminder::class, 1);
        Mail::assertNotSent(AppointmentReminder::class);
        $this->assertDatabaseHas('communications', [
            'appointment_id' => $appointment->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
        $this->assertDatabaseCount('shortlinks', 3);
        $this->assertSame('reminder_sent', $appointment->fresh()->nimbus_status);
    }

    public function test_partial_delivery_still_sends_configured_email_when_configured_sms_fails(): void
    {
        Mail::fake();
        [$user, $appointment] = $this->appointmentForUser(null);
        $appointment->patient->update([
            'phone' => '+34600000000',
            'consent_email' => true,
            'consent_sms' => true,
        ]);
        $this->template($user, 'Email principal', null, true);
        $this->template($user, 'SMS principal', null, true, 'sms');
        $this->mock(AcumbamailService::class, function ($mock): void {
            $mock->shouldReceive('sendSMS')->andThrow(new \Exception('Proveedor temporalmente no disponible'));
        });

        $sent = app(NotificationService::class)->sendReminder($appointment);

        $this->assertTrue($sent);
        Mail::assertSent(TemplatedReminder::class, 1);
        $this->assertDatabaseHas('communications', [
            'appointment_id' => $appointment->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('communications', [
            'appointment_id' => $appointment->id,
            'channel' => 'sms',
            'status' => 'failed',
        ]);
        $this->assertSame('pending', $appointment->fresh()->nimbus_status);
    }

    private function appointmentForUser(?string $messageCode): array
    {
        $user = $this->user(uniqid().'@example.com');
        $patient = Patient::create([
            'user_id' => $user->id,
            'code' => strtoupper(uniqid('P')),
            'name' => 'Paciente de prueba',
            'email' => uniqid().'@example.com',
        ]);

        $appointment = Appointment::create([
            'google_event_id' => uniqid('event-'),
            'calendar_id' => 'primary',
            'summary' => 'Sesión de prueba',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
            'timezone' => 'Europe/Madrid',
            'patient_id' => $patient->id,
            'message_code' => $messageCode,
        ]);

        return [$user, $appointment];
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => 'Psicóloga',
            'email' => $email,
            'password' => 'secret',
        ]);
    }

    private function template(
        User $user,
        string $name,
        ?string $code,
        bool $default = false,
        string $channel = 'email'
    ): MessageTemplate {
        return MessageTemplate::create([
            'user_id' => $user->id,
            'name' => $name,
            'code' => $code,
            'channel' => $channel,
            'subject' => $channel === 'email' ? 'Recordatorio' : null,
            'body' => 'Contenido',
            'is_default' => $default,
        ]);
    }
}
