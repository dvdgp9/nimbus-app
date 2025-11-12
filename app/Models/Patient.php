<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'preferred_channel',
        'consent_email',
        'consent_sms',
        'consent_whatsapp',
        'consent_date',
        'notes',
    ];

    protected $casts = [
        'consent_email' => 'boolean',
        'consent_sms' => 'boolean',
        'consent_whatsapp' => 'boolean',
        'consent_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    /**
     * Helper methods
     */
    public function hasConsentFor(string $channel): bool
    {
        return match($channel) {
            'email' => $this->consent_email,
            'sms' => $this->consent_sms,
            'whatsapp' => $this->consent_whatsapp,
            default => false,
        };
    }

    public function getContactForChannel(string $channel): ?string
    {
        return match($channel) {
            'email' => $this->email,
            'sms', 'whatsapp' => $this->phone,
            default => null,
        };
    }

    public function giveConsent(array $channels): void
    {
        $updates = ['consent_date' => now()];
        
        foreach ($channels as $channel) {
            $field = "consent_{$channel}";
            if (in_array($field, $this->fillable)) {
                $updates[$field] = true;
            }
        }
        
        $this->update($updates);
    }
}
