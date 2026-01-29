<?php

namespace App\Services;

use Google\Service\Calendar as GoogleCalendar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    public function listCalendars(?string $accountEmail, ?int $userId = null): array
    {
        $client = GoogleClientFactory::make($accountEmail, $userId);
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

    public function listUpcomingEvents(?string $accountEmail, int $hoursAhead = 48, ?array $calendarIds = null, ?int $userId = null): array
    {
        $client = GoogleClientFactory::make($accountEmail, $userId);
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
        $detectedCalendars = [];
        foreach ($events as $e) {
            $count++;
            
            // Track event id for later cleanup
            if (!empty($e['google_event_id'])) {
                $googleIds[] = $e['google_event_id'];
            }
            if (!empty($e['calendar_id'])) {
                $detectedCalendars[] = $e['calendar_id'];
            }

            // Try to find or create patient from attendee info
            $patientId = $this->findOrCreatePatient($e, $userId);
            
            // Extract message code from title (last word after last space)
            // Example: "EVTA Cita 1 BP" → code is "BP"
            $messageCode = $this->extractMessageCode($e['summary']);
            
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
                    'message_code' => $messageCode,
                    'last_synced_at' => now(),
                    'raw_payload' => json_encode($e['raw']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Cleanup: remove appointments that no longer exist in Google for these calendars
        $targetCalendars = $calendarIds ?: array_values(array_unique(array_filter($detectedCalendars)));
        if (!empty($targetCalendars)) {
            $now = now();
            $upperBound = $now->copy()->addHours($hoursAhead);

            $query = DB::table('appointments')
                ->whereIn('calendar_id', $targetCalendars)
                ->whereNotNull('google_event_id')
                ->where('start_at', '>=', $now)
                ->where('start_at', '<=', $upperBound);

            if (!empty($googleIds)) {
                $query->whereNotIn('google_event_id', $googleIds);
            }

            $query->delete();
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
     * Extract message code from event title (last word after last space)
     * Example: "EVTA Cita 1 BP" → returns "BP"
     * Example: "Primera sesión Psicóloga Laura" → returns "LAURA" (will be used for first session detection)
     * Returns uppercase code or null if title is empty/single word
     */
    private function extractMessageCode(?string $title): ?string
    {
        if (!$title) {
            return null;
        }
        
        $title = trim($title);
        
        // Split by spaces and get the last word
        $parts = preg_split('/\s+/', $title);
        
        if (count($parts) < 2) {
            // Single word title, no code
            return null;
        }
        
        $lastWord = end($parts);
        
        // Only return if it looks like a code (alphanumeric, 1-10 chars)
        if (preg_match('/^[A-Za-z0-9]{1,10}$/', $lastWord)) {
            return strtoupper($lastWord);
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

    /**
     * Update event title in Google Calendar (prepend prefix)
     * Used for adding "OK - " when patient confirms
     */
    public function updateEventTitle(string $calendarId, string $eventId, string $prefix, ?string $accountEmail = null, ?int $userId = null): bool
    {
        try {
            $client = GoogleClientFactory::make($accountEmail, $userId);
            $service = new GoogleCalendar($client);

            // Get current event
            $event = $service->events->get($calendarId, $eventId);
            $currentTitle = $event->getSummary() ?? '';

            // Don't add prefix if already present
            if (str_starts_with($currentTitle, $prefix)) {
                return true;
            }

            // Update title with prefix
            $event->setSummary($prefix . $currentTitle);
            $service->events->update($calendarId, $eventId, $event);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to update event title: " . $e->getMessage(), [
                'calendar_id' => $calendarId,
                'event_id' => $eventId,
                'prefix' => $prefix,
            ]);
            return false;
        }
    }

    /**
     * Move event to a different date in Google Calendar
     * Used for moving cancelled appointments to Sunday
     */
    public function moveEventToDate(string $calendarId, string $eventId, Carbon $newDate, ?string $accountEmail = null, ?int $userId = null): bool
    {
        try {
            $client = GoogleClientFactory::make($accountEmail, $userId);
            $service = new GoogleCalendar($client);

            // Get current event
            $event = $service->events->get($calendarId, $eventId);
            
            $start = $event->getStart();
            $end = $event->getEnd();
            
            // Calculate new start/end times keeping the same time of day
            if ($start->getDateTime()) {
                // DateTime event (has time)
                $originalStart = Carbon::parse($start->getDateTime());
                $originalEnd = Carbon::parse($end->getDateTime());
                $duration = $originalStart->diffInMinutes($originalEnd);

                $newStart = $newDate->copy()->setTime($originalStart->hour, $originalStart->minute);
                $newEnd = $newStart->copy()->addMinutes($duration);

                $start->setDateTime($newStart->toRfc3339String());
                $end->setDateTime($newEnd->toRfc3339String());
            } else {
                // All-day event
                $start->setDate($newDate->format('Y-m-d'));
                $end->setDate($newDate->format('Y-m-d'));
            }

            $event->setStart($start);
            $event->setEnd($end);
            
            $service->events->update($calendarId, $eventId, $event);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to move event: " . $e->getMessage(), [
                'calendar_id' => $calendarId,
                'event_id' => $eventId,
                'new_date' => $newDate->toDateString(),
            ]);
            return false;
        }
    }
}
