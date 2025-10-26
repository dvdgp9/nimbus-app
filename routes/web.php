<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\EventsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/healthz', function () {
    return response()->json(['status' => 'ok'], 200);
});

Route::get('/email', [EmailController::class, 'create'])->name('email.create');
Route::post('/email', [EmailController::class, 'send'])->name('email.send');

// Google OAuth
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.connect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

// Events endpoints
Route::get('/events', [EventsController::class, 'index'])->name('events.index');
Route::post('/events/sync', [EventsController::class, 'sync'])->name('events.sync');
