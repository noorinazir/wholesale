<?php

namespace App\Console\Commands;

use App\Services\EmailSendingService;
use App\Models\EmailQueue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('emails:process-queue')]
#[Description('Process pending email queue items with rate limiting and schedule checks')]
class ProcessEmailQueueCommand extends Command
{
    public function handle(): int
    {
        $sendingService = app(EmailSendingService::class);

        if (!$sendingService->canSend()) {
            $this->info('Cannot send right now (paused, outside schedule, or limit reached).');
            return self::SUCCESS;
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
                continue;
            }

            if ($sendingService->hasDuplicateSent($item->vendor_id, $item->campaign_id)) {
                $item->update(['status' => 'cancelled', 'last_error' => 'Duplicate: already sent for this campaign']);
                continue;
            }

            $result = $sendingService->sendQueueItem($item);
            $processed++;

            if ($result['success']) {
                $delay = $sendingService->getDelaySeconds();
                $this->info("Sent queue item {$item->id}, waiting {$delay}s");
                if ($sendingService->canSend()) {
                    sleep(min($delay, 5));
                }
            } else {
                $this->error("Failed queue item {$item->id}: " . ($result['error'] ?? 'Unknown'));
                if ($item->attempts < $item->max_attempts && $sendingService->canSend()) {
                    $item->update(['status' => 'scheduled', 'scheduled_at' => now()->addMinutes(5)]);
                }
            }
        }

        $this->info("Processed {$processed} email(s).");
        return self::SUCCESS;
    }
}
