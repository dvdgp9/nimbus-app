<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestReminder;

class EmailController extends Controller
{
    public function create()
    {
        $defaultMessage = "Hola {{nombre}},\n\nTe recordamos tu sesión programada para el {{fecha}} a las {{hora}} (zona horaria: {{tz}}).\n\nEnlace de la videollamada: {{enlace}}\n\nSi necesitas reprogramar o cancelar, por favor responde a este correo.\n\nGracias,\nEquipo Nimbus";
        return view('email.form', compact('defaultMessage'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Mail::to($data['to'])->send(new TestReminder($data['subject'], $data['message']));

        return back()->with('status', 'Correo enviado correctamente a ' . $data['to']);
    }
}
