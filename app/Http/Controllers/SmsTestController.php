<?php

namespace App\Http\Controllers;

use App\Services\AcumbamailService;
use Exception;
use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SmsTestController extends Controller
{
    public function index()
    {
        $credits = null;
        
        try {
            $acumbamail = app(AcumbamailService::class);
            $credits = $acumbamail->getCredits();
        } catch (Exception $e) {
            // Service not configured
        }
        
        return view('sms.test', [
            'credits' => $credits,
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'message' => 'required|string|max:2000',
        ], [
            'phone.required' => 'El número de teléfono es obligatorio.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede superar los 2000 caracteres.',
        ]);

        try {
            $acumbamail = app(AcumbamailService::class);
            $smsId = $acumbamail->sendSMS($validated['phone'], $validated['message']);

            return back()->with('success', "SMS aceptado por Acumbamail. ID: {$smsId}. Comprueba ahora si llega al teléfono.");
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'phone' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            Log::error('Test SMS failed', [
                'user_id' => $request->user()->id,
                'phone' => $validated['phone'],
                'error' => $e->getMessage(),
            ]);

            // Surface the raw provider response (tags/whitespace stripped) so the
            // test tool shows exactly what Acumbamail returned, e.g. the nginx 404.
            $detail = trim(preg_replace('/\s+/', ' ', strip_tags($e->getMessage())));

            return back()
                ->withInput()
                ->withErrors(['sms' => 'No se pudo enviar el SMS. Respuesta de Acumbamail: ' . Str::limit($detail, 300)]);
        }
    }
}
