<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\CalendarsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShortlinkController;
use App\Http\Controllers\PatientsController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/healthz', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Shortlink actions (public for patients)
Route::get('/link/{token}', [ShortlinkController::class, 'handle'])->name('shortlink.handle');

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/', HomeController::class)->name('home');
    
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Google OAuth
    Route::get('/auth/google', [GoogleAuthController::class, 'connect'])->name('google.connect');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

    // Events endpoints
    Route::get('/events', [EventsController::class, 'index'])->name('events.index');
    Route::post('/events/sync', [EventsController::class, 'sync'])->name('events.sync');

    // Calendars selection
    Route::get('/calendars', [CalendarsController::class, 'index'])->name('calendars.index');
    Route::post('/calendars', [CalendarsController::class, 'store'])->name('calendars.store');

    // Patients management
    Route::resource('patients', PatientsController::class);

    // Email
    Route::get('/email', [EmailController::class, 'create'])->name('email.create');
    Route::post('/email', [EmailController::class, 'send'])->name('email.send');
});

require __DIR__.'/auth.php';
