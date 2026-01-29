<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Mail\FirstSessionDetected;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FirstSessionService
{
    /**
     * Title pattern to detect first sessions
     */
    public const FIRST_SESSION_TITLE = 'Primera sesión Psicóloga Laura';

    /**
     * Check if an event is a first session based on title
     */
    public function isFirstSession(string $title): bool
    {
        return str_contains($title, self::FIRST_SESSION_TITLE);
    }

    /**
     * Parse Cal.com description to extract patient data
     * 
     * Expected format:
     * Qué:
     * Primera sesión Psicologalaura
     * 
     * Quien:
     * Laura García - Organizador
     * laurapsicologiaonline@gmail.com
     * Laura García Jiménez           ← PATIENT NAME
     * lauragarciajimz@gmail.com      ← PATIENT EMAIL
     * 
     * Donde:
     * +34621072649                    ← PHONE (alternative)
     * 
     * Notas Adicionales:
     * Notes content...               ← NOTES
     * 
     * Número de Teléfono:
     * 628640445                       ← PATIENT PHONE
     */
    public function parseCalComDescription(?string $description): array
    {
        $data = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'notes' => null,
        ];

        if (!$description) {
            return $data;
        }

        $lines = explode("\n", $description);
        $currentSection = null;
        $organizerFound = false;

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Detect section headers
            if (str_starts_with($line, 'Qué:') || $line === 'Qué:') {
                $currentSection = 'what';
                continue;
            }
            if (str_starts_with($line, 'Quien:') || $line === 'Quien:') {
                $currentSection = 'who';
                $organizerFound = false;
                continue;
            }
            if (str_starts_with($line, 'Donde:') || $line === 'Donde:') {
                $currentSection = 'where';
                continue;
            }
            if (str_starts_with($line, 'Notas Adicionales:') || $line === 'Notas Adicionales:') {
                $currentSection = 'notes';
                continue;
            }
            if (str_starts_with($line, 'Número de Teléfono:') || $line === 'Número de Teléfono:') {
                $currentSection = 'phone';
                continue;
            }
            if (str_starts_with($line, 'Zona horaria') || str_starts_with($line, 'Descripción') || str_starts_with($line, 'He leído') || str_starts_with($line, '¿Necesita reprogramar')) {
                $currentSection = null;
                continue;
            }

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Parse based on current section
            switch ($currentSection) {
                case 'who':
                    // First person with "Organizador" is the professional, skip
                    if (str_contains($line, 'Organizador')) {
                        $organizerFound = true;
                        continue 2;
                    }
                    
                    // If it's an email (after organizer was found)
                    if ($organizerFound && filter_var($line, FILTER_VALIDATE_EMAIL)) {
                        // If we already have an email, this is the patient's email
                        if ($data['email']) {
                            // Previous email was organizer's, this is patient's
                        } else {
                            // Check if next non-empty line is a name (not an email)
                            // For now, assume second email in "Quien" section is patient
                        }
                        
                        // If no name yet, the previous line might have been the name
                        if (!$data['name']) {
                            // Look back for name - we'll handle this differently
                        }
                        
                        $data['email'] = $line;
                        continue 2;
                    }
                    
                    // If it looks like a name (not email, not contains "Organizador")
                    if ($organizerFound && !filter_var($line, FILTER_VALIDATE_EMAIL) && !$data['name']) {
                        $data['name'] = $line;
                    }
                    break;

                case 'where':
                    // Phone number from "Donde" section (alternative)
                    if (!$data['phone'] && preg_match('/[\d\s\+\-]{9,}/', $line)) {
                        $data['phone'] = preg_replace('/[^\d\+]/', '', $line);
                    }
                    break;

                case 'notes':
                    // Accumulate notes
                    if ($data['notes']) {
                        $data['notes'] .= "\n" . $line;
                    } else {
                        $data['notes'] = $line;
                    }
                    break;

                case 'phone':
                    // Direct phone number
                    if (preg_match('/[\d\s\+\-]{6,}/', $line)) {
                        // Prefer this phone over "Donde" phone
                        $phone = preg_replace('/[^\d\+]/', '', $line);
                        // Add country code if missing
                        if (!str_starts_with($phone, '+') && strlen($phone) === 9) {
                            $phone = '+34' . $phone;
                        }
                        $data['phone'] = $phone;
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * Process a first session appointment - send email to professional
     */
    public function processFirstSession(Appointment $appointment, User $user): bool
    {
        // Check if already processed
        if ($appointment->first_session_notified) {
            return false;
        }

        // Parse description to get patient data
        $patientData = $this->parseCalComDescription($appointment->description);

        // Generate create patient URL with prefilled data
        $params = array_filter([
            'name' => $patientData['name'],
            'email' => $patientData['email'],
            'phone' => $patientData['phone'],
            'notes' => $patientData['notes'],
        ]);

        try {
            Mail::to($user->email)->send(new FirstSessionDetected(
                $appointment,
                $patientData,
                $params
            ));

            // Mark as notified (we'll add this field to appointments)
            DB::table('appointments')
                ->where('id', $appointment->id)
                ->update(['first_session_notified' => true]);

            Log::info("First session email sent for appointment {$appointment->id}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send first session email: " . $e->getMessage());
            return false;
        }
    }
}
