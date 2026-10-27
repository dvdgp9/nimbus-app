<?php

namespace App\Services;

use App\Mail\UnconfirmedSmsDelivery;
use App\Models\Communication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SmsDeliveryReconciler
{
    public const REVIEW_AFTER_MINUTES = 30;

    private const NOTIFIED_MARKER = '[sms-review-notified]';

    /** Only reads provider reports and updates history; never sends an SMS. */
    public function reconcile(Communication $communication): bool
    {
        $lock = Cache::lock('nimbus:sms:reconcile:'.$communication->id, 120);
        if (! $lock->get()) {
            return false;
        }

        try {
            $communication->refresh();
            if (in_array($communication->status, ['sent', 'delivered'], true)) {
                return true;
            }
            if ($communication->channel !== 'sms' || $communication->status !== 'pending') {
                return false;
            }

            $match = null;
            try {
                $from = $communication->created_at->copy()->subMinutes(2)->startOfDay();
                $until = $communication->created_at->copy()->addDay()->endOfDay();
                // Acumbamail allows ten report calls/minute. Share a snapshot
                // across pending communications for the same date range.
                $report = Cache::remember('nimbus:sms-report:'.$from->toDateString().':'.$until->toDateString(), 60,
                    fn () => app(AcumbamailService::class)->getSmsReport($from, $until));
                foreach ($report as $entry) {
                    if ($this->matches($communication, $entry)) {
                        $match = $entry;
                        break;
                    }
                }
            } catch (Throwable $e) {
                Log::warning('SMS reconciliation report unavailable', ['communication_id' => $communication->id]);
            }

            if ($match) {
                $delivered = in_array(mb_strtolower((string) ($match['status'] ?? '')), ['entregado', 'delivered'], true);
                $communication->update([
                    'status' => $delivered ? 'delivered' : 'sent',
                    'provider_message_id' => (string) $match['sms_id'],
                    'sent_at' => Carbon::parse($match['sent_at']),
                    'delivered_at' => $delivered && ! empty($match['delivered_at']) ? Carbon::parse($match['delivered_at']) : null,
                    'failed_at' => null,
                    'error_message' => null,
                ]);
                Log::info('SMS delivery recovered from Acumbamail report', [
                    'communication_id' => $communication->id,
                    'provider_message_id' => (string) $match['sms_id'],
                ]);

                return true;
            }

            // Absence from a report does not prove rejection: the provider may
            // still be processing it. Keep it blocked and ask for a review.
            $this->notifyIfOverdue($communication);

            return false;
        } finally {
            $lock->release();
        }
    }

    private function matches(Communication $communication, mixed $entry): bool
    {
        if (! is_array($entry) || empty($entry['sms_id']) || empty($entry['sent_at'])
            || ! isset($entry['phone'], $entry['sms_content'])
            || $entry['sms_content'] !== $communication->message_body) {
            return false;
        }

        try {
            $sentAt = Carbon::parse($entry['sent_at']);

            return AcumbamailService::formatPhoneNumber((string) $entry['phone']) === AcumbamailService::formatPhoneNumber($communication->recipient)
                && $sentAt->gte($communication->created_at->copy()->subMinutes(2))
                && $sentAt->lte($communication->created_at->copy()->addDay());
        } catch (Throwable $e) {
            return false;
        }
    }

    private function notifyIfOverdue(Communication $communication): void
    {
        if ($communication->created_at->gt(now()->subMinutes(self::REVIEW_AFTER_MINUTES))
            || str_contains($communication->error_message ?? '', self::NOTIFIED_MARKER)) {
            return;
        }

        $patient = $communication->patient;
        $professional = $patient?->user;
        if (! $professional?->email) {
            Log::warning('Unconfirmed SMS needs review but has no professional email', ['communication_id' => $communication->id]);

            return;
        }

        try {
            Mail::to($professional->email)->send(new UnconfirmedSmsDelivery($communication));
            $communication->update(['error_message' => trim(($communication->error_message ?? '').' '.self::NOTIFIED_MARKER)]);
        } catch (Throwable $e) {
            Log::error('Could not notify professional about unconfirmed SMS', ['communication_id' => $communication->id]);
        }
    }
}
