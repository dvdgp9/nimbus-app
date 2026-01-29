<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AppointmentStatusChanged;

class Appointment extends Model
{
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
        'first_session_notified',
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

    public function scopeNeedsReminder($query, int $hoursAhead = 24)
    {
        $now = now();
        $deadline = $now->copy()->addHours($hoursAhead);
        
        return $query->whereBetween('start_at', [$now, $deadline])
                     ->where('nimbus_status', 'pending')
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
        $this->update([
            'nimbus_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Notify professional
        $this->notifyProfessional('cancelled');
    }

    public function isInNext24Hours(): bool
    {
        return $this->start_at->isBetween(now(), now()->addHours(24));
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->start_at->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->start_at->format('H:i');
    }

    public function getSuggestedPatientCodeAttribute(): ?string
    {
        return $this->extractPatientCode($this->summary ?? '');
    }

    public function getSuggestedPatientNameAttribute(): ?string
    {
        return $this->extractPatientName($this->summary ?? '');
    }

    protected function extractPatientCode(string $title): ?string
    {
        $title = trim($title);

        if (preg_match('/^([A-Za-z0-9]+)(?:\s*[-:]\s*|\s+|$)/', $title, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    protected function extractPatientName(string $title): ?string
    {
        $title = trim($title);

        if (preg_match('/^[A-Za-z0-9]+(?:\s*[-:]\s*|\s+)(.*)$/', $title, $matches)) {
            return trim($matches[1]) ?: null;
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
