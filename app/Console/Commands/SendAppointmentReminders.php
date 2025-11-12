<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nimbus:send-reminders 
                            {--hours=24 : Hours ahead to check for appointments}
                            {--dry-run : Simulate without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for upcoming appointments';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hoursAhead = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Searching for appointments in the next {$hoursAhead} hours...");

        // Get appointments that need reminders
        $appointments = Appointment::needsReminder($hoursAhead)
            ->withPatient()
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('✅ No appointments found that need reminders.');
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$appointments->count()} appointment(s) that need reminders:");
        $this->newLine();

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;

            if (!$patient) {
                $this->warn("⚠️  Skipping appointment #{$appointment->id} - No patient assigned");
                $skipped++;
                continue;
            }

            $this->line("📅 {$appointment->summary}");
            $this->line("   Patient: {$patient->name} ({$patient->email})");
            $this->line("   Date: {$appointment->formatted_date} at {$appointment->formatted_time}");
            $this->line("   Channel: {$patient->preferred_channel}");

            if ($dryRun) {
                $this->info("   🔵 [DRY RUN] Would send reminder");
                $sent++;
            } else {
                try {
                    $success = $this->notificationService->sendReminder($appointment);
                    
                    if ($success) {
                        $this->info("   ✅ Reminder sent successfully");
                        $sent++;
                    } else {
                        $this->error("   ❌ Failed to send reminder");
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ Error: " . $e->getMessage());
                    Log::error("Reminder command error", [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            $this->newLine();
        }

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📊 Summary:");
        $this->info("   ✅ Sent: {$sent}");
        
        if ($failed > 0) {
            $this->error("   ❌ Failed: {$failed}");
        }
        
        if ($skipped > 0) {
            $this->warn("   ⚠️  Skipped: {$skipped}");
        }
        
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
