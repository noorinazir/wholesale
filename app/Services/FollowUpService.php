<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\EmailQueue;
use App\Models\FollowUp;
use App\Models\Vendor;
use App\Services\AI\TemplateEngine;
use App\Models\Company;
use App\Jobs\ProcessEmailQueueJob;
use Illuminate\Support\Facades\Log;

class FollowUpService
{
    public function processDueFollowUps(): array
    {
        $dueFollowUps = FollowUp::with('vendor')
            ->where('status', 'scheduled')
            ->where('scheduled_date', '<=', now()->toDateString())
            ->where('auto_send', true)
            ->get();

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $sendingService = app(\App\Services\EmailSendingService::class);

        foreach ($dueFollowUps as $followUp) {
            $vendor = $followUp->vendor;

            if (!$vendor || $vendor->isOptedOut()) {
                $followUp->update(['status' => 'cancelled']);
                $skipped++;
                continue;
            }

            if ($sendingService->isVendorSuppressed($vendor)) {
                $followUp->update(['status' => 'cancelled']);
                $skipped++;
                continue;
            }

            if ($vendor->status === 'replied' || $vendor->status === 'interested' || $vendor->status === 'approved') {
                $followUp->update(['status' => 'cancelled']);
                $skipped++;
                continue;
            }

            if (!$vendor->contact_email) {
                $followUp->update(['status' => 'cancelled']);
                $skipped++;
                continue;
            }

            $queueItem = EmailQueue::create([
                'vendor_id' => $vendor->id,
                'campaign_id' => $followUp->campaign_id,
                'generated_email_id' => $followUp->original_email_id,
                'recipient_email' => $vendor->contact_email,
                'subject' => $followUp->subject,
                'body' => $followUp->body,
                'status' => 'scheduled',
                'scheduled_at' => now(),
            ]);

            $followUp->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $sent++;
        }

        if ($sent > 0) {
            ProcessEmailQueueJob::dispatch();
            Log::info("FollowUpService dispatched queue processing for {$sent} follow-up emails");
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
    }

    public function createFollowUpSequence(EmailQueue $sentItem): void
    {
        $campaign = $sentItem->campaign;
        if (!$campaign || !$campaign->auto_followup_enabled) {
            return;
        }

        $vendor = $sentItem->vendor;
        $existingFollowUps = FollowUp::where('vendor_id', $vendor->id)
            ->where('campaign_id', $campaign->id)
            ->count();

        if ($existingFollowUps >= $campaign->max_followups) {
            return;
        }

        $sequence = $existingFollowUps + 1;
        $delayDays = $campaign->followup_delay_days * $sequence;
        $scheduledDate = now()->addDays($delayDays);

        $subject = 'Re: ' . $sentItem->subject;
        $body = $this->generateFollowUpBody($vendor, $sentItem, $sequence);

        FollowUp::create([
            'vendor_id' => $vendor->id,
            'campaign_id' => $campaign->id,
            'original_email_id' => $sentItem->generated_email_id,
            'sequence' => $sequence,
            'delay_days' => $delayDays,
            'scheduled_date' => $scheduledDate,
            'subject' => $subject,
            'body' => $body,
            'status' => 'scheduled',
            'auto_send' => true,
        ]);
    }

    private function generateFollowUpBody(Vendor $vendor, EmailQueue $originalEmail, int $sequence): string
    {
        $company = Company::where('is_active', true)->first();
        $templateEngine = app(TemplateEngine::class);

        $templateData = $templateEngine->buildFollowUp(
            $vendor,
            $company,
            $originalEmail->subject,
            $sequence
        );

        return $templateData['body'];
    }

    public function cancelFollowUpsForVendor(int $vendorId, ?int $campaignId = null): void
    {
        $query = FollowUp::where('vendor_id', $vendorId)
            ->where('status', 'scheduled');

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $query->update(['status' => 'cancelled']);
    }
}
