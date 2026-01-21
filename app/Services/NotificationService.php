<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Communication;
use App\Models\Shortlink;
use App\Mail\AppointmentReminder;
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

        $data = [
            'appointment' => $appointment,
            'patient' => $patient,
            'confirmUrl' => $confirmLink->getUrl(),
            'cancelUrl' => $cancelLink->getUrl(),
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
     * Send email reminder
     */
    protected function sendEmail(Appointment $appointment, Patient $patient, array $data): bool
    {
        $communication = Communication::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'channel' => 'email',
            'type' => 'reminder',
            'recipient' => $patient->email,
            'subject' => 'Recordatorio: ' . $appointment->summary,
            'message_body' => 'Reminder email',
            'consent_verified' => true,
            'status' => 'pending',
        ]);

        try {
            Mail::to($patient->email)->send(new AppointmentReminder($appointment, $patient, $data));
            
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

        $message = $this->buildSMSMessage($appointment, $data);

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
    protected function buildSMSMessage(Appointment $appointment, array $data): string
    {
        $firstName = explode(' ', $appointment->patient->name)[0];
        
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
