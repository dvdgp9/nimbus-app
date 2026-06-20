<?php

namespace App\Services;

use App\Mail\YellowAppointmentReview;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class YellowAppointmentReviewService
{
    public function __construct(
        private NotificationService $notifications,
        private GoogleCalendarService $calendar,
    ) {}

    public function notify(Appointment $appointment, User $user): bool
    {
        if (
            ! $appointment->requiresProfessionalReview()
            || $appointment->professional_review_notified_at
            || $appointment->reminder_sent_at
            || ! $appointment->patient
            || $appointment->start_at->isPast()
        ) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new YellowAppointmentReview($appointment));
            $appointment->update(['professional_review_notified_at' => now()]);
            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to notify professional about yellow appointment.', [
                'appointment_id' => $appointment->id,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    public function confirm(Appointment $appointment): bool
    {
        if ($appointment->professional_review_decision === 'confirmed') {
            return true;
        }
        if (! $appointment->requiresProfessionalReview() || ! $appointment->patient) {
            return false;
        }

        $appointment->update([
            'professional_review_decision' => 'confirmed',
            'professional_reviewed_at' => now(),
        ]);

        if ($this->notifications->sendReminder($appointment)) {
            return true;
        }

        $appointment->update([
            'professional_review_decision' => null,
            'professional_reviewed_at' => null,
        ]);

        return false;
    }

    public function cancel(Appointment $appointment): bool
    {
        if ($appointment->professional_review_decision === 'cancelled') {
            return true;
        }
        if (! $appointment->requiresProfessionalReview() || ! $appointment->patient) {
            return false;
        }

        $user = $appointment->patient->user;
        $googleToken = $user
            ? DB::table('google_tokens')->where('user_id', $user->id)->first()
            : null;

        if (! $googleToken || ! $appointment->google_event_id || ! $appointment->calendar_id) {
            return false;
        }

        $sunday = $appointment->start_at->copy()->endOfWeek(Carbon::SUNDAY);
        if (! $this->calendar->moveEventToDate(
            $appointment->calendar_id,
            $appointment->google_event_id,
            $sunday,
            $googleToken->account_email,
            $user->id,
        )) {
            return false;
        }

        $duration = $appointment->start_at->diffInMinutes($appointment->end_at);
        $newStart = $sunday->copy()->setTime($appointment->start_at->hour, $appointment->start_at->minute);

        $appointment->update([
            'start_at' => $newStart,
            'end_at' => $newStart->copy()->addMinutes($duration),
            'nimbus_status' => 'cancelled_acknowledged',
            'cancelled_at' => now(),
            'professional_review_decision' => 'cancelled',
            'professional_reviewed_at' => now(),
        ]);

        return true;
    }
}
