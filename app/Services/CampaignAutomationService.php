<?php

namespace App\Services;

use App\Jobs\ProcessEmailQueueJob;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\EmailQueue;
use App\Models\GeneratedEmail;
use App\Models\User;
use App\Services\AI\EmailPersonalizationService;

class CampaignAutomationService
{
    public function __construct(
        private EmailPersonalizationService $emailPersonalizationService,
        private EmailSendingService $emailSendingService
    ) {
    }

    public function addVendors(Campaign $campaign, array $vendorIds): int
    {
        $existing = $campaign->vendors()->pluck('vendors.id')->toArray();
        $newIds = array_diff($vendorIds, $existing);

        if (empty($newIds)) {
            return 0;
        }

        $syncData = [];
        foreach ($newIds as $vendorId) {
            $syncData[$vendorId] = ['status' => 'selected'];
        }

        $campaign->vendors()->attach($syncData);

        return count($newIds);
    }

    public function removeVendor(Campaign $campaign, int $vendorId): void
    {
        $campaign->vendors()->detach($vendorId);
    }

    public function bulkGenerateEmails(Campaign $campaign, string $objective, string $tone, User $user): array
    {
        $company = Company::where('is_active', true)->first();

        $vendors = $campaign->vendors()
            ->wherePivot('status', 'selected')
            ->whereNotIn('vendors.status', ['opted_out', 'invalid_email'])
            ->whereNotNull('contact_email')
            ->get();

        if ($vendors->isEmpty()) {
            return [
                'generated' => 0,
                'failed' => 0,
                'skipped' => 0,
                'auto_queued' => 0,
                'empty' => true,
            ];
        }

        $generated = 0;
        $failed = 0;
        $skipped = 0;
        $autoQueued = 0;

        foreach ($vendors as $vendor) {
            $existingEmail = GeneratedEmail::where('vendor_id', $vendor->id)
                ->where('campaign_id', $campaign->id)
                ->whereIn('status', ['draft', 'approved'])
                ->exists();

            if ($existingEmail) {
                $skipped++;
                continue;
            }

            $result = $this->emailPersonalizationService->generateEmail(
                $vendor,
                $company,
                $user,
                $objective,
                $tone,
                null,
                $campaign->id
            );

            if (!$result['success']) {
                $failed++;
                continue;
            }

            $campaign->vendors()->updateExistingPivot($vendor->id, [
                'status' => 'email_generated',
                'email_generated_at' => now(),
            ]);

            if ($campaign->auto_approve
                && !$this->emailSendingService->isVendorSuppressed($vendor)
                && !$this->emailSendingService->hasDuplicateSent($vendor->id, $campaign->id)
            ) {
                $email = $result['email'];
                $email->update(['status' => 'approved', 'approved_at' => now()]);

                EmailQueue::create([
                    'vendor_id' => $vendor->id,
                    'campaign_id' => $campaign->id,
                    'generated_email_id' => $email->id,
                    'recipient_email' => $vendor->contact_email,
                    'subject' => $email->subject,
                    'body' => $email->body,
                    'status' => 'scheduled',
                    'scheduled_at' => now(),
                ]);

                $vendor->update(['email_status' => 'scheduled']);
                $autoQueued++;
            } else {
                $vendor->update(['email_status' => 'draft']);
            }

            $generated++;
        }

        if ($autoQueued > 0) {
            ProcessEmailQueueJob::dispatch();
        }

        return [
            'generated' => $generated,
            'failed' => $failed,
            'skipped' => $skipped,
            'auto_queued' => $autoQueued,
            'empty' => false,
        ];
    }
}
