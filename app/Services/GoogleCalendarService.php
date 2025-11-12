<?php

namespace App\Services;

use Google\Service\Calendar as GoogleCalendar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    public function listCalendars(?string $accountEmail): array
    {
        $client = GoogleClientFactory::make($accountEmail);
        $service = new GoogleCalendar($client);

        $calList = $service->calendarList->listCalendarList([ 'minAccessRole' => 'reader', 'maxResults' => 250 ]);
        $out = [];
        foreach ($calList->getItems() as $cal) {
            $out[] = [
                'id' => $cal->getId(),
                'summary' => $cal->getSummary(),
                'primary' => (bool) $cal->getPrimary(),
                'timeZone' => $cal->getTimeZone(),
            ];
        }
        return $out;
    }

    public function listUpcomingEvents(?string $accountEmail, int $hoursAhead = 48, ?array $calendarIds = null): array
    {
        $client = GoogleClientFactory::make($accountEmail);
        $service = new GoogleCalendar($client);

        $timeMin = now()->toRfc3339String();
        $timeMax = now()->addHours($hoursAhead)->toRfc3339String();

        $out = [];
        $targetCalendars = $calendarIds && count($calendarIds) ? $calendarIds : ['primary'];
        foreach ($targetCalendars as $calId) {
            $events = $service->events->listEvents($calId, [
                'timeMin' => $timeMin,
                'timeMax' => $timeMax,
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'maxResults' => 2500,
            ]);

            foreach ($events->getItems() as $event) {
                $start = $event->getStart();
                $end = $event->getEnd();
                $attendees = $event->getAttendees() ?? [];
                $attendee = count($attendees) ? $attendees[0] : null;

                $out[] = [
                    'google_event_id' => $event->getId(),
                    'calendar_id' => $calId,
                    'summary' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'start_at' => $start?->getDateTime() ?: $start?->getDate(),
                    'end_at' => $end?->getDateTime() ?: $end?->getDate(),
                    'timezone' => $start?->getTimeZone(),
                    'attendee_name' => $attendee?->getDisplayName(),
                    'attendee_phone' => null,
                    'hangout_link' => $event->getHangoutLink(),
                    'raw' => $event,
                ];
            }
        }

        return $out;
    }

    public function syncAppointments(array $events): int
    {
        $count = 0;
        foreach ($events as $e) {
            $count++;
            
            // Try to find or create patient from attendee info
            $patientId = $this->findOrCreatePatient($e);
            
            DB::table('appointments')->updateOrInsert(
                ['google_event_id' => $e['google_event_id']],
                [
                    'calendar_id' => $e['calendar_id'],
                    'summary' => $e['summary'],
                    'description' => $e['description'],
                    'start_at' => $this->toDateTime($e['start_at']),
                    'end_at' => $this->toDateTime($e['end_at']),
                    'timezone' => $e['timezone'],
                    'hangout_link' => $e['hangout_link'],
                    'patient_id' => $patientId,
                    'last_synced_at' => now(),
                    'raw_payload' => json_encode($e['raw']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        return $count;
    }
    
    /**
     * Find or create patient from event attendee
     */
    private function findOrCreatePatient(array $event): ?int
    {
        $raw = $event['raw'];
        $attendees = $raw->getAttendees();
        
        if (empty($attendees)) {
            return null;
        }
        
        $attendee = $attendees[0];
        $email = $attendee->getEmail();
        $name = $attendee->getDisplayName() ?? $email;
        
        if (!$email) {
            return null;
        }
        
        // Find or create patient
        $patient = DB::table('patients')->where('email', $email)->first();
        
        if (!$patient) {
            $patientId = DB::table('patients')->insertGetId([
                'name' => $name,
                'email' => $email,
                'phone' => null, // Will be added manually
                'preferred_channel' => 'email', // Default to email
                'consent_email' => false, // Needs manual consent
                'consent_sms' => false,
                'consent_whatsapp' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return $patientId;
        }
        
        return $patient->id;
    }

    private function toDateTime(?string $value): ?string
    {
        if (!$value) return null;
        // If it's a date-only (YYYY-MM-DD), set time to 00:00:00
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::parse($value.' 00:00:00')->toDateTimeString();
        }
        return Carbon::parse($value)->toDateTimeString();
    }
}
