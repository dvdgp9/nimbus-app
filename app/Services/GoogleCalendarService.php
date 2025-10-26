<?php

namespace App\Services;

use Google\Service\Calendar as GoogleCalendar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    public function listUpcomingEvents(?string $accountEmail, int $hoursAhead = 48): array
    {
        $client = GoogleClientFactory::make($accountEmail);
        $service = new GoogleCalendar($client);

        $timeMin = now()->toRfc3339String();
        $timeMax = now()->addHours($hoursAhead)->toRfc3339String();

        $events = $service->events->listEvents('primary', [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'maxResults' => 2500,
        ]);

        $out = [];
        foreach ($events->getItems() as $event) {
            $start = $event->getStart();
            $end = $event->getEnd();
            $attendees = $event->getAttendees() ?? [];
            $attendee = count($attendees) ? $attendees[0] : null;

            $out[] = [
                'google_event_id' => $event->getId(),
                'calendar_id' => $event->getOrganizer()?->getEmail() ?? 'primary',
                'summary' => $event->getSummary(),
                'description' => $event->getDescription(),
                'start_at' => $start?->getDateTime() ?: $start?->getDate(),
                'end_at' => $end?->getDateTime() ?: $end?->getDate(),
                'timezone' => $start?->getTimeZone(),
                'attendee_name' => $attendee?->getDisplayName(),
                'attendee_phone' => null, // extraer desde description si se define un patrón
                'hangout_link' => $event->getHangoutLink(),
                'raw' => $event,
            ];
        }

        return $out;
    }

    public function syncAppointments(array $events): int
    {
        $count = 0;
        foreach ($events as $e) {
            $count++;
            DB::table('appointments')->updateOrInsert(
                ['google_event_id' => $e['google_event_id']],
                [
                    'calendar_id' => $e['calendar_id'],
                    'summary' => $e['summary'],
                    'description' => $e['description'],
                    'start_at' => $this->toDateTime($e['start_at']),
                    'end_at' => $this->toDateTime($e['end_at']),
                    'timezone' => $e['timezone'],
                    'attendee_name' => $e['attendee_name'],
                    'attendee_phone' => $e['attendee_phone'],
                    'hangout_link' => $e['hangout_link'],
                    'last_synced_at' => now(),
                    'raw_payload' => json_encode($e['raw']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        return $count;
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
