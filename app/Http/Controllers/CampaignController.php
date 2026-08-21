<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\AuditLogService;
use App\Services\CampaignAutomationService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private AuditLogService $auditLog,
        private CampaignAutomationService $campaignAutomationService
    ) {
    }

    public function store(Request $request)
    {
        $this->authorize('create', Campaign::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'objective' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $campaign = Campaign::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'status' => 'draft',
        ]));

        $this->auditLog->log('created', 'Campaign', $campaign->name);

        return redirect()->route('campaigns.show', $campaign->id)->with('status', 'Campaign created.');
    }

    public function addVendors(Request $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('assignVendors', $campaign);

        $request->validate([
            'vendor_ids' => 'required|array',
            'vendor_ids.*' => 'exists:vendors,id',
        ]);

        $added = $this->campaignAutomationService->addVendors($campaign, $request->vendor_ids);

        $this->auditLog->log('updated', 'Campaign', "Added {$added} vendors to {$campaign->name}");

        return back()->with('status', "{$added} vendors added to campaign.");
    }

    public function removeVendor(Request $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('assignVendors', $campaign);

        $request->validate(['vendor_id' => 'required|exists:vendors,id']);

        $this->campaignAutomationService->removeVendor($campaign, (int) $request->vendor_id);
        $this->auditLog->log('updated', 'Campaign', "Removed vendor from {$campaign->name}");

        return back()->with('status', 'Vendor removed from campaign.');
    }

    public function bulkGenerateEmails(Request $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('generateEmails', $campaign);

        $request->validate([
            'objective' => 'required|string',
            'tone' => 'required|string',
            'use_ai' => 'nullable|boolean',
        ]);

        $useAI = $request->has('use_ai');

        $result = $this->campaignAutomationService->bulkGenerateEmails(
            $campaign,
            $request->objective,
            $request->tone,
            auth()->user(),
            $useAI
        );

        if ($result['empty']) {
            return back()->with('error', 'No eligible vendors found for email generation.');
        }

        $method = $useAI ? 'AI-personalized' : 'template-based';
        $message = "Bulk generation complete ({$method}): {$result['generated']} generated, {$result['auto_queued']} auto-queued, {$result['failed']} failed, {$result['skipped']} skipped.";
        $this->auditLog->log('generated', 'Campaign', $message);

        return back()->with('status', $message);
    }

    public function start(Request $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('start', $campaign);

        $campaign->update(['status' => 'active', 'started_at' => now()]);

        $this->auditLog->log('started', 'Campaign', $campaign->name);

        return back()->with('status', 'Campaign started.');
    }

    public function pause(Request $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('pause', $campaign);

        $campaign->update(['status' => 'paused']);

        $this->auditLog->log('paused', 'Campaign', $campaign->name);

        return back()->with('status', 'Campaign paused.');
    }

    public function updateAutomation(Request $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'auto_approve' => 'boolean',
            'auto_followup_enabled' => 'boolean',
            'followup_delay_days' => 'integer|min:1|max:30',
            'max_followups' => 'integer|min:0|max:10',
        ]);

        $validated['auto_approve'] = $request->has('auto_approve');
        $validated['auto_followup_enabled'] = $request->has('auto_followup_enabled');

        $campaign->update($validated);

        $this->auditLog->log('updated', 'Campaign Automation', $campaign->name);

        return back()->with('status', 'Automation settings saved.');
    }
}
