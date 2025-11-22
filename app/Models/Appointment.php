<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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
        'message_type',
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
    }

    public function cancel(): void
    {
        $this->update([
            'nimbus_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
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
}
