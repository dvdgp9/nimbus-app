<?php

namespace Tests\Feature;

use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Models\Communication;
use App\Models\Patient;
use App\Services\AcumbamailService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regression tests for the "email succeeded but SMS failed" reminder bug
 * observed in production on 2026-06-27 (appointment 53). Acumbamail returned a
 * transient 404; the reminder was still marked as fully sent, so the failed SMS
 * was never retried.
 */
class ReminderPartialDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(): Appointment
    {
        $patient = Patient::create([
            'name' => 'Clara Robles',
            'email' => 'clara@example.com',
            'phone' => '+34628640445',
            'consent_email' => true,
            'consent_sms' => true,
        ]);

        return Appointment::create([
            'calendar_id' => 'cal-1',
            'google_event_id' => 'evt-' . uniqid(),
            'patient_id' => $patient->id,
            'summary' => 'Sesión de seguimiento',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
            'nimbus_status' => 'pending',
        ]);
    }

    public function test_failed_sms_does_not_mark_the_reminder_as_sent(): void
    {
        Mail::fake();

        // Simulate the Acumbamail 404: SMS sending throws.
        $this->mock(AcumbamailService::class, function ($mock) {
            $mock->shouldReceive('sendSMS')
                ->andThrow(new \Exception('Acumbamail API error: 404 Not Found'));
        });

        $appointment = $this->makeAppointment();

        $result = (new NotificationService())->sendReminder($appointment);

        // At least one channel (email) succeeded.
        $this->assertTrue($result);
        Mail::assertSent(AppointmentReminder::class, 1);

        $appointment->refresh();

        // The crux of the bug fix: because SMS failed, the appointment must stay
        // pending so the cron retries it — NOT be marked as reminder_sent.
        $this->assertNull($appointment->reminder_sent_at, 'reminder_sent_at must stay null when SMS fails');
        $this->assertSame('pending', $appointment->nimbus_status);

        $this->assertSame('sent', Communication::where('channel', 'email')->first()->status);
        $this->assertSame('failed', Communication::where('channel', 'sms')->first()->status);
    }

    public function test_retry_resends_only_the_failed_sms_and_then_completes(): void
    {
        Mail::fake();
        $appointment = $this->makeAppointment();

        // First tick: SMS fails (transient 404), email goes out.
        $this->mock(AcumbamailService::class, function ($mock) {
            $mock->shouldReceive('sendSMS')
                ->andThrow(new \Exception('Acumbamail API error: 404 Not Found'));
        });
        (new NotificationService())->sendReminder($appointment);

        // Second tick: Acumbamail recovers, SMS now succeeds.
        $this->mock(AcumbamailService::class, function ($mock) {
            $mock->shouldReceive('sendSMS')->andReturn('sms-123');
        });
        (new NotificationService())->sendReminder($appointment->fresh());

        // Email must NOT be resent on the retry (idempotent): still exactly one.
        Mail::assertSent(AppointmentReminder::class, 1);
        $this->assertSame(1, Communication::where('channel', 'email')->where('status', 'sent')->count());

        // SMS now succeeded with the provider id recorded.
        $sms = Communication::where('channel', 'sms')->where('status', 'sent')->first();
        $this->assertNotNull($sms);
        $this->assertSame('sms-123', $sms->provider_message_id);

        // Both channels delivered -> reminder is finally marked sent.
        $appointment->refresh();
        $this->assertNotNull($appointment->reminder_sent_at);
        $this->assertSame('reminder_sent', $appointment->nimbus_status);
    }
}
