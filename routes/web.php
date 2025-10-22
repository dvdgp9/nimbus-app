<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/healthz', function () {
    return response()->json(['status' => 'ok'], 200);
});

Route::get('/email', [EmailController::class, 'create'])->name('email.create');
Route::post('/email', [EmailController::class, 'send'])->name('email.send');
