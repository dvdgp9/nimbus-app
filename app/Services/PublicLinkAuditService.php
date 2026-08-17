<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Shortlink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicLinkAuditService
{
    public function record(
        string $message,
        Request $request,
        Shortlink $shortlink,
        Appointment $appointment,
        string $result
    ): void {
        Log::info($message, [
            'appointment_id' => $appointment->id,
            'shortlink_id' => $shortlink->id,
            'action' => $shortlink->action,
            'http_method' => $request->method(),
            'result' => $result,
            'appointment_status' => $appointment->nimbus_status,
            'ip_hash' => $this->hashIp($request->ip()),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 180),
        ]);
    }

    private function hashIp(?string $ip): string
    {
        $key = (string) config('app.key', 'nimbus-public-link-audit');

        return substr(hash_hmac('sha256', $ip ?: 'unknown', $key), 0, 16);
    }
}
