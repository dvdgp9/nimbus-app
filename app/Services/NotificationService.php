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
     * Send reminder for an appointment
     */
    public function sendReminder(Appointment $appointment): bool
    {
        if (!$appointment->patient) {
            Log::warning("Appointment {$appointment->id} has no patient assigned");
            return false;
        }

        $patient = $appointment->patient;
        $channel = $patient->preferred_channel;

        // Verify consent
        if (!$patient->hasConsentFor($channel)) {
            Log::warning("Patient {$patient->id} has no consent for {$channel}");
            return false;
        }

        // Generate shortlinks
        $confirmLink = Shortlink::createForAppointment($appointment, 'confirm');
        $cancelLink = Shortlink::createForAppointment($appointment, 'cancel');

        // Build template data for dynamic fields
        $templateData = $this->buildTemplateData($appointment, $patient, $confirmLink, $cancelLink);

        $data = [
            'appointment' => $appointment,
            'patient' => $patient,
            'confirmUrl' => $confirmLink->getUrl(),
            'cancelUrl' => $cancelLink->getUrl(),
            'templateData' => $templateData,
        ];

        try {
            return match($channel) {
                'email' => $this->sendEmail($appointment, $patient, $data),
                'sms' => $this->sendSMS($appointment, $patient, $data),
                default => false,
            };
        } catch (Exception $e) {
            Log::error("Failed to send reminder: " . $e->getMessage());
            return false;
        }
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
     * Get the user's default template for a channel, or null if none
     */
    protected function getUserTemplate(Patient $patient, string $channel): ?MessageTemplate
    {
        if (!$patient->user) {
            return null;
        }

        return $patient->user
            ->messageTemplates()
            ->forChannel($channel)
            ->default()
            ->first();
    }

    /**
     * Send email reminder
     */
    protected function sendEmail(Appointment $appointment, Patient $patient, array $data): bool
    {
        $template = $this->getUserTemplate($patient, 'email');
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
                // Send with default template
                Mail::to($patient->email)->send(new AppointmentReminder($appointment, $patient, $data));
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
        $template = $this->getUserTemplate($patient, 'sms');
        
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
