<?php

namespace App\Http\Controllers;

use App\Models\BrandApproval;
use App\Models\Vendor;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class VendorBrandApprovalController extends Controller
{
    public function __construct(private AuditLogService $auditLog)
    {
    }

    public function save(Request $request, int $vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        $this->authorize('saveBrandApproval', $vendor);

        $validated = $request->validate([
            'approval_status' => 'required|in:pending,submitted,under_review,approved,rejected,expired',
            'submitted_at' => 'nullable|date',
            'approved_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'approved_categories' => 'nullable|array',
            'minimum_order_qty' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:255',
            'lead_time_days' => 'nullable|integer|min:0',
            'exclusive_territories' => 'nullable|array',
            'pricing_tier' => 'nullable|string|max:255',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'contact_person' => 'nullable|string|max:255',
            'approval_document_url' => 'nullable|string|max:500',
            'requires_exclusivity' => 'boolean',
            'requires_map_policy' => 'boolean',
            'requires_brand_registry' => 'boolean',
            'requirements_notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['vendor_id'] = $vendor->id;
        $validated['requires_exclusivity'] = $request->has('requires_exclusivity');
        $validated['requires_map_policy'] = $request->has('requires_map_policy');
        $validated['requires_brand_registry'] = $request->has('requires_brand_registry');

        $existing = $vendor->brandApproval;
        if ($existing) {
            $existing->update($validated);
        } else {
            BrandApproval::create($validated);
        }

        if ($validated['approval_status'] === 'approved' && $vendor->status !== 'approved') {
            $vendor->update(['status' => 'approved']);
        }

        $this->auditLog->log('updated', 'Brand Approval', $vendor->brand_name);

        return back()->with('status', 'Brand approval saved.');
    }
}
