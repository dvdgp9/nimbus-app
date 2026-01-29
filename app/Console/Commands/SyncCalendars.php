<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\FirstSessionService;
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
        private FirstSessionService $firstSessionService
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
            ->where('summary', 'like', '%' . FirstSessionService::FIRST_SESSION_TITLE . '%')
            ->whereHas('patient', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }, '=', 0) // No patient assigned yet
            ->orWhere(function ($query) use ($userId) {
                $query->where('first_session_notified', false)
                    ->where('summary', 'like', '%' . FirstSessionService::FIRST_SESSION_TITLE . '%')
                    ->whereNull('patient_id');
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
            if ($this->firstSessionService->isFirstSession($appointment->summary ?? '')) {
                if ($this->firstSessionService->processFirstSession($appointment, $user)) {
                    $processed++;
                }
            }
        }

        return $processed;
    }
}
