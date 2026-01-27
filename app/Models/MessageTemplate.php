<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'channel',
        'subject',
        'body',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Available dynamic fields for templates
     */
    public const DYNAMIC_FIELDS = [
        'patient_name' => 'Nombre completo del paciente',
        'patient_first_name' => 'Primer nombre del paciente',
        'patient_email' => 'Email del paciente',
        'appointment_date' => 'Fecha de la cita (ej: Lunes 27 de Enero)',
        'appointment_time' => 'Hora de la cita (ej: 10:00)',
        'appointment_summary' => 'Título/resumen de la cita',
        'professional_name' => 'Nombre del profesional',
        'confirm_link' => 'Enlace de confirmación',
        'cancel_link' => 'Enlace de cancelación',
        'hangout_link' => 'Enlace de videollamada',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Parse template body replacing dynamic fields with actual values
     */
    public function parse(array $data): string
    {
        $body = $this->body;

        foreach (self::DYNAMIC_FIELDS as $field => $description) {
            $placeholder = '{{' . $field . '}}';
            $value = $data[$field] ?? '';
            $body = str_replace($placeholder, $value, $body);
        }

        return $body;
    }

    /**
     * Parse subject (for email templates)
     */
    public function parseSubject(array $data): string
    {
        if (!$this->subject) {
            return '';
        }

        $subject = $this->subject;

        foreach (self::DYNAMIC_FIELDS as $field => $description) {
            $placeholder = '{{' . $field . '}}';
            $value = $data[$field] ?? '';
            $subject = str_replace($placeholder, $value, $subject);
        }

        return $subject;
    }

    /**
     * Get preview with sample data
     */
    public function getPreview(): string
    {
        $sampleData = [
            'patient_name' => 'María García López',
            'patient_first_name' => 'María',
            'patient_email' => 'maria@ejemplo.com',
            'appointment_date' => 'Lunes 27 de Enero de 2026',
            'appointment_time' => '10:00',
            'appointment_summary' => 'Sesión de terapia',
            'professional_name' => 'Dr. Juan Pérez',
            'confirm_link' => 'https://nimbus.app/link/abc123',
            'cancel_link' => 'https://nimbus.app/link/xyz789',
            'hangout_link' => 'https://meet.google.com/abc-defg-hij',
        ];

        return $this->parse($sampleData);
    }

    /**
     * Check if template is for SMS (character limit considerations)
     */
    public function isSms(): bool
    {
        return $this->channel === 'sms';
    }

    /**
     * Check if template is for Email
     */
    public function isEmail(): bool
    {
        return $this->channel === 'email';
    }

    /**
     * Get estimated SMS segments (160 chars per segment)
     */
    public function getSmsSegments(): int
    {
        if (!$this->isSms()) {
            return 0;
        }

        $previewLength = strlen($this->getPreview());
        return (int) ceil($previewLength / 160);
    }
}
