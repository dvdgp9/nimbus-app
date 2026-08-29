<?php

namespace App\Services;

use App\Mail\TemplatedReminder;
use App\Mail\UnknownPatientCode;
use App\Models\Appointment;
use App\Models\Communication;
use App\Models\Patient;
use App\Models\Shortlink;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Maximum number of failed delivery attempts per appointment in 24h
     * before we give up and ask the professional to contact the patient manually.
     */
    public const MAX_DELIVERY_ATTEMPTS = 3;

    private ReminderTemplateResolver $templateResolver;

    public function __construct(
        ?ReminderTemplateResolver $templateResolver = null
    ) {
        $this->templateResolver = $templateResolver ?? app(ReminderTemplateResolver::class);
    }

    /**
     * Send reminder for an appointment via ALL channels with consent
     * Returns true if at least one channel succeeded.
     */
    public function sendReminder(Appointment $appointment): bool
    {
        // BUG-B7: prevent concurrent sends of the same reminder. Two cron ticks
        // overlapping (or a sync running at the same time as send-reminders)
        // could otherwise both decide this reminder is "pending" and send twice.
        $lock = Cache::lock("nimbus:reminder:appt:{$appointment->id}", 120);
        if (! $lock->get()) {
            Log::info("Reminder skipped: lock held for appointment {$appointment->id}");

            return false;
        }

        try {
            return $this->doSendReminder($appointment);
        } finally {
            $lock->release();
        }
    }

    protected function doSendReminder(Appointment $appointment): bool
    {
        if ($appointment->isExcludedFromAutomation()) {
            Log::info("Reminder skipped because appointment {$appointment->id} is excluded from automation");

            return false;
        }

        if ($appointment->requiresProfessionalReview()) {
            Log::info("Reminder blocked pending professional review for yellow appointment {$appointment->id}");

            return false;
        }

        if (! $appointment->patient) {
            // N2: notify with or without a detected code. A title we cannot read
            // at all used to end here as a log line the professional never saw.
            $this->notifyUnknownPatientCode($appointment, $appointment->suggested_patient_code);

            return false;
        }

        $patient = $appointment->patient;

        // Determine which channels to send to (all with consent)
        $channelsToSend = [];
        if ($patient->consent_email && $patient->email) {
            $channelsToSend[] = 'email';
        }
        if ($patient->consent_sms && $patient->phone) {
            $channelsToSend[] = 'sms';
        }

        if (empty($channelsToSend)) {
            Log::warning("Patient {$patient->id} has no channels with consent");

            return false;
        }

        // Resolve every consented channel before creating links or sending
        // anything. Unknown codes and missing defaults must be visible
        // configuration errors, never silent fallbacks to Nimbus copy.
        $templateResolutions = [];
        $hasBlockedChannel = false;
        foreach ($channelsToSend as $channel) {
            $resolution = $this->templateResolver->resolve($appointment, $channel);
            $templateResolutions[$channel] = $resolution;

            if (! $resolution->isReady()) {
                $hasBlockedChannel = true;
                Log::warning('Reminder blocked by template configuration', [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $patient->id,
                    'user_id' => $patient->user_id,
                    'channel' => $channel,
                    'message_code' => $appointment->message_code,
                    'resolution_status' => $resolution->status,
                    'resolution_message' => $resolution->message,
                ]);
            }
        }

        if ($hasBlockedChannel) {
            return false;
        }

        // BUG-B7: cap retries. If this reminder has already failed too many times
        // in the last 24h, stop trying and alert the professional so they can
        // contact the patient another way.
        $recentFailures = Communication::where('appointment_id', $appointment->id)
            ->where('type', 'reminder')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentFailures >= self::MAX_DELIVERY_ATTEMPTS) {
            Log::error("Reminder give up: {$recentFailures} failed attempts for appointment {$appointment->id}");
            $appointment->markReminderSent(); // stop the cron from retrying
            $this->notifyProfessionalOfDeliveryFailure($appointment, $recentFailures);

            return false;
        }

        // Generate shortlinks (shared for all channels)
        $confirmLink = Shortlink::createForAppointment($appointment, 'confirm');
        $cancelLink = Shortlink::createForAppointment($appointment, 'cancel');
        $rescheduleLink = Shortlink::createForAppointment($appointment, 'reschedule');

        // Build template data for dynamic fields
        $templateData = $this->buildTemplateData($appointment, $patient, $confirmLink, $cancelLink);

        $data = [
            'appointment' => $appointment,
            'patient' => $patient,
            'confirmUrl' => $confirmLink->getUrl(),
            'cancelUrl' => $cancelLink->getUrl(),
            'rescheduleUrl' => $rescheduleLink->getUrl(),
            'templateData' => $templateData,
            'templateResolutions' => $templateResolutions,
        ];

        $results = [];

        // Send via ALL channels with consent
        foreach ($channelsToSend as $channel) {
            // Idempotency: if a previous run already delivered this channel,
            // don't resend it. This lets the cron retry a *failed* channel
            // (e.g. SMS) on a later tick without duplicating one that already
            // succeeded (e.g. email). Without this, a transient SMS failure
            // would either be lost (if we marked the reminder done) or cause
            // duplicate emails (if we didn't).
            if ($this->channelAlreadySent($appointment, $channel)) {
                $results[$channel] = true;
                Log::info("Reminder channel {$channel} already sent for appointment {$appointment->id}, skipping");

                continue;
            }

            try {
                $success = match ($channel) {
                    'email' => $this->sendEmail($appointment, $patient, $data),
                    'sms' => $this->sendSMS($appointment, $patient, $data),
                    default => false,
                };
                $results[$channel] = $success;

                if ($success) {
                    Log::info("Reminder sent via {$channel} for appointment {$appointment->id}");
                }
            } catch (Exception $e) {
                Log::error("Failed to send {$channel} reminder: ".$e->getMessage());
                $results[$channel] = false;
            }
        }

        if (($results['email'] ?? false) && array_key_exists('sms', $results) && ! $results['sms']) {
            Log::warning('Reminder partially delivered: email succeeded but SMS failed', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'phone' => $patient->phone,
            ]);
        }

        // Only consider the reminder "sent" (and stop the cron from retrying)
        // once EVERY consented channel has succeeded. If any channel failed,
        // we leave the appointment pending so the next cron tick retries just
        // the failed channel(s) — capped by MAX_DELIVERY_ATTEMPTS above.
        $allChannelsSucceeded = ! empty($results) && ! in_array(false, $results, true);
        if ($allChannelsSucceeded) {
            $appointment->markReminderSent();
        }

        // Return true if at least one channel succeeded
        return in_array(true, $results, true);
    }

    /**
     * Build template data array for dynamic field replacement
     */
    protected function buildTemplateData(Appointment $appointment, Patient $patient, Shortlink $confirmLink, Shortlink $cancelLink): array
    {
        $professional = $patient->user;

        $rescheduleLink = RescheduleLinkService::forAppointment($appointment);

        return [
            'patient_name' => $patient->name,
            'patient_first_name' => explode(' ', $patient->name)[0],
            'patient_email' => $patient->email ?? '',
            'appointment_date' => $appointment->formatted_date,
            'appointment_time' => $appointment->formatted_time,
            'appointment_summary' => $appointment->summary ?? 'Cita',
            'professional_name' => $professional?->name ?? 'Tu profesional',
            'confirm_link' => $confirmLink->getUrl(),
            'cancel_link' => $cancelLink->getUrl(),
            'reschedule_link' => $rescheduleLink,
            'hangout_link' => $appointment->hangout_link ?? '',
        ];
    }

    /**
     * Has this reminder channel already been delivered for this appointment?
     * Used for idempotent retries so a previously-sent channel is not resent
     * when the cron retries the appointment for a different failed channel.
     */
    protected function channelAlreadySent(Appointment $appointment, string $channel): bool
    {
        return Communication::where('appointment_id', $appointment->id)
            ->where('channel', $channel)
            ->where('type', 'reminder')
            ->whereIn('status', ['sent', 'delivered'])
            ->exists();
    }

    /**
     * Check if appointment has a valid message code with matching template
     */
    public function hasValidMessageCode(Appointment $appointment): bool
    {
        if (! $appointment->message_code || ! $appointment->patient) {
            return false;
        }

        $user = $appointment->patient->user;
        if (! $user) {
            return false;
        }

        // Check if any template exists with this code for any channel
        return $user->messageTemplates()
            ->where('code', $appointment->message_code)
            ->exists();
    }

    /**
     * Send email reminder
     */
    protected function sendEmail(Appointment $appointment, Patient $patient, array $data): bool
    {
        $template = $data['templateResolutions']['email']->template;
        $templateData = $data['templateData'];

        $subject = $template->parseSubject($templateData);
        $body = $template->parse($templateData);

        $communication = Communication::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'channel' => 'email',
            'type' => 'reminder',
            'recipient' => $patient->email,
            'subject' => $subject,
            'message_body' => $body,
            'consent_verified' => true,
            'status' => 'pending',
        ]);

        try {
            Mail::to($patient->email)->send(new TemplatedReminder($appointment, $patient, $subject, $body, $data));

            $communication->markAsSent();

            Log::info("Email reminder sent to {$patient->email} for appointment {$appointment->id}");

            return true;
        } catch (Exception $e) {
            $communication->markAsFailed($e->getMessage());
            Log::error('Email failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send SMS reminder via Acumbamail
     */
    protected function sendSMS(Appointment $appointment, Patient $patient, array $data): bool
    {
        if (! $patient->phone) {
            Log::warning("Patient {$patient->id} has no phone number");

            return false;
        }

        $template = $data['templateResolutions']['sms']->template;
        $message = $template->parse($data['templateData']);

        $communication = Communication::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'channel' => 'sms',
            'type' => 'reminder',
            'recipient' => $patient->phone,
            'message_body' => $message,
            'consent_verified' => true,
            'status' => 'pending',
        ]);

        try {
            $acumbamailService = app(AcumbamailService::class);
            $smsId = $acumbamailService->sendSMS($patient->phone, $message);

            $communication->markAsSent($smsId);

            Log::info("SMS reminder sent to {$patient->phone} for appointment {$appointment->id}");

            return true;
        } catch (Exception $e) {
            $communication->markAsFailed($e->getMessage());
            Log::error('SMS failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Notify the professional that a patient code was not found
     */
    public function notifyUnknownPatientCode(Appointment $appointment, ?string $patientCode): bool
    {
        if ($appointment->isExcludedFromAutomation()) {
            return false;
        }

        // Get the user who owns this calendar
        $user = $this->getUserFromAppointment($appointment);

        if (! $user) {
            Log::warning("Cannot notify about unknown patient code: no user found for appointment {$appointment->id}");

            return false;
        }

        // Check if we already notified about this appointment (avoid spam)
        if ($appointment->unknown_patient_notified) {
            Log::info("Already notified about unknown patient code for appointment {$appointment->id}");

            return false;
        }

        try {
            Mail::to($user->email)->send(new UnknownPatientCode($appointment, $patientCode));

            // Mark as notified to avoid sending multiple times
            $appointment->update(['unknown_patient_notified' => true]);

            Log::info('Notified professional about an appointment with no matching patient', [
                'appointment_id' => $appointment->id,
                'user_id' => $user->id,
                // N2: null means the title had no readable code at all.
                'patient_code' => $patientCode,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send unknown patient code notification: '.$e->getMessage());

            return false;
        }
    }

    /**
     * N1: second, urgent notice when the unknown code was already reported but
     * the patient still does not exist and the session is now within
     * Appointment::UNKNOWN_PATIENT_ESCALATION_HOURS.
     */
    public function escalateUnknownPatientCode(Appointment $appointment, ?string $patientCode): bool
    {
        if ($appointment->isExcludedFromAutomation() || ! $appointment->needsUnknownPatientEscalation()) {
            return false;
        }

        $user = $this->getUserFromAppointment($appointment);

        if (! $user) {
            Log::warning("Cannot escalate unknown patient code: no user found for appointment {$appointment->id}");

            return false;
        }

        try {
            Mail::to($user->email)->send(new UnknownPatientCode($appointment, $patientCode, escalated: true));

            // Only mark it once the email actually left, so a failed send is
            // retried by the next sync instead of being silently swallowed.
            $appointment->update(['unknown_patient_escalated_at' => now()]);

            Log::info("Escalated unknown patient code '{$patientCode}' for appointment {$appointment->id} to user {$user->id}");

            return true;
        } catch (Exception $e) {
            Log::error('Failed to escalate unknown patient code notification: '.$e->getMessage(), [
                'appointment_id' => $appointment->id,
                'patient_code' => $patientCode,
            ]);

            return false;
        }
    }

    /**
     * Get the user who owns the calendar for this appointment
     */
    protected function getUserFromAppointment(Appointment $appointment): ?User
    {
        if (! $appointment->calendar_id) {
            return null;
        }

        // Find user via connected_calendars table
        $userId = \Illuminate\Support\Facades\DB::table('connected_calendars')
            ->where('calendar_id', $appointment->calendar_id)
            ->where('enabled', 1)
            ->value('user_id');

        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }

    /**
     * Notify the professional that we couldn't deliver a reminder after
     * MAX_DELIVERY_ATTEMPTS failures, so they can reach the patient manually.
     */
    protected function notifyProfessionalOfDeliveryFailure(Appointment $appointment, int $attempts): void
    {
        $patient = $appointment->patient;
        if (! $patient || ! $patient->user || empty($patient->user->email)) {
            Log::warning("Cannot alert professional about delivery failure for appointment {$appointment->id}: no user email.");

            return;
        }

        $when = $appointment->start_at?->format('d/m/Y H:i') ?? '—';
        $subject = "[Nimbus] No se pudo avisar a {$patient->name}";
        $body = "Hola {$patient->user->name},\n\n"
              ."Hemos intentado enviar el recordatorio de la cita de {$patient->name} ({$when}) "
              ."{$attempts} veces sin éxito en las últimas 24 horas.\n\n"
              ."Datos del paciente:\n"
              .'  • Email: '.($patient->email ?: '—')."\n"
              .'  • Teléfono: '.($patient->phone ?: '—')."\n\n"
              ."Te recomendamos contactar manualmente para confirmar la asistencia.\n\n"
              .'Nimbus';

        try {
            Mail::raw($body, function ($message) use ($patient, $subject) {
                $message->to($patient->user->email)->subject($subject);
            });
        } catch (Exception $e) {
            Log::error('Failed to send delivery-failure alert to professional: '.$e->getMessage());
        }
    }
}
