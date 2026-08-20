<?php

namespace App\Services\Search;

use App\Models\Campaign;
use App\Models\GeneratedEmail;
use App\Models\User;
use App\Models\Vendor;

class GlobalSearchService
{
    public function search(User $user, string $query): array
    {
        $results = [];

        if (strlen($query) < 2) {
            return $results;
        }

        $canSearchVendors = $user->can('manage-vendors') || $user->can('view-vendors');
        $canSearchCampaigns = $user->can('manage-campaigns') || $user->can('view-campaigns');
        $canSearchEmails = $user->can('manage-emails') || $user->can('view-emails');

        if ($canSearchVendors) {
            $vendors = Vendor::where('brand_name', 'like', "%{$query}%")
                ->orWhere('company_name', 'like', "%{$query}%")
                ->orWhere('contact_email', 'like', "%{$query}%")
                ->limit(10)
                ->get(['id', 'brand_name', 'company_name', 'contact_email']);

            foreach ($vendors as $vendor) {
                $results[] = [
                    'type' => 'Vendor',
                    'label' => $vendor->brand_name,
                    'sublabel' => $vendor->contact_email ?? $vendor->company_name,
                    'url' => route('vendors.show', $vendor->id),
                ];
            }
        }

        if ($canSearchCampaigns) {
            $campaigns = Campaign::where('name', 'like', "%{$query}%")
                ->limit(5)
                ->get(['id', 'name']);

            foreach ($campaigns as $campaign) {
                $results[] = [
                    'type' => 'Campaign',
                    'label' => $campaign->name,
                    'sublabel' => 'Campaign',
                    'url' => route('campaigns.show', $campaign->id),
                ];
            }
        }

        if ($canSearchEmails) {
            $emails = GeneratedEmail::where('subject', 'like', "%{$query}%")
                ->limit(5)
                ->get(['id', 'subject']);

            foreach ($emails as $email) {
                $results[] = [
                    'type' => 'Email',
                    'label' => $email->subject,
                    'sublabel' => 'Generated Email',
                    'url' => route('emails.preview', $email->id),
                ];
            }
        }

        return $results;
    }
}
