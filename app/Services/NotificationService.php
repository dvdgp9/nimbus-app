<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Communication;
use App\Models\Shortlink;
use App\Models\MessageTemplate;
use App\Mail\AppointmentReminder;
use App\Mail\TemplatedReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\AcumbamailService;

class NotificationService
{
    /**
     * Send reminder for an appointment via ALL channels with consent
     * Returns array with results per channel
     */
    public function sendReminder(Appointment $appointment): bool
    {
        if (!$appointment->patient) {
            Log::warning("Appointment {$appointment->id} has no patient assigned");
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

        // Return true if at least one channel succeeded
        return in_array(true, $results, true);
    }

    /**
     * Build template data array for dynamic field replacement
     */
    protected function buildTemplateData(Appointment $appointment, Patient $patient, Shortlink $confirmLink, Shortlink $cancelLink): array
    {
        $professional = $patient->user;
        
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
}
