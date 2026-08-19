<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\GeneratedEmail;
use App\Models\EmailLog;
use App\Models\EmailReply;
use App\Models\Product;
use App\Models\Company;
use App\Services\AI\KimiService;

class VendorShowService
{
    public function getVendorShowData(int $vendorId): array
    {
        $vendor = Vendor::with(['products', 'brandApproval', 'emailReplies'])->findOrFail($vendorId);

        return [
            'vendor' => $vendor,
            'generatedEmails' => GeneratedEmail::where('vendor_id', $vendorId)->latest()->limit(10)->get(),
            'emailLogs' => EmailLog::where('vendor_id', $vendorId)->latest()->limit(10)->get(),
            'emailReplies' => $vendor->emailReplies,
            'products' => $vendor->products,
            'brandApproval' => $vendor->brandApproval,
            'company' => Company::where('is_active', true)->first(),
            'kimiService' => app(KimiService::class),
        ];
    }
}
