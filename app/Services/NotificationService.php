<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Communication;
use App\Models\Shortlink;
use App\Models\MessageTemplate;
use App\Mail\AppointmentReminder;
use App\Mail\TemplatedReminder;
use App\Mail\UnknownPatientCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;
use App\Services\AcumbamailService;

class NotificationService
{
    /**
     * Maximum number of failed delivery attempts per appointment in 24h
     * before we give up and ask the professional to contact the patient manually.
     */
    public const MAX_DELIVERY_ATTEMPTS = 3;

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
        if (!$lock->get()) {
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
        if ($appointment->requiresProfessionalReview()) {
            Log::info("Reminder blocked pending professional review for yellow appointment {$appointment->id}");
            return false;
        }

        if (!$appointment->patient) {
            // Check if there's a patient code that wasn't found
            $suggestedCode = $appointment->suggested_patient_code;
            if ($suggestedCode) {
                $this->notifyUnknownPatientCode($appointment, $suggestedCode);
            } else {
                Log::warning("Appointment {$appointment->id} has no patient assigned and no code detected");
            }
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
        ];

        $results = [];
        
        // Send via ALL channels with consent
        foreach ($channelsToSend as $channel) {
            try {
                $success = match($channel) {
                    'email' => $this->sendEmail($appointment, $patient, $data),
                    'sms' => $this->sendSMS($appointment, $patient, $data),
                    default => false,
                };
                $results[$channel] = $success;
                
                if ($success) {
                    Log::info("Reminder sent via {$channel} for appointment {$appointment->id}");
                }
            } catch (Exception $e) {
                Log::error("Failed to send {$channel} reminder: " . $e->getMessage());
                $results[$channel] = false;
            }
        }

        if (($results['email'] ?? false) && array_key_exists('sms', $results) && !$results['sms']) {
            Log::warning('Reminder partially delivered: email succeeded but SMS failed', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'phone' => $patient->phone,
            ]);
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
     * Get the user's template for a channel by message code, or fallback to default
     */
    protected function getUserTemplate(Patient $patient, string $channel, ?string $messageCode = null): ?MessageTemplate
    {
        if (!$patient->user) {
            return null;
        }

        $query = $patient->user->messageTemplates()->forChannel($channel);

        // If message code is provided, try to find template with that code
        if ($messageCode) {
            $template = (clone $query)->where('code', $messageCode)->first();
            if ($template) {
                return $template;
            }
        }

        // Fallback to default template
        return $query->default()->first();
    }

    /**
     * Check if appointment has a valid message code with matching template
     */
    public function hasValidMessageCode(Appointment $appointment): bool
    {
        if (!$appointment->message_code || !$appointment->patient) {
            return false;
        }

        $user = $appointment->patient->user;
        if (!$user) {
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
        $template = $this->getUserTemplate($patient, 'email', $appointment->message_code);
        $templateData = $data['templateData'];

        // Determine subject and body
        if ($template) {
            $subject = $template->parseSubject($templateData);
            $body = $template->parse($templateData);
        } else {
            // Fallback to default
            $subject = 'Recordatorio: ' . $appointment->summary;
            $body = null; // Will use default Mailable template
        }

        $communication = Communication::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'channel' => 'email',
            'type' => 'reminder',
            'recipient' => $patient->email,
            'subject' => $subject,
            'message_body' => $body ?? 'Reminder email',
            'consent_verified' => true,
            'status' => 'pending',
        ]);

        try {
            if ($template) {
                // Send with custom template
                Mail::to($patient->email)->send(new TemplatedReminder($appointment, $patient, $subject, $body, $data));
            } else {
                // Send with default template - pass links array with required keys
                $links = [
                    'confirmUrl' => $data['confirmUrl'],
                    'cancelUrl' => $data['cancelUrl'],
                    'rescheduleUrl' => $data['rescheduleUrl'],
                ];
                Mail::to($patient->email)->send(new AppointmentReminder($appointment, $patient, $links));
            }
            
            $communication->markAsSent();
            $appointment->markReminderSent();
            
            Log::info("Email reminder sent to {$patient->email} for appointment {$appointment->id}");
            return true;
        } catch (Exception $e) {
            $communication->markAsFailed($e->getMessage());
            Log::error("Email failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS reminder via Acumbamail
     */
    protected function sendSMS(Appointment $appointment, Patient $patient, array $data): bool
    {
        if (!$patient->phone) {
            Log::warning("Patient {$patient->id} has no phone number");
            return false;
        }

        $message = $this->buildSMSMessage($appointment, $patient, $data);

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
            $appointment->markReminderSent();
            
            Log::info("SMS reminder sent to {$patient->phone} for appointment {$appointment->id}");
            return true;
        } catch (Exception $e) {
            $communication->markAsFailed($e->getMessage());
            Log::error("SMS failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build SMS message
     */
    protected function buildSMSMessage(Appointment $appointment, Patient $patient, array $data): string
    {
        $template = $this->getUserTemplate($patient, 'sms', $appointment->message_code);
        
        if ($template) {
            return $template->parse($data['templateData']);
        }

        // Fallback to default message
        $firstName = explode(' ', $patient->name)[0];
        
        return sprintf(
            "Hola %s! Recordatorio: %s el %s a las %s. Confirmar: %s Cancelar: %s",
            $firstName,
            $appointment->summary,
            $appointment->formatted_date,
            $appointment->formatted_time,
            $data['confirmUrl'],
            $data['cancelUrl']
        );
    }

    /**
     * Notify the professional that a patient code was not found
     */
    public function notifyUnknownPatientCode(Appointment $appointment, string $patientCode): void
    {
        // Get the user who owns this calendar
        $user = $this->getUserFromAppointment($appointment);
        
        if (!$user) {
            Log::warning("Cannot notify about unknown patient code: no user found for appointment {$appointment->id}");
            return;
        }

        // Check if we already notified about this appointment (avoid spam)
        if ($appointment->unknown_patient_notified) {
            Log::info("Already notified about unknown patient code for appointment {$appointment->id}");
            return;
        }

        try {
            Mail::to($user->email)->send(new UnknownPatientCode($appointment, $patientCode));
            
            // Mark as notified to avoid sending multiple times
            $appointment->update(['unknown_patient_notified' => true]);
            
            Log::info("Notified user {$user->id} about unknown patient code '{$patientCode}' for appointment {$appointment->id}");
        } catch (Exception $e) {
            Log::error("Failed to send unknown patient code notification: " . $e->getMessage());
        }
    }

    /**
     * Get the user who owns the calendar for this appointment
     */
    protected function getUserFromAppointment(Appointment $appointment): ?User
    {
        if (!$appointment->calendar_id) {
            return null;
        }

        // Find user via connected_calendars table
        $userId = \Illuminate\Support\Facades\DB::table('connected_calendars')
            ->where('calendar_id', $appointment->calendar_id)
            ->where('enabled', 1)
            ->value('user_id');

        if (!$userId) {
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
        if (!$patient || !$patient->user || empty($patient->user->email)) {
            Log::warning("Cannot alert professional about delivery failure for appointment {$appointment->id}: no user email.");
            return;
        }

        $when = $appointment->start_at?->format('d/m/Y H:i') ?? '—';
        $subject = "[Nimbus] No se pudo avisar a {$patient->name}";
        $body = "Hola {$patient->user->name},\n\n"
              . "Hemos intentado enviar el recordatorio de la cita de {$patient->name} ({$when}) "
              . "{$attempts} veces sin éxito en las últimas 24 horas.\n\n"
              . "Datos del paciente:\n"
              . "  • Email: " . ($patient->email ?: '—') . "\n"
              . "  • Teléfono: " . ($patient->phone ?: '—') . "\n\n"
              . "Te recomendamos contactar manualmente para confirmar la asistencia.\n\n"
              . "Nimbus";

        try {
            Mail::raw($body, function ($message) use ($patient, $subject) {
                $message->to($patient->user->email)->subject($subject);
            });
        } catch (Exception $e) {
            Log::error("Failed to send delivery-failure alert to professional: " . $e->getMessage());
        }
    }
}
