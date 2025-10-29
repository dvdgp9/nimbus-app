<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\CalendarsController;
use App\Http\Controllers\HomeController;

Route::get('/', HomeController::class)->name('home');

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

// Calendars selection
Route::get('/calendars', [CalendarsController::class, 'index'])->name('calendars.index');
Route::post('/calendars', [CalendarsController::class, 'store'])->name('calendars.store');
