<?php

namespace App\Http\Controllers;

use App\Services\AcumbamailService;
use Exception;
use Illuminate\Http\Request;

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
            'message' => 'required|string|max:160',
        ], [
            'phone.required' => 'El número de teléfono es obligatorio.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede superar los 160 caracteres.',
        ]);

        try {
            $acumbamail = app(AcumbamailService::class);
            $smsId = $acumbamail->sendSMS($validated['phone'], $validated['message']);

            return back()->with('success', "SMS enviado correctamente. ID: {$smsId}");
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['sms' => 'Error al enviar SMS: ' . $e->getMessage()]);
        }
    }
}
