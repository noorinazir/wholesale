<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\EmailTemplate;
use App\Models\GeneratedEmail;
use App\Models\EmailQueue;
use App\Models\EmailLog;
use App\Models\SmtpSetting;
use App\Models\SystemSetting;
use App\Models\SuppressionList;
use App\Models\User;
use App\Services\AI\EmailPersonalizationService;
use App\Services\AI\KimiService;
use App\Services\EmailSendingService;
use App\Services\CsvImportService;
use App\Services\AuditLogService;
use App\Services\DashboardService;
use App\Services\AnalyticsService;
use App\Services\VendorShowService;
use App\Jobs\ProcessEmailQueueJob;
use App\Http\Requests\VendorRequest;
use App\Http\Requests\ProductRequest;
use App\Enums\VendorStatus;
use App\Enums\Priority;
use App\Enums\EmailStatus;
use App\Enums\ApprovalStatus;
use App\Support\CategoryOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class ApplicationController extends Controller
{
    public function __construct(
        private AuditLogService $auditLog,
        private DashboardService $dashboardService,
        private AnalyticsService $analyticsService,
        private VendorShowService $vendorShowService
    ) {}

    // === Company Profile ===

    // === Dashboard ===
    public function dashboard()
    {
        $data = $this->dashboardService->getDashboardData();
        return view('dashboard', $data);
    }

    // === Analytics ===
    public function analytics()
    {
        $data = $this->analyticsService->getAnalyticsData();
        return view('analytics.index', $data);
    }

    // === Company Profile ===
    public function saveCompany(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'legal_company_name' => 'nullable|string',
            'resell_tax_id' => 'nullable|string|max:255',
            'ein' => 'nullable|string|max:255',
            'website' => 'nullable|string',
            'business_description' => 'nullable|string',
            'business_address' => 'nullable|string',
            'country' => 'nullable|string',
            'state_province' => 'nullable|string',
            'city' => 'nullable|string',
            'contact_person' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'phone' => 'nullable|string',
            'amazon_store_url' => 'nullable|string',
            'amazon_marketplace' => 'nullable|string',
            'years_in_business' => 'nullable|integer',
            'business_model' => 'nullable|string',
            'product_categories' => 'nullable|string',
            'brands_represented' => 'nullable|string',
            'sales_channels' => 'nullable|string',
            'estimated_annual_purchasing_volume' => 'nullable|numeric',
            'estimated_monthly_purchasing_volume' => 'nullable|numeric',
            'target_brands' => 'nullable|string',
            'additional_information' => 'nullable|string',
            'custom_notes' => 'nullable|string',
        ]);

        $company = Company::where('is_active', true)->first();
        if ($company) {
            $company->update(array_merge($validated, ['user_id' => auth()->id()]));
        } else {
            $company = Company::create(array_merge($validated, ['user_id' => auth()->id(), 'is_active' => true]));
        }

        $this->auditLog->log('updated', 'Company Profile', $company->company_name);
        return back()->with('status', 'Company profile saved successfully.');
    }

    public function uploadCompanyDocument(Request $request)
    {
        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'type' => 'required|in:resell_tax_id,ein,business_license,other',
        ]);

        $company = Company::where('is_active', true)->first();
        if (!$company) {
            return back()->with('error', 'Please save your company profile first.');
        }

        $file = $request->file('document');
        $path = $file->store('company-documents/' . $company->id, 'local');

        $doc = \App\Models\CompanyDocument::create([
            'company_id' => $company->id,
            'type' => $validated['type'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $this->auditLog->log('uploaded', 'Company Document', "{$validated['type']}: {$doc->original_name}");
        return back()->with('status', 'Document uploaded successfully.');
    }

    public function deleteCompanyDocument($id)
    {
        $doc = \App\Models\CompanyDocument::findOrFail($id);

        if (!Storage::disk('local')->exists($doc->file_path)) {
            return back()->with('error', 'Document file was not found on disk.');
        }

        Storage::disk('local')->delete($doc->file_path);
        $doc->delete();

        $this->auditLog->log('deleted', 'Company Document', "{$doc->type}: {$doc->original_name}");
        return back()->with('status', 'Document deleted.');
    }

    public function downloadCompanyDocument(Request $request, $id)
    {
        abort_unless($request->hasValidSignature(), 403);

        $doc = \App\Models\CompanyDocument::findOrFail($id);

        if (!Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('local')->download(
            $doc->file_path,
            $doc->original_name,
            ['Content-Type' => $doc->mime_type]
        );
    }

    // === Vendor Index ===
    public function vendorIndex(Request $request)
    {
        $query = Vendor::query()->select(['id', 'brand_name', 'company_name', 'website', 'contact_name', 'contact_email', 'phone', 'product_category', 'country', 'status', 'email_status', 'priority', 'created_at']);

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('brand_name', 'like', '%' . $search . '%')
                  ->orWhere('company_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_email', 'like', '%' . $search . '%');
            });
        }

        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->input('email_status')) {
            $query->where('email_status', $request->input('email_status'));
        }
        if ($request->input('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->input('country')) {
            $query->where('country', $request->input('country'));
        }

        $sort = $request->input('sort', 'latest');
        switch ($sort) {
            case 'name':
                $query->orderBy('brand_name');
                break;
            case 'oldest':
                $query->oldest();
                break;
            case 'status':
                $query->orderBy('status')->orderByDesc('created_at');
                break;
            case 'priority':
                $query->orderByRaw("CASE WHEN priority = 'critical' THEN 1 WHEN priority = 'high' THEN 2 WHEN priority = 'medium' THEN 3 WHEN priority = 'low' THEN 4 ELSE 5 END")->orderByDesc('created_at');
                break;
            default:
                $query->latest();
        }

        $vendors = $query->paginate(25);

        $statusCounts = Vendor::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('vendors.index', [
            'vendors' => $vendors,
            'campaigns' => Campaign::orderBy('name')->get(['id', 'name']),
            'statuses' => VendorStatus::options(),
            'emailStatuses' => EmailStatus::options(),
            'priorities' => Priority::options(),
            'countries' => CategoryOptions::countries(),
            'categories' => CategoryOptions::categories(),
            'sources' => CategoryOptions::sources(),
            'statusColors' => VendorStatus::colors(),
            'emailStatusColors' => EmailStatus::colors(),
            'priorityColors' => Priority::colors(),
            'statusCounts' => $statusCounts,
        ]);
    }

    // === Vendor Import ===
    public function importVendors(Request $request, CsvImportService $importService)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $parsed = $importService->parseCsv($path);

        if (!$parsed['success']) {
            return back()->with('error', $parsed['error']);
        }

        $mapping = $importService->detectColumnMapping($parsed['headers']);
        $result = $importService->validateAndImport($parsed['headers'], $parsed['rows'], $mapping, auth()->id());

        $this->auditLog->log('imported', 'Vendors', "Imported {$result['imported']} vendors");
        return back()->with('import_result', $result);
    }

    // === Vendor Create ===
    public function createVendor(VendorRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();
        $validated['priority'] = $validated['priority'] ?? 'medium';
        $validated['status'] = $validated['status'] ?? 'new';

        if (!empty($validated['contact_email'])) {
            $existing = Vendor::where('contact_email', $validated['contact_email'])
                ->where('brand_name', $validated['brand_name'])
                ->first();
            if ($existing) {
                return back()->with('error', 'A vendor with this email and brand name already exists.')->withInput();
            }
        }

        $vendor = Vendor::create($validated);
        $this->auditLog->log('created', 'Vendor', $vendor->brand_name);

        if ($request->has('add_another')) {
            return back()->with('status', "Vendor '{$vendor->brand_name}' created. Add another below.")->withInput();
        }

        return redirect()->route('vendors.show', $vendor->id)->with('status', 'Vendor created successfully.');
    }

    // === Vendor Update ===
    public function updateVendor(VendorRequest $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $validated = $request->validated();

        $vendor->update($validated);
        $this->auditLog->log('updated', 'Vendor', $vendor->brand_name);
        return back()->with('status', 'Vendor updated successfully.');
    }

    // === Vendor Show ===
    public function showVendor($id)
    {
        $data = $this->vendorShowService->getVendorShowData($id);
        return view('vendors.show', $data);
    }

    public function generateDocumentResponse(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $request->validate([
            'reply_id' => 'required|exists:email_replies,id',
        ]);
        $reply = \App\Models\EmailReply::where('id', $request->input('reply_id'))
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();
        $company = Company::where('is_active', true)->first();

        $personalizationService = app(\App\Services\AI\EmailPersonalizationService::class);
        $result = $personalizationService->generateDocumentResponseEmail(
            $vendor,
            $company,
            $reply->subject,
            $reply->body_text,
            auth()->user()
        );

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        $docNames = [];
        if (!empty($result['attachments'])) {
            $docLabels = [
                'resell_tax_id' => 'Resale Tax ID',
                'ein' => 'EIN Document',
                'business_license' => 'Business License',
                'w9' => 'W-9 Form',
                'other' => 'Supporting Document',
            ];
            foreach ($result['attachments'] as $doc) {
                $docNames[] = $docLabels[$doc->type] ?? ucfirst(str_replace('_', ' ', $doc->type));
            }
        }

        $message = 'Document response email generated successfully.';
        if (!empty($docNames)) {
            $message .= ' Attachments ready: ' . implode(', ', $docNames) . '. Please review and attach the files before sending.';
        } elseif (!empty($result['requested_documents'])) {
            $message .= ' Requested documents not found in system. Please upload them in Company Settings.';
        }

        $this->auditLog->log('generated', 'Document Response Email', $vendor->brand_name);
        return redirect()->route('emails.preview', $result['email']->id)->with('status', $message);
    }

    // === Vendor Delete ===
    public function deleteVendor(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $vendor->update(['status' => 'archived']);
        $this->auditLog->log('archived', 'Vendor', $vendor->brand_name);
        return redirect()->route('vendors.index')->with('status', 'Vendor archived.');
    }

    // === Campaign Vendor Management ===
    public function addVendorsToCampaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $request->validate([
            'vendor_ids' => 'required|array',
            'vendor_ids.*' => 'exists:vendors,id',
        ]);

        $existing = $campaign->vendors()->pluck('vendors.id')->toArray();
        $newIds = array_diff($request->vendor_ids, $existing);

        if (!empty($newIds)) {
            $syncData = [];
            foreach ($newIds as $vid) {
                $syncData[$vid] = ['status' => 'selected'];
            }
            $campaign->vendors()->attach($syncData);
        }

        $this->auditLog->log('updated', 'Campaign', "Added " . count($newIds) . " vendors to {$campaign->name}");
        return back()->with('status', count($newIds) . ' vendors added to campaign.');
    }

    public function removeVendorFromCampaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $request->validate(['vendor_id' => 'required|exists:vendors,id']);

        $campaign->vendors()->detach($request->vendor_id);
        $this->auditLog->log('updated', 'Campaign', "Removed vendor from {$campaign->name}");
        return back()->with('status', 'Vendor removed from campaign.');
    }

    public function bulkGenerateEmails(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $request->validate([
            'objective' => 'required|string',
            'tone' => 'required|string',
        ]);

        $company = Company::where('is_active', true)->first();
        $service = app(EmailPersonalizationService::class);
        $sendingService = app(EmailSendingService::class);

        $vendors = $campaign->vendors()
            ->wherePivot('status', 'selected')
            ->whereNotIn('vendors.status', ['opted_out', 'invalid_email'])
            ->whereNotNull('contact_email')
            ->get();

        if ($vendors->isEmpty()) {
            return back()->with('error', 'No eligible vendors found for email generation.');
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

            $result = $service->generateEmail(
                $vendor, $company, auth()->user(),
                $request->objective, $request->tone, null, $campaign->id
            );

            if ($result['success']) {
                $campaign->vendors()->updateExistingPivot($vendor->id, [
                    'status' => 'email_generated',
                    'email_generated_at' => now(),
                ]);

                if ($campaign->auto_approve && !$sendingService->isVendorSuppressed($vendor) && !$sendingService->hasDuplicateSent($vendor->id, $campaign->id)) {
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
            } else {
                $failed++;
            }
        }

        if ($autoQueued > 0) {
            ProcessEmailQueueJob::dispatch();
        }

        $msg = "Bulk generation complete: {$generated} generated, {$autoQueued} auto-queued, {$failed} failed, {$skipped} skipped.";
        $this->auditLog->log('generated', 'Campaign', $msg);
        return back()->with('status', $msg);
    }

    public function startCampaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->update(['status' => 'active', 'started_at' => now()]);
        $this->auditLog->log('started', 'Campaign', $campaign->name);
        return back()->with('status', 'Campaign started.');
    }

    public function pauseCampaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->update(['status' => 'paused']);
        $this->auditLog->log('paused', 'Campaign', $campaign->name);
        return back()->with('status', 'Campaign paused.');
    }

    public function updateCampaignAutomation(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
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

    // === Vendor Quick Status Update ===
    public function updateVendorStatus(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:new,researching,ready_to_contact,contacted,replied,interested,not_interested,approved,rejected,follow_up_required,opted_out,invalid_email,archived',
        ]);

        $vendor->update($validated);
        $this->auditLog->log('status_changed', 'Vendor', "{$vendor->brand_name}: {$validated['status']}");

        return back()->with('status', 'Status updated to ' . ucfirst(str_replace('_', ' ', $validated['status'])));
    }

    // === Vendor Quick Priority Update ===
    public function updateVendorPriority(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $vendor->update($validated);
        $this->auditLog->log('priority_changed', 'Vendor', "{$vendor->brand_name}: {$validated['priority']}");

        return back()->with('status', 'Priority updated to ' . ucfirst($validated['priority']));
    }

    // === Vendor Show Actions ===
    public function vendorAction(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $action = $request->input('action');

        if ($action === 'research') {
            $company = Company::where('is_active', true)->first();
            $service = app(EmailPersonalizationService::class);
            $result = $service->researchVendor($vendor, auth()->user());

            if ($result['success']) {
                $this->auditLog->log('researched', 'Vendor', $vendor->brand_name);
                return back()->with('status', 'Research completed.');
            }
            return back()->with('error', $result['error'] ?? 'Research failed.');
        }

        return back();
    }

    // === AI Assistant ===
    public function aiAssistant(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'generate') {
            $request->validate([
                'vendor_id' => 'required|exists:vendors,id',
                'objective' => 'required|string',
                'tone' => 'required|string',
                'custom_instructions' => 'nullable|string',
            ]);

            $vendor = Vendor::findOrFail($request->vendor_id);
            $company = Company::where('is_active', true)->first();
            $service = app(EmailPersonalizationService::class);

            $result = $service->generateEmail(
                $vendor,
                $company,
                auth()->user(),
                $request->objective,
                $request->tone,
                $request->custom_instructions
            );

            if ($result['success']) {
                $this->auditLog->log('generated', 'Email', $vendor->brand_name);
                return back()->with('generated_email', $result['email'])->withInput(['vendor' => $vendor->id]);
            }
            return back()->with('error', $result['error'] ?? 'Generation failed')->withInput(['vendor' => $vendor->id]);
        }

        if ($action === 'research') {
            $request->validate(['vendor_id' => 'required|exists:vendors,id']);
            $vendor = Vendor::findOrFail($request->vendor_id);
            $service = app(EmailPersonalizationService::class);
            $result = $service->researchVendor($vendor, auth()->user());

            if ($result['success']) {
                $this->auditLog->log('researched', 'Vendor', $vendor->brand_name);
                return back()->with('research_data', $result['data'])->withInput(['vendor' => $vendor->id]);
            }
            return back()->with('error', $result['error'] ?? 'Research failed')->withInput(['vendor' => $vendor->id]);
        }

        return back();
    }

    // === Email Preview Actions ===
    public function emailPreviewAction(Request $request, $id)
    {
        $email = GeneratedEmail::findOrFail($id);
        $action = $request->input('action');
        $sendingService = app(EmailSendingService::class);

        if ($action === 'save') {
            $validated = $request->validate([
                'subject' => 'required|string|max:500',
                'body' => 'required|string|max:10000',
            ]);
            $email->update([
                'subject' => $validated['subject'],
                'body' => $validated['body'],
            ]);
            $this->auditLog->log('edited', 'Email', $email->subject);
            return back()->with('status', 'Email saved.');
        }

        if ($action === 'approve') {
            if ($sendingService->isVendorSuppressed($email->vendor)) {
                return back()->with('error', 'Cannot approve: vendor is suppressed/opted out.');
            }

            $email->update(['status' => 'approved', 'approved_at' => now()]);
            $email->vendor->update(['email_status' => 'ready']);

            if ($email->campaign_id) {
                $email->campaign->vendors()->updateExistingPivot($email->vendor_id, [
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
            }

            $this->auditLog->log('approved', 'Email', $email->subject);
            return back()->with('status', 'Email approved.');
        }

        if ($action === 'reject') {
            $email->update(['status' => 'rejected', 'rejected_at' => now()]);
            $this->auditLog->log('rejected', 'Email', $email->subject);
            return back()->with('status', 'Email rejected.');
        }

        if ($action === 'send') {
            if ($sendingService->isVendorSuppressed($email->vendor)) {
                return back()->with('error', 'Cannot send: vendor is suppressed/opted out.');
            }

            if ($sendingService->hasDuplicateSent($email->vendor_id, $email->campaign_id)) {
                return back()->with('error', 'This vendor has already been contacted for this campaign.');
            }

            $queueItem = EmailQueue::create([
                'vendor_id' => $email->vendor_id,
                'campaign_id' => $email->campaign_id,
                'generated_email_id' => $email->id,
                'recipient_email' => $email->vendor->contact_email,
                'subject' => $email->subject,
                'body' => $email->body,
                'status' => 'scheduled',
                'scheduled_at' => now(),
            ]);

            $email->update(['status' => 'approved']);
            $email->vendor->update(['email_status' => 'scheduled']);

            ProcessEmailQueueJob::dispatch();

            $this->auditLog->log('queued', 'Email', $email->subject);
            return back()->with('status', 'Email queued for sending.');
        }

        return back();
    }

    // === Campaign Actions ===
    public function createCampaign(Request $request)
    {
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

    // === Template Actions ===
    public function createTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'subject_template' => 'required|string',
            'body_template' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template = EmailTemplate::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'is_active' => $request->has('is_active'),
        ]));

        $this->auditLog->log('created', 'Template', $template->name);
        return redirect()->route('templates.index')->with('status', 'Template created.');
    }

    public function updateTemplate(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'subject_template' => 'required|string',
            'body_template' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $template->update($validated);

        $this->auditLog->log('updated', 'Template', $template->name);
        return back()->with('status', 'Template updated.');
    }

    // === SMTP Settings ===
    public function saveSmtp(Request $request)
    {
        if ($request->input('action') === 'test') {
            $request->validate(['test_email' => 'required|email']);
            $smtp = SmtpSetting::where('is_active', true)->first();
            if (!$smtp) {
                return back()->with('error', 'No SMTP configuration found.');
            }
            $sendingService = app(EmailSendingService::class);
            $result = $sendingService->testSmtpConnection($smtp, $request->test_email);
            $this->auditLog->log('tested', 'SMTP', $result['message']);
            return back()->with($result['success'] ? 'status' : 'error', $result['message']);
        }

        if ($request->input('action') === 'imap') {
            Log::info('IMAP save handler reached', ['request_data' => $request->except(['imap_password', '_token'])]);
            $validated = $request->validate([
                'imap_host' => 'nullable|string',
                'imap_port' => 'nullable|integer',
                'imap_encryption' => 'nullable|string',
                'imap_username' => 'nullable|string',
                'imap_password' => 'nullable|string',
            ]);

            $smtp = SmtpSetting::where('is_active', true)->first();
            if (!$smtp) {
                return back()->with('error', 'Save SMTP settings first before configuring IMAP.');
            }

            $smtp->update([
                'imap_host' => $validated['imap_host'] ?? null,
                'imap_port' => $validated['imap_port'] ?? 993,
                'imap_encryption' => $validated['imap_encryption'] ?? 'ssl',
                'imap_username' => $validated['imap_username'] ?? null,
                'inbox_checking_enabled' => $request->has('inbox_checking_enabled'),
            ]);

            if (!empty($validated['imap_password'])) {
                $smtp->update(['imap_password' => Crypt::encrypt($validated['imap_password'])]);
            }

            $this->auditLog->log('updated', 'IMAP Settings');
            return back()->with('status', 'IMAP settings saved.');
        }

        $validated = $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'encryption' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'from_name' => 'required|string',
            'from_email' => 'required|email',
            'reply_to' => 'nullable|email',
            'test_mode' => 'boolean',
            'test_mode_recipient' => 'nullable|email',
        ]);

        $smtp = SmtpSetting::where('is_active', true)->first();
        $data = [
            'host' => $validated['host'],
            'port' => $validated['port'],
            'encryption' => $validated['encryption'],
            'username' => $validated['username'],
            'from_name' => $validated['from_name'],
            'from_email' => $validated['from_email'],
            'reply_to' => $validated['reply_to'] ?? null,
            'is_active' => true,
            'test_mode' => $request->has('test_mode'),
            'test_mode_recipient' => $validated['test_mode_recipient'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Crypt::encrypt($validated['password']);
        }

        if ($smtp) {
            if (empty($validated['password'])) {
                unset($data['password']);
            }
            $smtp->update($data);
        } else {
            if (empty($validated['password'])) {
                $data['password'] = Crypt::encrypt('');
            }
            SmtpSetting::create($data);
        }

        $this->auditLog->log('updated', 'SMTP Settings');
        return back()->with('status', 'SMTP settings saved.');
    }

    // === AI Settings ===
    public function saveAiSettings(Request $request)
    {
        if ($request->input('action') === 'test') {
            $kimiService = app(KimiService::class);
            $result = $kimiService->testConnection();
            $this->auditLog->log('tested', 'AI Connection', $result['message']);
            return back()->with($result['success'] ? 'status' : 'error', $result['message']);
        }

        $validated = $request->validate([
            'kimi_api_key' => 'nullable|string',
            'kimi_model' => 'required|string',
            'kimi_temperature' => 'required|numeric|min:0|max:2',
            'kimi_max_tokens' => 'required|integer|min:1',
            'default_tone' => 'required|string',
            'default_objective' => 'required|string',
        ]);

        if (!empty($validated['kimi_api_key'])) {
            SystemSetting::set('kimi_api_key', $validated['kimi_api_key'], 'ai', true);
        }
        SystemSetting::set('kimi_model', $validated['kimi_model'], 'ai');
        SystemSetting::set('kimi_temperature', $validated['kimi_temperature'], 'ai');
        SystemSetting::set('kimi_max_tokens', $validated['kimi_max_tokens'], 'ai');
        SystemSetting::set('default_tone', $validated['default_tone'], 'ai');
        SystemSetting::set('default_objective', $validated['default_objective'], 'ai');

        $this->auditLog->log('updated', 'AI Settings');
        return back()->with('status', 'AI settings saved.');
    }

    // === Sending Settings ===
    public function saveSendingSettings(Request $request)
    {
        $action = $request->input('action');
        $sendingService = app(EmailSendingService::class);

        if ($request->has('sending_paused') && !$action) {
            $paused = (bool) $request->input('sending_paused');
            if ($paused) {
                $sendingService->pauseSending();
                $this->auditLog->log('paused', 'Sending');
                return back()->with('status', 'Sending paused.');
            } else {
                $sendingService->resumeSending();
                $this->auditLog->log('resumed', 'Sending');
                return back()->with('status', 'Sending resumed.');
            }
        }

        if ($action === 'pause') {
            $sendingService->pauseSending();
            $this->auditLog->log('paused', 'Sending');
            return back()->with('status', 'Sending paused.');
        }

        if ($action === 'resume') {
            $sendingService->resumeSending();
            $this->auditLog->log('resumed', 'Sending');
            return back()->with('status', 'Sending resumed.');
        }

        if ($action === 'cancel_pending') {
            $count = $sendingService->cancelAllPending();
            $this->auditLog->log('cancelled', 'Pending Emails', "{$count} emails cancelled");
            return back()->with('status', "{$count} pending emails cancelled.");
        }

        if ($action === 'limits') {
            $validated = $request->validate([
                'delay_type' => 'required|in:random,fixed',
                'min_delay_seconds' => 'required|integer|min:0',
                'max_delay_seconds' => 'required|integer|min:0',
                'daily_email_limit' => 'required|integer|min:1',
                'hourly_email_limit' => 'required|integer|min:1',
            ]);

            foreach ($validated as $key => $value) {
                SystemSetting::set($key, (string) $value, 'sending');
            }

            $this->auditLog->log('updated', 'Sending Limits');
            return back()->with('status', 'Limits & delay saved.');
        }

        if ($action === 'schedule') {
            $validated = $request->validate([
                'sending_start_time' => 'nullable|string',
                'sending_end_time' => 'nullable|string',
                'sending_timezone' => 'nullable|string',
                'allowed_weekdays' => 'array',
            ]);

            SystemSetting::set('sending_start_time', $validated['sending_start_time'], 'sending');
            SystemSetting::set('sending_end_time', $validated['sending_end_time'], 'sending');
            SystemSetting::set('sending_timezone', $validated['sending_timezone'], 'sending');
            SystemSetting::set('allowed_weekdays', json_encode($validated['allowed_weekdays'] ?? []), 'sending');

            $this->auditLog->log('updated', 'Sending Schedule');
            return back()->with('status', 'Schedule saved.');
        }

        return back();
    }

    // === User Management ===
    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
            'role' => 'required|in:administrator,manager,staff,viewer',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        $this->auditLog->log('created', 'User', $user->name);
        return back()->with('status', 'User created.');
    }

    // === System Settings ===
    public function saveSystemSettings(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'nullable|string',
            'session_timeout' => 'nullable|integer',
            'enable_follow_ups' => 'boolean',
            'include_opt_out' => 'boolean',
        ]);

        SystemSetting::set('app_name', $validated['app_name'] ?? config('app.name'), 'general');
        SystemSetting::set('session_timeout', (string)($validated['session_timeout'] ?? 120), 'general');
        SystemSetting::set('enable_follow_ups', $request->has('enable_follow_ups') ? '1' : '0', 'general');
        SystemSetting::set('include_opt_out', $request->has('include_opt_out') ? '1' : '0', 'general');

        $this->auditLog->log('updated', 'System Settings');
        return back()->with('status', 'System settings saved.');
    }

    // === Data Export ===
    public function export(Request $request)
    {
        $type = $request->query('export');

        if ($type === 'vendors') {
            $filename = 'vendors_' . now()->format('Y_m_d_His') . '.csv';
            $headers = ['Content-Type' => 'text/csv'];
            $callback = function () {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'Brand', 'Company', 'Contact', 'Email', 'Phone', 'Website', 'Category', 'Country', 'Status', 'Email Status', 'Priority', 'Last Contacted', 'Created At']);
                Vendor::orderBy('id')->chunk(500, function ($vendors) use ($handle) {
                    foreach ($vendors as $v) {
                        fputcsv($handle, [$v->id, $v->brand_name, $v->company_name, $v->contact_name, $v->contact_email, $v->phone, $v->website, $v->product_category, $v->country, $v->status, $v->email_status, $v->priority, $v->last_contacted_at, $v->created_at]);
                    }
                });
                fclose($handle);
            };
            return response()->stream($callback, 200, $headers + ['Content-Disposition' => "attachment; filename={$filename}"]);
        }

        if ($type === 'email_logs') {
            $filename = 'email_logs_' . now()->format('Y_m_d_His') . '.csv';
            $headers = ['Content-Type' => 'text/csv'];
            $callback = function () {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'Vendor', 'Recipient', 'Subject', 'Status', 'Sent At', 'Error', 'SMTP Response']);
                EmailLog::with('vendor:id,brand_name')->orderBy('id')->chunk(500, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [$log->id, $log->vendor?->brand_name, $log->recipient, $log->subject, $log->status, $log->sent_at, $log->error, $log->smtp_response]);
                    }
                });
                fclose($handle);
            };
            return response()->stream($callback, 200, $headers + ['Content-Disposition' => "attachment; filename={$filename}"]);
        }

        return back();
    }

    // === Inbox Check ===
    public function checkInbox(Request $request)
    {
        $inboxService = app(\App\Services\InboxService::class);
        $result = $inboxService->checkInbox();

        if ($result['success']) {
            return back()->with('status', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    // === Mark Reply as Read ===
    public function markReplyRead(Request $request, $id)
    {
        $reply = \App\Models\EmailReply::findOrFail($id);
        $reply->update(['is_read' => true]);

        return back()->with('status', 'Reply marked as read.');
    }

    // === Update Vendor Status from Reply ===
    public function updateVendorFromReply(Request $request, $id)
    {
        $reply = \App\Models\EmailReply::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:new,researching,ready_to_contact,contacted,replied,interested,not_interested,approved,rejected,follow_up_required,opted_out,invalid_email,archived',
        ]);

        $reply->vendor->update(['status' => $validated['status']]);
        $reply->update(['is_read' => true]);
        $this->auditLog->log('reply_status', 'Vendor', "{$reply->vendor->brand_name}: {$validated['status']}");

        return back()->with('status', 'Vendor status updated to ' . ucfirst(str_replace('_', ' ', $validated['status'])));
    }

    // === Product Management ===
    // === Product Index ===
    public function productIndex(Request $request)
    {
        $query = \App\Models\Product::query()->with('vendor:id,brand_name,company_name,contact_email,status');

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                  ->orWhere('asin', 'like', '%' . $search . '%')
                  ->orWhereHas('vendor', fn($vq) => $vq->where('brand_name', 'like', '%' . $search . '%'));
            });
        }

        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('category')) {
            $query->where('product_category', $request->input('category'));
        }

        if ($request->input('vendor_id')) {
            $query->where('vendor_id', $request->input('vendor_id'));
        }

        $sort = $request->input('sort', 'latest');
        switch ($sort) {
            case 'margin':
                $query->orderByDesc('margin_percent');
                break;
            case 'profit':
                $query->orderByDesc('net_profit');
                break;
            case 'roi':
                $query->orderByDesc('roi_percent');
                break;
            case 'price':
                $query->orderByDesc('amazon_sell_price');
                break;
            case 'name':
                $query->orderBy('product_name');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(25);

        return view('products.index', [
            'products' => $products,
            'vendors' => Vendor::orderBy('brand_name')->get(['id', 'brand_name']),
            'categories' => CategoryOptions::categories(),
        ]);
    }

    // === Product Show ===
    public function showProduct($id)
    {
        $product = \App\Models\Product::with('vendor:id,brand_name,company_name,contact_email,status')->findOrFail($id);
        $categories = CategoryOptions::categories();

        return view('products.show', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    // === Product CRUD ===
    public function createProduct(ProductRequest $request, $vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        $validated = $request->validated();

        $validated['vendor_id'] = $vendor->id;

        $numericDefaults = [
            'buying_price' => 0, 'fba_fee' => 0, 'shipping_cost' => 0,
            'labeling_cost' => 0, 'other_costs' => 0, 'operation_cost' => 0,
            'amazon_sell_price' => 0, 'referral_fee_percent' => 15.00,
            'number_of_sellers' => 0, 'bsr_rank' => null,
            'review_count' => null, 'review_rating' => null,
        ];
        foreach ($numericDefaults as $field => $default) {
            if (!isset($validated[$field]) || $validated[$field] === '') {
                $validated[$field] = $default;
            }
        }
        $validated = array_filter($validated, fn($v) => $v !== null);

        try {
            $product = \App\Models\Product::create($validated);
            $product->recalculate();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);
            return back()->with('error', 'Failed to create product: ' . $e->getMessage())->withInput();
        }

        $this->auditLog->log('created', 'Product', $product->product_name);
        return back()->with('status', 'Product added.');
    }

    public function createProductStandalone(ProductRequest $request)
    {
        $validated = $request->validated();

        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        $validated['vendor_id'] = $request->input('vendor_id');

        $numericDefaults = [
            'buying_price' => 0, 'fba_fee' => 0, 'shipping_cost' => 0,
            'labeling_cost' => 0, 'other_costs' => 0, 'operation_cost' => 0,
            'amazon_sell_price' => 0, 'referral_fee_percent' => 15.00,
            'number_of_sellers' => 0, 'bsr_rank' => null,
            'review_count' => null, 'review_rating' => null,
        ];
        foreach ($numericDefaults as $field => $default) {
            if (!isset($validated[$field]) || $validated[$field] === '') {
                $validated[$field] = $default;
            }
        }
        $validated = array_filter($validated, fn($v) => $v !== null);

        if (!empty($validated['asin'])) {
            $existing = \App\Models\Product::where('asin', $validated['asin'])->first();
            if ($existing) {
                return back()->with('error', "A product with ASIN {$validated['asin']} already exists: {$existing->product_name}")->withInput();
            }
        }

        try {
            $product = \App\Models\Product::create($validated);
            $product->recalculate();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);
            return back()->with('error', 'Failed to create product: ' . $e->getMessage())->withInput();
        }

        $this->auditLog->log('created', 'Product', $product->product_name);

        if ($request->has('add_another')) {
            return back()->with('status', "Product '{$product->product_name}' added. Add another below.");
        }

        return redirect()->route('products.show', $product->id)->with('status', 'Product added.');
    }

    public function updateProduct(ProductRequest $request, $id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $validated = $request->validated();

        $numericDefaults = [
            'buying_price' => 0, 'fba_fee' => 0, 'shipping_cost' => 0,
            'labeling_cost' => 0, 'other_costs' => 0, 'operation_cost' => 0,
            'amazon_sell_price' => 0, 'referral_fee_percent' => 15.00,
            'number_of_sellers' => 0,
        ];
        foreach ($numericDefaults as $field => $default) {
            if (array_key_exists($field, $validated) && ($validated[$field] === '' || $validated[$field] === null)) {
                $validated[$field] = $default;
            }
        }
        $validated = array_filter($validated, fn($v) => $v !== null);

        try {
            $product->update($validated);
            $product->recalculate();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update product', [
                'error' => $e->getMessage(),
                'product_id' => $id,
            ]);
            return back()->with('error', 'Failed to update product: ' . $e->getMessage())->withInput();
        }

        $this->auditLog->log('updated', 'Product', $product->product_name);
        return back()->with('status', 'Product updated.');
    }

    public function deleteProduct(Request $request, $id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $product->delete();

        $this->auditLog->log('deleted', 'Product', $product->product_name);
        return back()->with('status', 'Product deleted.');
    }

    public function bulkProductAction(Request $request)
    {
        $productIds = $request->input('product_ids');
        if (is_string($productIds)) {
            $productIds = array_filter(explode(',', $productIds));
        }

        $request->merge(['product_ids' => $productIds]);

        $request->validate([
            'action' => 'required|in:set_status,delete,recalculate',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'status' => 'required_if:action,set_status|in:active,inactive,discontinued',
        ]);

        $products = \App\Models\Product::whereIn('id', $productIds)->get();
        $count = $products->count();

        if ($count === 0) {
            return back()->with('error', 'No products selected.');
        }

        switch ($request->input('action')) {
            case 'set_status':
                $status = $request->input('status');
                \App\Models\Product::whereIn('id', $productIds)->update(['status' => $status]);
                $this->auditLog->log('bulk_updated', 'Products', "Set {$count} products to {$status}");
                return back()->with('status', "{$count} products set to {$status}.");
            case 'delete':
                \App\Models\Product::whereIn('id', $productIds)->delete();
                $this->auditLog->log('bulk_deleted', 'Products', "Deleted {$count} products");
                return back()->with('status', "{$count} products deleted.");
            case 'recalculate':
                foreach ($products as $product) {
                    $product->recalculate();
                }
                $this->auditLog->log('bulk_recalculated', 'Products', "Recalculated {$count} products");
                return back()->with('status', "{$count} products recalculated.");
        }

        return back()->with('error', 'Invalid action.');
    }

    // === Brand Approval Management ===
    public function saveBrandApproval(Request $request, $vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
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
            \App\Models\BrandApproval::create($validated);
        }

        if ($validated['approval_status'] === 'approved' && $vendor->status !== 'approved') {
            $vendor->update(['status' => 'approved']);
        }

        $this->auditLog->log('updated', 'Brand Approval', $vendor->brand_name);
        return back()->with('status', 'Brand approval saved.');
    }

    // === Bulk Vendor Actions ===
    public function bulkVendorAction(Request $request)
    {
        $validated = $request->validate([
            'vendor_ids' => 'required|array',
            'vendor_ids.*' => 'exists:vendors,id',
            'bulk_action' => 'required|in:set_status,set_priority,assign_campaign,delete',
            'status' => 'nullable|in:new,researching,ready_to_contact,contacted,replied,interested,not_interested,approved,rejected,follow_up_required,opted_out,invalid_email,archived',
            'priority' => 'nullable|in:low,medium,high,critical',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $vendors = Vendor::whereIn('id', $validated['vendor_ids'])->get();
        $action = $validated['bulk_action'];

        if ($action === 'set_status') {
            foreach ($vendors as $vendor) {
                $vendor->update(['status' => $validated['status']]);
            }
            $this->auditLog->log('bulk_status', 'Vendor', "Updated {$vendors->count()} vendors to {$validated['status']}");
            return back()->with('status', "Updated status for {$vendors->count()} vendors.");
        }

        if ($action === 'set_priority') {
            foreach ($vendors as $vendor) {
                $vendor->update(['priority' => $validated['priority']]);
            }
            $this->auditLog->log('bulk_priority', 'Vendor', "Updated {$vendors->count()} vendors to {$validated['priority']}");
            return back()->with('status', "Updated priority for {$vendors->count()} vendors.");
        }

        if ($action === 'assign_campaign') {
            $campaign = Campaign::findOrFail($validated['campaign_id']);
            $existing = $campaign->vendors()->pluck('vendors.id')->toArray();
            $newIds = array_diff($validated['vendor_ids'], $existing);
            $syncData = [];
            foreach ($newIds as $vid) {
                $syncData[$vid] = ['status' => 'selected'];
            }
            $campaign->vendors()->attach($syncData);
            $this->auditLog->log('bulk_campaign', 'Vendor', "Assigned " . count($newIds) . " vendors to {$campaign->name}");
            return back()->with('status', "Assigned " . count($newIds) . " vendors to campaign.");
        }

        if ($action === 'delete') {
            foreach ($vendors as $vendor) {
                $vendor->update(['status' => 'archived']);
            }
            $this->auditLog->log('bulk_archive', 'Vendor', "Archived {$vendors->count()} vendors");
            return back()->with('status', "Archived {$vendors->count()} vendors.");
        }

        return back();
    }

    // === CSV Export ===
    public function exportVendors()
    {
        $filename = 'vendors_' . now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['Brand Name', 'Company', 'Contact Name', 'Email', 'Phone', 'Country', 'Category', 'Status', 'Priority', 'Email Status', 'Last Contacted'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            Vendor::orderBy('id')->chunk(500, function ($vendors) use ($file) {
                foreach ($vendors as $vendor) {
                    fputcsv($file, [
                        $vendor->brand_name,
                        $vendor->company_name,
                        $vendor->contact_name,
                        $vendor->contact_email,
                        $vendor->phone,
                        $vendor->country,
                        $vendor->product_category,
                        $vendor->status,
                        $vendor->priority,
                        $vendor->email_status,
                        $vendor->last_contacted_at?->format('Y-m-d'),
                    ]);
                }
            });
            fclose($file);
        };

        $this->auditLog->log('exported', 'Vendor', 'Exported vendors');
        return response()->stream($callback, 200, $headers);
    }

    public function exportProducts($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        $products = $vendor->products;
        $filename = "products_{$vendor->brand_name}_" . now()->format('Y_m_d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['Product Name', 'ASIN', 'Category', 'Buying Price', 'FBA Fee', 'Shipping', 'Labeling', 'Other', 'Total Cost', 'Sell Price', 'Amazon Fee', 'Net Profit', 'Margin %', 'ROI %', 'Sellers', 'Buy Box', 'BSR', 'Reviews', 'Rating'];

        $callback = function () use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->product_name,
                    $product->asin,
                    $product->product_category,
                    $product->buying_price,
                    $product->fba_fee,
                    $product->shipping_cost,
                    $product->labeling_cost,
                    $product->other_costs,
                    $product->total_cost,
                    $product->amazon_sell_price,
                    $product->amazon_fee,
                    $product->net_profit,
                    $product->margin_percent,
                    $product->roi_percent,
                    $product->number_of_sellers,
                    $product->buy_box_type,
                    $product->bsr_rank,
                    $product->review_count,
                    $product->review_rating,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // === Notifications ===
    public function markNotificationRead($id)
    {
        $notification = \App\Models\Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->update(['read_at' => now()]);
        return back();
    }

    public function markAllNotificationsRead()
    {
        \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        return back();
    }

    // === Global Search ===
    public function globalSearch(Request $request)
    {
        $q = $request->input('q', '');
        $results = [];

        $user = auth()->user();
        $canSearchVendors = $user?->can('manage-vendors') || $user?->can('view-vendors');
        $canSearchCampaigns = $user?->can('manage-campaigns') || $user?->can('view-campaigns');
        $canSearchEmails = $user?->can('manage-emails') || $user?->can('view-emails');

        if (strlen($q) >= 2) {
            if ($canSearchVendors) {
                $vendors = Vendor::where('brand_name', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%")
                    ->orWhere('contact_email', 'like', "%{$q}%")
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
                $campaigns = Campaign::where('name', 'like', "%{$q}%")
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
                $emails = GeneratedEmail::where('subject', 'like', "%{$q}%")
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
        }

        return response()->json($results);
    }
}
