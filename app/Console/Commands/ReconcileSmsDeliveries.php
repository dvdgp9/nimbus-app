<?php

namespace App\Console\Commands;

use App\Models\Communication;
use App\Services\SmsDeliveryReconciler;
use Illuminate\Console\Command;

class ReconcileSmsDeliveries extends Command
{
    protected $signature = 'nimbus:reconcile-sms {--dry-run : List pending sends without querying or changing anything}';

    protected $description = 'Recover unconfirmed SMS from Acumbamail reports without resending them';

    public function handle(SmsDeliveryReconciler $reconciler): int
    {
        $recovered = 0;
        $pending = 0;
        Communication::where('channel', 'sms')->where('type', 'reminder')->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(2))
            ->with(['patient.user', 'appointment'])
            ->chunkById(100, function ($communications) use ($reconciler, &$recovered, &$pending): void {
                foreach ($communications as $communication) {
                    if (! $this->option('dry-run') && $reconciler->reconcile($communication)) {
                        $recovered++;
                    } else {
                        $pending++;
                    }
                }
            });
        $this->line("SMS recovered: {$recovered}; pending review: {$pending}.");

        return self::SUCCESS;
    }
}
