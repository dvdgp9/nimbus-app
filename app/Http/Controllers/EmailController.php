<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestReminder;

class EmailController extends Controller
{
    public function create()
    {
        return view('email.form');
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
