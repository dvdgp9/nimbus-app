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

    public function syncAppointments(array $events, ?int $userId = null, ?array $calendarIds = null, int $hoursAhead = 336): int
    {
        $count = 0;
        $googleIds = [];
        foreach ($events as $e) {
            $count++;
            
            // Track event id for later cleanup
            if (!empty($e['google_event_id'])) {
                $googleIds[] = $e['google_event_id'];
            }

            // Try to find or create patient from attendee info
            $patientId = $this->findOrCreatePatient($e, $userId);
            
            // Extract message type from description (#MSG1, #MSG2, #MSG3, #MSG4)
            $messageType = $this->extractMessageType($e['description']);
            
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
                    'message_type' => $messageType,
                    'last_synced_at' => now(),
                    'raw_payload' => json_encode($e['raw']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Cleanup: remove appointments that no longer exist in Google for these calendars
        if (!empty($calendarIds) && !empty($googleIds)) {
            DB::table('appointments')
                ->whereIn('calendar_id', $calendarIds)
                ->whereNotNull('google_event_id')
                ->where('start_at', '>=', now())
                ->where('start_at', '<=', now()->addHours($hoursAhead))
                ->whereNotIn('google_event_id', $googleIds)
                ->delete();
        }

        return $count;
    }
    
    /**
     * Find patient by code extracted from event title
     * The title should start with the patient code (e.g., "P123 - Consulta")
     */
    private function findOrCreatePatient(array $event, ?int $userId = null): ?int
    {
        $title = $event['summary'] ?? '';
        
        // Extract patient code from title
        // Patterns supported:
        // - "P123 - Consulta" → P123
        // - "P123: Consulta" → P123
        // - "P123 Consulta" → P123
        // - Just "P123" → P123
        $code = $this->extractPatientCode($title);
        
        if (!$code) {
            // No code found in title
            return null;
        }
        
        // Find patient by code within the given user (if provided)
        $query = DB::table('patients')->where('code', $code);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $patient = $query->first();
        
        if ($patient) {
            return $patient->id;
        }
        
        // Patient not found - appointment will be marked as unassigned
        return null;
    }
    
    /**
     * Extract patient code from event title
     * Supports formats: "CODE - Text", "CODE: Text", "CODE Text", or just "CODE"
     */
    private function extractPatientCode(string $title): ?string
    {
        // Trim whitespace
        $title = trim($title);
        
        // Try to match a code at the beginning
        // Pattern: alphanumeric code followed by separator (-, :, space) or end of string
        if (preg_match('/^([A-Za-z0-9]+)(?:\s*[-:]\s*|\s+|$)/', $title, $matches)) {
            return strtoupper($matches[1]); // Normalize to uppercase
        }
        
        return null;
    }

    /**
     * Extract message type from event description
     * Looks for #MSG1, #MSG2, #MSG3, or #MSG4 tags
     * Returns integer 1-4 or null if not found
     */
    private function extractMessageType(?string $description): ?int
    {
        if (!$description) {
            return null;
        }
        
        // Search for #MSG followed by a number (1-4)
        if (preg_match('/#MSG([1-4])\b/i', $description, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
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
