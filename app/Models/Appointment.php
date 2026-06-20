<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Mail\AppointmentStatusChanged;

class Appointment extends Model
{
    public const GOOGLE_YELLOW_COLOR_ID = '5';

    protected $fillable = [
        'google_event_id',
        'calendar_id',
        'summary',
        'description',
        'start_at',
        'end_at',
        'timezone',
        'hangout_link',
        'patient_id',
        'message_code',
        'google_color_id',
        'first_session_notified',
        'unknown_patient_notified',
        'professional_review_notified_at',
        'professional_reviewed_at',
        'professional_review_decision',
        'nimbus_status',
        'reminder_sent_at',
        'confirmed_at',
        'cancelled_at',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'professional_review_notified_at' => 'datetime',
        'professional_reviewed_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    /**
     * Relationships
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function shortlinks(): HasMany
    {
        return $this->hasMany(Shortlink::class);
    }

    /**
     * Query scopes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>=', now())
                     ->orderBy('start_at', 'asc');
    }

    public function scopeNeedsReminder($query, int $hoursBefore = 48, int $windowMinutes = 15)
    {
        // BUG-B3: self-healing window. Instead of a fragile 15-minute slot exactly
        // $hoursBefore hours before start_at, we pick any appointment that is due
        // within the next $hoursBefore hours and has not been notified yet.
        // This way, if a cron tick is missed, the next one catches up.
        //
        // We keep the "don't notify last-minute appointments" rule (created at least
        // 24h before the appointment), to preserve previous business behaviour.
        return $query
            ->where('start_at', '>', now())
            ->where('start_at', '<=', now()->addHours($hoursBefore))
            ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, start_at) >= ?', [24])
            ->where('nimbus_status', 'pending')
            ->where(function ($query) {
                $query->whereNull('google_color_id')
                    ->orWhere('google_color_id', '!=', self::GOOGLE_YELLOW_COLOR_ID)
                    ->orWhere('professional_review_decision', 'confirmed');
            })
            ->whereNull('reminder_sent_at');
    }

    public function scopeWithPatient($query)
    {
        return $query->whereNotNull('patient_id')
                     ->with('patient');
    }

    /**
     * Helper methods
     */
    public function canSendReminder(): bool
    {
        return $this->nimbus_status === 'pending'
            && is_null($this->reminder_sent_at)
            && $this->start_at->isFuture();
    }

    public function requiresProfessionalReview(): bool
    {
        return $this->google_color_id === self::GOOGLE_YELLOW_COLOR_ID
            && $this->professional_review_decision !== 'confirmed';
    }

    public function markReminderSent(): void
    {
        $this->update([
            'nimbus_status' => 'reminder_sent',
            'reminder_sent_at' => now(),
        ]);
    }

    public function confirm(): void
    {
        $this->update([
            'nimbus_status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Update event title in Google Calendar with "OK - " prefix
        $this->updateGoogleCalendarTitle('OK - ');

        // Notify professional
        $this->notifyProfessional('confirmed');
    }

    /**
     * Update the event title in Google Calendar
     */
    protected function updateGoogleCalendarTitle(string $prefix): void
    {
        if (!$this->google_event_id || !$this->calendar_id || !$this->patient) {
            return;
        }

        $user = $this->patient->user;
        if (!$user) {
            return;
        }

        // Get the user's Google account email
        $googleToken = \Illuminate\Support\Facades\DB::table('google_tokens')
            ->where('user_id', $user->id)
            ->first();

        if (!$googleToken) {
            return;
        }

        try {
            $calendarService = app(\App\Services\GoogleCalendarService::class);
            $calendarService->updateEventTitle(
                $this->calendar_id,
                $this->google_event_id,
                $prefix,
                $googleToken->account_email,
                $user->id
            );
        } catch (\Exception $e) {
            Log::error("Failed to update Google Calendar event title: " . $e->getMessage());
        }
    }

    public function cancel(): void
    {
        $wasConfirmed = $this->nimbus_status === 'confirmed';

        $this->update([
            'nimbus_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // BUG-B2: if the appointment had been confirmed previously, its Google
        // Calendar title was prefixed with "OK - ". Now that it's cancelled,
        // remove the prefix so the agenda reflects reality.
        if ($wasConfirmed) {
            $this->removeGoogleCalendarTitlePrefix('OK - ');
        }

        // Notify professional
        $this->notifyProfessional('cancelled');
    }

    /**
     * Remove a leading prefix from the event title in Google Calendar.
     */
    protected function removeGoogleCalendarTitlePrefix(string $prefix): void
    {
        if (!$this->google_event_id || !$this->calendar_id || !$this->patient) {
            return;
        }

        $user = $this->patient->user;
        if (!$user) {
            return;
        }

        $googleToken = \Illuminate\Support\Facades\DB::table('google_tokens')
            ->where('user_id', $user->id)
            ->first();

        if (!$googleToken) {
            return;
        }

        try {
            app(\App\Services\GoogleCalendarService::class)->removeEventTitlePrefix(
                $this->calendar_id,
                $this->google_event_id,
                $prefix,
                $googleToken->account_email,
                $user->id
            );
        } catch (\Exception $e) {
            Log::error("Failed to remove Google Calendar event title prefix: " . $e->getMessage());
        }
    }

    public function isInNext24Hours(): bool
    {
        return $this->start_at->isBetween(now(), now()->addHours(24));
    }

    public function getFormattedDateAttribute(): string
    {
        $date = $this->start_at->locale('es')->isoFormat('dddd D [de] MMMM');

        return Str::ucfirst($date);
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->start_at->format('H:i');
    }

    public function getSuggestedPatientCodeAttribute(): ?string
    {
        return $this->extractPatientCode($this->summary ?? '');
    }

    public function getSuggestedMessageCodeAttribute(): ?string
    {
        return $this->extractMessageCode($this->summary ?? '');
    }

    public function getSuggestedPatientNameAttribute(): ?string
    {
        return $this->extractPatientName($this->summary ?? '');
    }

    protected function extractPatientCode(string $title): ?string
    {
        $title = trim($title);

        // Remove "OK - " prefix if present
        $title = preg_replace('/^OK\s*-\s*/i', '', $title);

        if (preg_match('/^([A-Za-z0-9]+)(?:\s*[-:]\s*|\s+|$)/', $title, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    protected function extractPatientName(string $title): ?string
    {
        $title = trim($title);

        // Remove "OK - " prefix if present
        $title = preg_replace('/^OK\s*-\s*/i', '', $title);

        if (preg_match('/^[A-Za-z0-9]+(?:\s*[-:]\s*|\s+)(.*)$/', $title, $matches)) {
            return trim($matches[1]) ?: null;
        }

        return null;
    }

    protected function extractMessageCode(?string $title): ?string
    {
        if (!$title) {
            return null;
        }

        $title = trim($title);
        $parts = preg_split('/\s+/', $title);

        if (count($parts) < 2) {
            return null;
        }

        $lastWord = end($parts);

        if (preg_match('/^[A-Za-z0-9]{1,10}$/', $lastWord)) {
            return strtoupper($lastWord);
        }

        return null;
    }

    /**
     * Notify professional about patient action
     */
    protected function notifyProfessional(string $action): void
    {
        if (!$this->patient || !$this->patient->user) {
            Log::warning("Cannot notify professional: appointment {$this->id} has no patient or user");
            return;
        }

        try {
            Mail::to($this->patient->user->email)->send(
                new AppointmentStatusChanged($this, $this->patient, $action)
            );
            
            Log::info("Professional notified: appointment {$this->id} was {$action} by patient {$this->patient->id}");
        } catch (\Exception $e) {
            Log::error("Failed to notify professional: " . $e->getMessage());
        }
    }
}
