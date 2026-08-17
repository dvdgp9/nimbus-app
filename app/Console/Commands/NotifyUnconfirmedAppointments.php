<?php

namespace App\Console\Commands;

use App\Mail\UnconfirmedAppointments;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * N3: tell the professional which patients have not answered the reminder, while
 * there is still a day left to chase them by hand.
 */
class NotifyUnconfirmedAppointments extends Command
{
    protected $signature = 'nimbus:notify-unconfirmed
                            {--hours=24 : Hours before the session to alert about no answer}
                            {--dry-run : Simulate without sending}';

    protected $description = 'Alert each professional about upcoming sessions the patient has not confirmed or cancelled';

    public function handle(): int
    {
        $hoursAhead = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $appointments = Appointment::awaitingPatientResponse($hoursAhead)
            ->with('patient.user')
            ->orderBy('start_at')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('✅ No unanswered sessions in the next '.$hoursAhead.' hours.');

            return Command::SUCCESS;
        }

        // One digest per professional, so two silent patients never mean two emails.
        $byUser = $appointments->groupBy(fn (Appointment $appointment) => $appointment->patient?->user_id);

        $sent = 0;
        $failed = 0;

        foreach ($byUser as $userId => $userAppointments) {
            $user = $userId ? User::find($userId) : null;

            if (! $user || empty($user->email)) {
                $this->warn("⚠️  Skipping {$userAppointments->count()} session(s): no professional email found.");
                Log::warning('Cannot alert about unconfirmed sessions: no user email.', [
                    'user_id' => $userId,
                    'appointment_ids' => $userAppointments->pluck('id')->all(),
                ]);
                $failed++;
                continue;
            }

            $this->line("📋 {$user->email}: {$userAppointments->count()} sesión(es) sin respuesta");
            foreach ($userAppointments as $appointment) {
                $this->line("   · {$appointment->patient?->name} — {$appointment->formatted_date} {$appointment->formatted_time}");
            }

            if ($dryRun) {
                $this->info('   🔵 [DRY RUN] Would send digest');
                $sent++;
                continue;
            }

            try {
                Mail::to($user->email)->send(new UnconfirmedAppointments($userAppointments->values(), $hoursAhead));

                // Only mark once the digest actually left, so a failed send is
                // retried on the next tick instead of being lost.
                Appointment::whereIn('id', $userAppointments->pluck('id'))
                    ->update(['unconfirmed_alert_sent_at' => now()]);

                $this->info('   ✅ Digest sent');
                Log::info('Alerted professional about unconfirmed sessions', [
                    'user_id' => $user->id,
                    'appointment_ids' => $userAppointments->pluck('id')->all(),
                ]);
                $sent++;
            } catch (\Exception $e) {
                $this->error('   ❌ Error: '.$e->getMessage());
                Log::error('Failed to alert about unconfirmed sessions: '.$e->getMessage(), [
                    'user_id' => $user->id,
                    'appointment_ids' => $userAppointments->pluck('id')->all(),
                ]);
                $failed++;
            }
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📊 Digests enviados: {$sent}");
        if ($failed > 0) {
            $this->error("   ❌ Fallidos: {$failed}");
        }
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
