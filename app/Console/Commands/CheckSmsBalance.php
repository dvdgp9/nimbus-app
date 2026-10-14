<?php

namespace App\Console\Commands;

use App\Mail\LowSmsBalance;
use App\Models\User;
use App\Services\AcumbamailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Warn the professional when the shared Acumbamail balance runs low, once per
 * drop below the threshold. A recharge above it re-arms the alert.
 */
class CheckSmsBalance extends Command
{
    /** Set while an alert for the current low-balance episode has gone out. */
    public const ALERTED_CACHE_KEY = 'acumbamail:low_sms_alerted_at';

    protected $signature = 'nimbus:check-sms-balance
                            {--dry-run : Simulate without sending}';

    protected $description = 'Email the professional when few SMS are left in Acumbamail';

    public function handle(AcumbamailService $acumbamail): int
    {
        $threshold = (int) config('services.acumbamail.low_sms_threshold', 50);
        $balance = $acumbamail->getSmsBalance();

        if ($balance === null) {
            // Already logged by the service; the next tick will try again.
            $this->error('❌ Could not read the Acumbamail balance.');

            return Command::FAILURE;
        }

        $sms = $balance['sms'];
        $this->line("📊 Saldo: ≈ {$sms} SMS ({$balance['credits']} créditos). Umbral: {$threshold} SMS.");

        if ($sms > $threshold) {
            if (Cache::pull(self::ALERTED_CACHE_KEY) !== null) {
                $this->info('🔄 Balance recovered, alert re-armed.');
                Log::info('Acumbamail SMS balance recovered, low-balance alert re-armed', ['sms' => $sms]);
            }

            return Command::SUCCESS;
        }

        if ($alertedAt = Cache::get(self::ALERTED_CACHE_KEY)) {
            $this->info("ℹ️  Already alerted at {$alertedAt}, waiting for a recharge.");

            return Command::SUCCESS;
        }

        // One shared Acumbamail account: every active professional depends on it.
        $recipients = User::whereNotNull('onboarding_completed_at')
            ->whereNotNull('email')
            ->pluck('email');

        if ($recipients->isEmpty()) {
            $this->warn('⚠️  Low balance but no professional to alert.');
            Log::warning('Low Acumbamail SMS balance but no user to alert', ['sms' => $sms]);

            return Command::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('🔵 [DRY RUN] Would alert: '.$recipients->implode(', '));

            return Command::SUCCESS;
        }

        $sent = 0;
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new LowSmsBalance($sms, $threshold));
                $this->info("✅ Alert sent to {$email}");
                $sent++;
            } catch (\Exception $e) {
                $this->error("❌ {$email}: ".$e->getMessage());
                Log::error('Failed to send low SMS balance alert: '.$e->getMessage(), ['email' => $email]);
            }
        }

        // Only mark once something actually left, so a failed send is retried.
        if ($sent > 0) {
            Cache::forever(self::ALERTED_CACHE_KEY, now()->toDateTimeString());
            Log::info('Alerted about low Acumbamail SMS balance', ['sms' => $sms, 'threshold' => $threshold, 'sent' => $sent]);
        }

        return $sent === $recipients->count() ? Command::SUCCESS : Command::FAILURE;
    }
}
