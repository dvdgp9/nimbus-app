<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Shortlink extends Model
{
    protected $fillable = [
        'appointment_id',
        'token',
        'action',
        'expires_at',
        'used',
        'used_at',
        'used_ip',
        'used_user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
        'used_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Generate a secure token
     */
    public static function generateToken(): string
    {
        return Str::random(32) . '-' . bin2hex(random_bytes(16));
    }

    /**
     * Create shortlink for appointment
     */
    public static function createForAppointment(Appointment $appointment, string $action, int $expiresInHours = 72): self
    {
        return self::create([
            'appointment_id' => $appointment->id,
            'token' => self::generateToken(),
            'action' => $action,
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }

    /**
     * Helper methods
     */
    public function isValid(): bool
    {
        if (in_array($this->action, ['confirm', 'cancel'], true)) {
            return true;
        }

        return !$this->used && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        // Patient confirmation/cancellation links remain valid. `used` and
        // `expires_at` are retained as audit data for existing records, but do
        // not invalidate these idempotent actions.
        if (in_array($this->action, ['confirm', 'cancel'], true)) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function markAsUsed(string $ip, string $userAgent): void
    {
        if ($this->used) {
            return;
        }

        $this->update([
            'used' => true,
            'used_at' => now(),
            'used_ip' => $ip,
            'used_user_agent' => $userAgent,
        ]);
    }

    public function getUrl(): string
    {
        return url('/link/' . $this->token);
    }

    /**
     * Scopes
     */
    public function scopeValid($query)
    {
        return $query->where(function ($query) {
            $query->whereIn('action', ['confirm', 'cancel'])
                ->orWhere(function ($query) {
                    $query->where('used', false)
                        ->where('expires_at', '>', now());
                });
        });
    }
}
