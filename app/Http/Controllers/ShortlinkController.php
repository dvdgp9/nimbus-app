<?php

namespace App\Http\Controllers;

use App\Models\Shortlink;
use App\Models\Appointment;
use Illuminate\Http\Request;

class ShortlinkController extends Controller
{
    /**
     * Handle shortlink action (confirm/cancel/reschedule)
     */
    public function handle(Request $request, string $token)
    {
        // Find and validate shortlink
        $shortlink = Shortlink::where('token', $token)->first();

        if (!$shortlink) {
            return view('shortlinks.error', [
                'message' => 'Enlace no válido',
                'detail' => 'Este enlace no existe o ha sido eliminado.',
            ]);
        }

        // Check if expired
        if ($shortlink->isExpired()) {
            return view('shortlinks.error', [
                'message' => 'Enlace caducado',
                'detail' => 'Este enlace ha expirado. Por favor, contacta con nosotros.',
            ]);
        }

        // Check if already used
        if ($shortlink->used) {
            return view('shortlinks.error', [
                'message' => 'Enlace ya utilizado',
                'detail' => 'Este enlace ya ha sido usado anteriormente.',
            ]);
        }

        // Get appointment
        $appointment = $shortlink->appointment;

        if (!$appointment) {
            return view('shortlinks.error', [
                'message' => 'Cita no encontrada',
                'detail' => 'No se pudo encontrar la cita asociada.',
            ]);
        }

        // Execute action
        $action = $shortlink->action;
        
        switch ($action) {
            case 'confirm':
                $appointment->confirm();
                $shortlink->markAsUsed($request);
                
                return view('shortlinks.success', [
                    'title' => '✅ Cita confirmada',
                    'message' => 'Tu cita ha sido confirmada exitosamente.',
                    'appointment' => $appointment,
                ]);

            case 'cancel':
                $appointment->cancel();
                $shortlink->markAsUsed($request);
                
                return view('shortlinks.success', [
                    'title' => '❌ Cita cancelada',
                    'message' => 'Tu cita ha sido cancelada. Te confirmaremos la cancelación por email.',
                    'appointment' => $appointment,
                ]);

            case 'reschedule':
                // For reschedule, redirect to WhatsApp
                $phone = config('whatsapp.professional_phone');
                $message = urlencode("Hola, necesito reprogramar mi cita: {$appointment->summary} del {$appointment->formatted_date}");
                $whatsappUrl = "https://wa.me/{$phone}?text={$message}";
                
                $shortlink->markAsUsed($request);
                
                return redirect()->away($whatsappUrl);

            default:
                return view('shortlinks.error', [
                    'message' => 'Acción no válida',
                    'detail' => 'La acción solicitada no es válida.',
                ]);
        }
    }
}
