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
        $rescheduleLink = $this->getWhatsAppRescheduleLink($appointment);

        $data = [
            'appointment' => $appointment,
            'patient' => $patient,
            'confirmUrl' => $confirmLink->getUrl(),
            'cancelUrl' => $cancelLink->getUrl(),
            'rescheduleUrl' => $rescheduleLink,
        ];

        try {
            return match($channel) {
                'email' => $this->sendEmail($appointment, $patient, $data),
                'sms' => $this->sendSMS($appointment, $patient, $data),
                'whatsapp' => $this->sendWhatsApp($appointment, $patient, $data),
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
     * Send SMS reminder via Twilio
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
            $twilioService = app(TwilioService::class);
            $sid = $twilioService->sendSMS($patient->phone, $message);
            
            $communication->markAsSent($sid);
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
     * Send WhatsApp reminder via Twilio
     */
    protected function sendWhatsApp(Appointment $appointment, Patient $patient, array $data): bool
    {
        if (!$patient->phone) {
            Log::warning("Patient {$patient->id} has no phone number");
            return false;
        }

        $message = $this->buildWhatsAppMessage($appointment, $data);

        $communication = Communication::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'channel' => 'whatsapp',
            'type' => 'reminder',
            'recipient' => $patient->phone,
            'message_body' => $message,
            'consent_verified' => true,
            'status' => 'pending',
        ]);

        try {
            $twilioService = app(TwilioService::class);
            $sid = $twilioService->sendWhatsApp($patient->phone, $message);
            
            $communication->markAsSent($sid);
            $appointment->markReminderSent();
            
            Log::info("WhatsApp reminder sent to {$patient->phone} for appointment {$appointment->id}");
            return true;
        } catch (Exception $e) {
            $communication->markAsFailed($e->getMessage());
            Log::error("WhatsApp failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build SMS message
     */
    protected function buildSMSMessage(Appointment $appointment, array $data): string
    {
        return sprintf(
            "Hola %s! 👋\n\n" .
            "Recordatorio de tu cita:\n" .
            "📅 %s\n" .
            "🕐 %s\n" .
            "📋 %s\n\n" .
            "Confirmar: %s\n" .
            "Cancelar: %s\n" .
            "Reprogramar: %s",
            $appointment->patient->name,
            $appointment->formatted_date,
            $appointment->formatted_time,
            $appointment->summary,
            $data['confirmUrl'],
            $data['cancelUrl'],
            $data['rescheduleUrl']
        );
    }

    /**
     * Build WhatsApp message based on message_type
     */
    protected function buildWhatsAppMessage(Appointment $appointment, array $data): string
    {
        $firstName = explode(' ', $appointment->patient->name)[0];
        $messageType = $appointment->message_type ?? 2; // Default to type 2 (paid sessions)
        
        // Get the appropriate template
        $template = $this->getWhatsAppTemplate($messageType, $firstName, $appointment);
        
        // Add action buttons
        $template .= sprintf(
            "\n\n✅ Confirmar: %s\n" .
            "❌ Cancelar: %s\n" .
            "📞 Reprogramar: %s",
            $data['confirmUrl'],
            $data['cancelUrl'],
            $data['rescheduleUrl']
        );

        return $template;
    }

    /**
     * Get WhatsApp message template based on type
     */
    protected function getWhatsAppTemplate(int $type, string $firstName, Appointment $appointment): string
    {
        $day = $appointment->formatted_date;
        $time = $appointment->formatted_time;
        
        // IMPORTANTE: este texto debe coincidir EXACTAMENTE con la plantilla de WhatsApp aprobada
        // Hola {{1}} 😊
        // Te recuerdo nuestra sesión del {{2}} a las {{3}} (duración: 55 minutos).
        // Por favor, confirma o reprograma usando los botones que verás a continuación.
        // ¡Gracias! 🤗

        return "Hola {$firstName} 😊\n" .
               "Te recuerdo nuestra sesión del {$day} a las {$time} (duración: 55 minutos).\n" .
               "Por favor, confirma o reprograma usando los botones que verás a continuación.\n" .
               "¡Gracias! 🤗";
    }

    /**
     * Generate WhatsApp reschedule link
     */
    protected function getWhatsAppRescheduleLink(Appointment $appointment): string
    {
        $userPhone = optional($appointment->user)->whatsapp_phone;
        $phone = $userPhone ?: config('services.whatsapp.professional_phone', '+34600000000');
        $message = urlencode(sprintf(
            "Hola! Necesito reprogramar mi cita del %s a las %s. ¿Qué disponibilidad tienes?",
            $appointment->formatted_date,
            $appointment->formatted_time
        ));

        return "https://wa.me/{$phone}?text={$message}";
    }
}
