<?php

namespace Tests\Feature;

use App\Mail\TemplatedReminder;
use App\Mail\UnconfirmedSmsDelivery;
use App\Models\Appointment;
use App\Models\Communication;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SmsDuplicatePreventionTest extends TestCase
{
    private const SEND_URL = 'https://acumbamail.com/api/1/sendSMS/';

    private const REPORT_URL = 'https://acumbamail.com/api/1/getSMSQuickSubscriberReport/';

    private Appointment $appointment;

    private int $sendRequests = 0;

    private array $providerReport = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.acumbamail.auth_token' => 'test-token']);
        Mail::fake();
        Http::preventStrayRequests();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('consent_email')->default(false);
            $table->boolean('consent_sms')->default(true);
            $table->timestamps();
        });
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('google_event_id');
            $table->string('calendar_id');
            $table->string('summary');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedBigInteger('patient_id');
            $table->string('nimbus_status')->default('pending');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('rescheduled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('channel');
            $table->string('code')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });
        (require database_path('migrations/2025_11_11_200818_create_communications_table.php'))->up();
        Schema::create('shortlinks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('token')->unique();
            $table->string('action');
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });

        $user = User::create(['name' => 'Profesional', 'email' => 'professional@example.test', 'onboarding_completed_at' => now()]);
        $patient = Patient::create([
            'user_id' => $user->id, 'name' => 'Paciente de prueba', 'email' => 'patient@example.test',
            'phone' => '+34600111222', 'consent_sms' => true,
        ]);
        MessageTemplate::create([
            'user_id' => $user->id, 'name' => 'SMS', 'channel' => 'sms',
            'body' => 'Cita el {{appointment_date_short}} a las {{appointment_time}}. {{manage_link}}', 'is_default' => true,
        ]);
        $this->appointment = Appointment::create([
            'google_event_id' => 'event-test', 'calendar_id' => 'calendar-test', 'summary' => 'Cita',
            'patient_id' => $patient->id, 'start_at' => now()->addDays(2), 'end_at' => now()->addDays(2)->addHour(),
        ]);
    }

    private function fakeLostResponse(bool $reportAccepted = true): void
    {
        Http::fake([
            self::SEND_URL => function ($request) use ($reportAccepted) {
                $this->sendRequests++;
                $message = json_decode($request['messages'], true)[0];
                if ($reportAccepted) {
                    $this->providerReport = [[
                        'sms_id' => 12345, 'phone' => '0034600111222', 'sms_content' => $message['body'],
                        'sent_at' => now()->toDateTimeString(), 'status' => 'Entregado',
                        'delivered_at' => now()->addSeconds(3)->toDateTimeString(),
                    ]];
                }
                throw new ConnectionException('Accepted but response lost');
            },
            self::REPORT_URL => fn () => Http::response($this->providerReport),
        ]);
    }

    public function test_timeout_then_next_tick_recovers_delivery_without_sending_again(): void
    {
        $this->fakeLostResponse();
        $this->assertFalse(app(NotificationService::class)->sendReminder($this->appointment));
        $this->assertSame('pending', Communication::sole()->status);
        $this->assertNull($this->appointment->fresh()->reminder_sent_at);

        $this->travel(15)->minutes();
        $this->assertTrue(app(NotificationService::class)->sendReminder($this->appointment->fresh()));

        $this->assertSame(1, $this->sendRequests);
        $this->assertSame('delivered', Communication::sole()->status);
        $this->assertSame('12345', Communication::sole()->provider_message_id);
        $this->assertNotNull(Communication::sole()->delivered_at);
        $this->assertNotNull($this->appointment->fresh()->reminder_sent_at);
    }

    public function test_no_report_match_never_causes_another_send_and_alerts_once(): void
    {
        $this->fakeLostResponse(false);
        app(NotificationService::class)->sendReminder($this->appointment);
        $this->travel(31)->minutes();

        app(NotificationService::class)->sendReminder($this->appointment->fresh());
        app(NotificationService::class)->sendReminder($this->appointment->fresh());

        $this->assertSame(1, $this->sendRequests);
        $this->assertSame('pending', Communication::sole()->status);
        $this->assertSame(1, Communication::count());
        Mail::assertSent(UnconfirmedSmsDelivery::class, fn ($mail) => $mail->hasTo('professional@example.test')
            && str_contains($mail->render(), 'detenido los reintentos'));
        Mail::assertSentCount(1);
    }

    public function test_report_outage_keeps_delivery_pending_without_resending(): void
    {
        $this->fakeLostResponse();
        app(NotificationService::class)->sendReminder($this->appointment);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([self::REPORT_URL => Http::response(['error' => 'Unavailable'], 401)]);

        $this->assertFalse(app(NotificationService::class)->sendReminder($this->appointment->fresh()));
        $this->assertSame('pending', Communication::sole()->status);
        Http::assertNotSent(fn ($request) => $request->url() === self::SEND_URL);
    }

    public function test_wrong_phone_body_and_old_sends_do_not_confirm_delivery(): void
    {
        $this->fakeLostResponse();
        app(NotificationService::class)->sendReminder($this->appointment);
        $correct = $this->providerReport[0];
        $this->providerReport = [
            array_replace($correct, ['phone' => '+34600999888']),
            array_replace($correct, ['sms_content' => 'A different reminder']),
            array_replace($correct, ['sent_at' => now()->subDay()->toDateTimeString()]),
        ];

        $this->assertFalse(app(NotificationService::class)->sendReminder($this->appointment->fresh()));
        $this->assertSame('pending', Communication::sole()->status);
        $this->assertSame(1, $this->sendRequests);
    }

    public function test_provider_acceptance_blocks_resends_while_handset_delivery_is_pending(): void
    {
        $this->fakeLostResponse();
        app(NotificationService::class)->sendReminder($this->appointment);
        $this->providerReport[0]['status'] = 'Pendiente';
        $this->providerReport[0]['delivered_at'] = null;

        $this->assertTrue(app(NotificationService::class)->sendReminder($this->appointment->fresh()));
        $this->assertTrue(app(NotificationService::class)->sendReminder($this->appointment->fresh()));

        $this->assertSame('sent', Communication::sole()->status);
        $this->assertSame(1, $this->sendRequests);
    }

    public function test_a_crash_leaving_a_pending_row_is_reconciled_without_sending(): void
    {
        $communication = $this->pendingCommunication();
        Http::fake([self::REPORT_URL => Http::response([])]);

        app(NotificationService::class)->sendReminder($this->appointment);

        $this->assertSame('pending', $communication->fresh()->status);
        $this->assertSame(1, Communication::count());
        Http::assertNotSent(fn ($request) => $request->url() === self::SEND_URL);
    }

    public function test_an_explicit_rejection_still_allows_a_later_successful_retry(): void
    {
        Http::fake([self::SEND_URL => Http::sequence()
            ->push(['messages' => [['status' => 2, 'error' => 'Not enough credits']]], 400)
            ->push(['messages' => [['status' => '0', 'id' => 12345]]])]);

        $this->assertFalse(app(NotificationService::class)->sendReminder($this->appointment));
        $this->assertSame('failed', Communication::sole()->status);
        $this->assertTrue(app(NotificationService::class)->sendReminder($this->appointment->fresh()));
        $this->assertSame(1, Communication::where('status', 'sent')->count());
        Http::assertSentCount(2);
    }

    public function test_email_is_not_duplicated_while_sms_is_being_reconciled(): void
    {
        $patient = $this->appointment->patient;
        $patient->update(['consent_email' => true]);
        MessageTemplate::create([
            'user_id' => $patient->user_id, 'name' => 'Email', 'channel' => 'email',
            'subject' => 'Recordatorio', 'body' => 'Tu cita', 'is_default' => true,
        ]);
        $this->fakeLostResponse();

        $this->assertTrue(app(NotificationService::class)->sendReminder($this->appointment));
        $this->travel(15)->minutes();
        $this->assertTrue(app(NotificationService::class)->sendReminder($this->appointment->fresh()));

        Mail::assertSent(TemplatedReminder::class, 1);
        $this->assertSame(1, $this->sendRequests);
        $this->assertNotNull($this->appointment->fresh()->reminder_sent_at);
    }

    public function test_command_recovers_sms_even_after_patient_confirmation(): void
    {
        $this->fakeLostResponse();
        app(NotificationService::class)->sendReminder($this->appointment);
        $this->appointment->update(['nimbus_status' => 'confirmed']);
        $this->travel(15)->minutes();

        $this->artisan('nimbus:reconcile-sms')->assertSuccessful();

        $this->assertSame('delivered', Communication::sole()->status);
        $this->assertSame('confirmed', $this->appointment->fresh()->nimbus_status);
        $this->assertSame(1, $this->sendRequests);
    }

    public function test_command_dry_run_does_not_query_provider_or_notify(): void
    {
        $communication = $this->pendingCommunication();
        $this->travel(60)->minutes();
        Http::fake();

        $this->artisan('nimbus:reconcile-sms --dry-run')->assertSuccessful();

        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertSame('pending', $communication->fresh()->status);
    }

    public function test_pending_sms_for_an_old_date_does_not_block_a_new_date(): void
    {
        $this->pendingCommunication();
        $this->travel(10)->minutes();
        $this->appointment->update(['rescheduled_at' => now(), 'start_at' => now()->addWeek()]);
        Http::fake([self::SEND_URL => Http::response(['messages' => [['status' => 0, 'id' => 12345]]])]);

        $this->assertTrue(app(NotificationService::class)->sendReminder($this->appointment->fresh()));

        Http::assertSentCount(1);
        $this->assertSame(2, Communication::count());
    }

    public function test_a_failed_id_save_leaves_a_pending_row_instead_of_a_retryable_failure(): void
    {
        Http::fake([self::SEND_URL => Http::response(['messages' => [['status' => 0, 'id' => 12345]]])]);
        Communication::updating(function ($communication) {
            if ($communication->status === 'sent') {
                throw new \RuntimeException('Database write failed after acceptance');
            }
        });
        try {
            $this->assertFalse(app(NotificationService::class)->sendReminder($this->appointment));
            $this->assertSame('pending', Communication::sole()->status);
            Http::assertSentCount(1);
        } finally {
            Communication::flushEventListeners();
        }
        app(NotificationService::class)->sendReminder($this->appointment->fresh());
        Http::assertSentCount(1);
        $this->assertSame('pending', Communication::sole()->status);
    }

    public function test_multiple_pending_sends_share_one_provider_report_request(): void
    {
        $this->pendingCommunication();
        $this->pendingCommunication();
        $this->travel(15)->minutes();
        Http::fake([self::REPORT_URL => Http::response([])]);

        $this->artisan('nimbus:reconcile-sms')->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertSame(2, Communication::where('status', 'pending')->count());
    }

    public function test_sms_test_page_explains_uncertainty_instead_of_inviting_a_retry(): void
    {
        $this->fakeLostResponse();

        $this->actingAs($this->appointment->patient->user)->from('/sms/test')
            ->post(route('sms.send'), ['phone' => '600111222', 'message' => 'Test'])
            ->assertRedirect('/sms/test')->assertSessionHasErrors('sms');

        $this->assertStringContainsString('puede haberse enviado', session('errors')->first('sms'));
        $this->assertSame(1, $this->sendRequests);
    }

    private function pendingCommunication(): Communication
    {
        return Communication::create([
            'appointment_id' => $this->appointment->id, 'patient_id' => $this->appointment->patient_id,
            'channel' => 'sms', 'type' => 'reminder', 'recipient' => '+34600111222',
            'message_body' => 'Previously submitted SMS', 'status' => 'pending', 'consent_verified' => true,
        ]);
    }
}
