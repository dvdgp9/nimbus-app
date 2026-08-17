<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\FirstSessionService;
use App\Services\NotificationService;
use App\Services\YellowAppointmentReviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCalendars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nimbus:sync-calendars 
                            {--user= : Sync only for a specific user ID}
                            {--hours=720 : Hours ahead to sync (default 30 days)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Calendar events for all users with connected calendars';

    public function __construct(
        private GoogleCalendarService $calendar,
        private FirstSessionService $firstSessionService,
        private NotificationService $notificationService,
        private YellowAppointmentReviewService $yellowReviews,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificUserId = $this->option('user');
        $hoursAhead = (int) $this->option('hours');

        $this->info("🔄 Starting calendar sync (next {$hoursAhead} hours / " . round($hoursAhead / 24) . " days)...");

        // Get all users with Google tokens and enabled calendars
        $query = DB::table('google_tokens')
            ->select('google_tokens.user_id', 'google_tokens.account_email')
            ->join('connected_calendars', function ($join) {
                $join->on('google_tokens.user_id', '=', 'connected_calendars.user_id')
                    ->on('google_tokens.account_email', '=', 'connected_calendars.account_email');
            })
            ->where('connected_calendars.enabled', 1)
            ->groupBy('google_tokens.user_id', 'google_tokens.account_email');

        if ($specificUserId) {
            $query->where('google_tokens.user_id', $specificUserId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info('ℹ️  No users with connected calendars found.');
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$accounts->count()} account(s) to sync");
        $this->newLine();

        $totalSynced = 0;
        $failedAccounts = 0;

        foreach ($accounts as $account) {
            // Skip users who haven't completed onboarding
            $user = User::find($account->user_id);
            if (!$user || !$user->hasCompletedOnboarding()) {
                $this->line("⏭️  User #{$account->user_id} - Skipping (onboarding not completed)");
                continue;
            }

            $this->line("👤 User #{$account->user_id} ({$account->account_email})");

            // Get enabled calendar IDs for this user/account
            $calendarIds = DB::table('connected_calendars')
                ->where('user_id', $account->user_id)
                ->where('account_email', $account->account_email)
                ->where('enabled', 1)
                ->pluck('calendar_id')
                ->all();

            if (empty($calendarIds)) {
                $this->warn("   ⚠️  No enabled calendars, skipping");
                continue;
            }

            try {
                $events = $this->calendar->listUpcomingEvents(
                    $account->account_email,
                    $hoursAhead,
                    $calendarIds,
                    $account->user_id
                );

                $count = $this->calendar->syncAppointments(
                    $events,
                    $account->user_id,
                    $calendarIds,
                    $hoursAhead
                );

                $this->info("   ✅ Synced {$count} events from " . count($calendarIds) . " calendar(s)");
                $totalSynced += $count;

                // Process first sessions for this user
                $firstSessionsProcessed = $this->processFirstSessions($account->user_id);
                if ($firstSessionsProcessed > 0) {
                    $this->info("   📧 Notified {$firstSessionsProcessed} first session(s)");
                }

                // Process unknown patient codes for this user
                $unknownCodesProcessed = $this->processUnknownPatientCodes($account->user_id);
                if ($unknownCodesProcessed > 0) {
                    $this->info("   ⚠️ Notified {$unknownCodesProcessed} unknown patient code(s)");
                }

                // N1: insist on codes that are still unresolved close to the session
                $escalated = $this->escalateUnknownPatientCodes($account->user_id);
                if ($escalated > 0) {
                    $this->warn("   🚨 Escalated {$escalated} unresolved patient code(s)");
                }

                $yellowReviewsProcessed = $this->processYellowAppointments($account->user_id);
                if ($yellowReviewsProcessed > 0) {
                    $this->info("   Notified {$yellowReviewsProcessed} yellow appointment(s)");
                }

            } catch (\Google\Service\Exception $e) {
                $error = json_decode($e->getMessage(), true);
                $errorCode = $error['error']['code'] ?? 'unknown';
                $errorMessage = $error['error']['message'] ?? $e->getMessage();
                
                $this->error("   ❌ Google API error ({$errorCode}): {$errorMessage}");
                Log::error("Calendar sync failed for user {$account->user_id}", [
                    'account_email' => $account->account_email,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                ]);
                $failedAccounts++;

            } catch (\Exception $e) {
                $this->error("   ❌ Error: " . $e->getMessage());
                Log::error("Calendar sync failed for user {$account->user_id}", [
                    'account_email' => $account->account_email,
                    'error' => $e->getMessage(),
                ]);
                $failedAccounts++;
            }

            $this->newLine();
        }

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📊 Sync Summary:");
        $this->info("   📅 Total events synced: {$totalSynced}");
        $this->info("   👤 Accounts processed: {$accounts->count()}");
        
        if ($failedAccounts > 0) {
            $this->error("   ❌ Failed accounts: {$failedAccounts}");
        }
        
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return $failedAccounts > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process first sessions for a user - detect and send notification emails
     */
    protected function processFirstSessions(int $userId): int
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        // Find appointments that look like first sessions and haven't been notified
        $appointments = Appointment::where('first_session_notified', false)
            ->where('summary', 'like', FirstSessionService::FIRST_SESSION_SQL_LIKE)
            ->whereNull('patient_id')
            ->whereIn('calendar_id', function ($query) use ($userId) {
                $query->select('calendar_id')
                    ->from('connected_calendars')
                    ->where('user_id', $userId)
                    ->where('enabled', 1);
            })
            ->get();

        $processed = 0;
        foreach ($appointments as $appointment) {
            if ($this->firstSessionService->isFirstSession($appointment->summary ?? '')) {
                if ($this->firstSessionService->processFirstSession($appointment, $user)) {
                    $processed++;
                }
            }
        }

        return $processed;
    }

    /**
     * Process appointments with unknown patient codes - detect and send notification emails
     */
    protected function processUnknownPatientCodes(int $userId): int
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        // Find appointments that:
        // 1. Have no patient assigned
        // 2. Have NOT been notified yet about unknown patient code
        // 3. Are NOT first sessions (those are handled separately)
        // 4. Belong to this user's calendars
        $appointments = Appointment::whereNull('patient_id')
            ->where('unknown_patient_notified', false)
            ->where('start_at', '>', now()) // Only future appointments
            ->where(function ($query) {
                // Exclude first sessions
                $query->where('summary', 'not like', FirstSessionService::FIRST_SESSION_SQL_LIKE);
            })
            ->whereIn('calendar_id', function ($query) use ($userId) {
                $query->select('calendar_id')
                    ->from('connected_calendars')
                    ->where('user_id', $userId)
                    ->where('enabled', 1);
            })
            ->get();

        $processed = 0;
        foreach ($appointments as $appointment) {
            $suggestedCode = $appointment->suggested_patient_code;

            // N2: titles with no readable code used to be dropped silently here.
            // They are now notified too, but with two guards against noise, since
            // "no code" also describes personal events sitting in a work calendar:
            // all-day events are never sessions, and we only warn once the event
            // is close enough that she is unlikely to still be renaming it.
            if (! $suggestedCode) {
                if ($appointment->isAllDay()) {
                    continue;
                }

                if ($appointment->start_at->gt(now()->addDays(Appointment::UNRECOGNIZED_NOTICE_DAYS))) {
                    continue;
                }
            }

            if ($this->notificationService->notifyUnknownPatientCode($appointment, $suggestedCode)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * N1: send the urgent second notice for patient codes that were already
     * reported but still do not exist, once the session is close enough.
     */
    protected function escalateUnknownPatientCodes(int $userId): int
    {
        $appointments = Appointment::whereNull('patient_id')
            ->where('unknown_patient_notified', true)
            ->whereNull('unknown_patient_escalated_at')
            ->where('start_at', '>', now())
            ->where('start_at', '<=', now()->addHours(Appointment::UNKNOWN_PATIENT_ESCALATION_HOURS))
            ->where('summary', 'not like', FirstSessionService::FIRST_SESSION_SQL_LIKE)
            ->whereIn('calendar_id', function ($query) use ($userId) {
                $query->select('calendar_id')
                    ->from('connected_calendars')
                    ->where('user_id', $userId)
                    ->where('enabled', 1);
            })
            ->get();

        $escalated = 0;
        foreach ($appointments as $appointment) {
            // N2: escalate whatever got a first notice, code or no code. Anything
            // that never got one cannot reach here (the query requires the flag).
            if ($this->notificationService->escalateUnknownPatientCode($appointment, $appointment->suggested_patient_code)) {
                $escalated++;
            }
        }

        return $escalated;
    }

    protected function processYellowAppointments(int $userId): int
    {
        $user = User::find($userId);
        if (! $user) {
            return 0;
        }

        $appointments = Appointment::query()
            ->with('patient.user')
            ->where('google_color_id', Appointment::GOOGLE_YELLOW_COLOR_ID)
            ->whereNull('professional_review_notified_at')
            ->whereNull('professional_reviewed_at')
            ->whereNull('reminder_sent_at')
            ->where('nimbus_status', 'pending')
            ->whereNotNull('patient_id')
            ->where('start_at', '>', now())
            ->whereIn('calendar_id', function ($query) use ($userId) {
                $query->select('calendar_id')
                    ->from('connected_calendars')
                    ->where('user_id', $userId)
                    ->where('enabled', 1);
            })
            ->get();

        return $appointments->filter(
            fn (Appointment $appointment) => $this->yellowReviews->notify($appointment, $user)
        )->count();
    }
}
