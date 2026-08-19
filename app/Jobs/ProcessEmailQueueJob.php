<?php

namespace App\Jobs;

use App\Models\EmailQueue;
use App\Services\EmailSendingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmailQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(EmailSendingService $sendingService): void
    {
        if ($sendingService->isSendingPaused()) {
            Log::info('Sending is paused, skipping queue processing');
            return;
        }

        if (!$sendingService->isWithinSendingSchedule()) {
            Log::info('Outside sending schedule, skipping queue processing');
            return;
        }

        if ($sendingService->isDailyLimitReached()) {
            Log::info('Daily sending limit reached, pausing queue');
            return;
        }

        if ($sendingService->isHourlyLimitReached()) {
            Log::info('Hourly sending limit reached, pausing queue');
            return;
        }

        $processed = 0;
        $maxPerRun = 20;

        while ($processed < $maxPerRun && $sendingService->canSend()) {
            $item = EmailQueue::where('status', 'scheduled')
                ->where('scheduled_at', '<=', now())
                ->orderBy('scheduled_at')
                ->first();

            if (!$item) {
                $item = EmailQueue::where('status', 'pending')
                    ->orderBy('id')
                    ->first();
            }

            if (!$item) {
                break;
            }

            if ($sendingService->isVendorSuppressed($item->vendor)) {
                $item->update(['status' => 'cancelled', 'last_error' => 'Vendor is suppressed/opted out']);
                Log::info("Vendor {$item->vendor_id} is suppressed, cancelling queue item {$item->id}");
                continue;
            }

            if ($sendingService->hasDuplicateSent($item->vendor_id, $item->campaign_id)) {
                $item->update(['status' => 'cancelled', 'last_error' => 'Duplicate: already sent for this campaign']);
                Log::info("Duplicate detected for vendor {$item->vendor_id}, cancelling queue item {$item->id}");
                continue;
            }

            $result = $sendingService->sendQueueItem($item);
            $processed++;

            if ($result['success']) {
                $delay = $sendingService->getDelaySeconds();
                Log::info("Email sent for queue item {$item->id}, next delay: {$delay}s");

                if ($sendingService->canSend()) {
                    sleep(min($delay, 5));
                }
            } else {
                Log::error("Failed to send queue item {$item->id}: " . ($result['error'] ?? 'Unknown error'));

                if ($item->attempts < $item->max_attempts && $sendingService->canSend()) {
                    $item->update(['status' => 'scheduled', 'scheduled_at' => now()->addMinutes(5)]);
                }
            }
        }

        Log::info("ProcessEmailQueueJob finished, processed {$processed} items");
    }
}
