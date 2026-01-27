<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\CalendarsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShortlinkController;
use App\Http\Controllers\PatientsController;
use App\Http\Controllers\SmsTestController;
use App\Http\Controllers\MessageTemplatesController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/healthz', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Shortlink actions (public for patients)
Route::get('/link/{token}', [ShortlinkController::class, 'handle'])->name('shortlink.handle');

// Legal pages
Route::get('/privacy-policy', function () {
    return view('legal.privacy');
})->name('legal.privacy');

Route::get('/terms-of-service', function () {
    return view('legal.tos');
})->name('legal.tos');

// Google OAuth (public routes for login)
Route::get('/auth/google/login', [GoogleAuthController::class, 'loginRedirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/', HomeController::class)->name('home');
    
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Google OAuth (calendar connection - requires auth)
    Route::get('/auth/google', [GoogleAuthController::class, 'connect'])->name('google.connect');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');

    // Events endpoints
    Route::get('/events', [EventsController::class, 'index'])->name('events.index');
    Route::post('/events/sync', [EventsController::class, 'sync'])->name('events.sync');
    Route::post('/events/reminders', [EventsController::class, 'sendReminders'])->name('events.reminders');

    // Calendars selection
    Route::get('/calendars', [CalendarsController::class, 'index'])->name('calendars.index');
    Route::post('/calendars', [CalendarsController::class, 'store'])->name('calendars.store');

    // Patients management
    Route::resource('patients', PatientsController::class);

    // Email
    Route::get('/email', [EmailController::class, 'create'])->name('email.create');
    Route::post('/email', [EmailController::class, 'send'])->name('email.send');

    // SMS Test
    Route::get('/sms/test', [SmsTestController::class, 'index'])->name('sms.test');
    Route::post('/sms/test', [SmsTestController::class, 'send'])->name('sms.send');

    // Message Templates
    Route::resource('templates', MessageTemplatesController::class)->except(['show']);
    Route::post('/templates/{template}/duplicate', [MessageTemplatesController::class, 'duplicate'])->name('templates.duplicate');
    Route::post('/templates/{template}/set-default', [MessageTemplatesController::class, 'setDefault'])->name('templates.setDefault');
    Route::post('/templates/preview', [MessageTemplatesController::class, 'preview'])->name('templates.preview');
});

require __DIR__.'/auth.php';
