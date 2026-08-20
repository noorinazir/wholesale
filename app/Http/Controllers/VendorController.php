<?php

namespace App\Http\Controllers;

use App\Enums\EmailStatus;
use App\Enums\Priority;
use App\Enums\VendorStatus;
use App\Http\Requests\VendorRequest;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Vendor;
use App\Services\AI\EmailPersonalizationService;
use App\Services\AuditLogService;
use App\Services\CsvImportService;
use App\Services\VendorShowService;
use App\Support\CategoryOptions;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(
        private AuditLogService $auditLog,
        private VendorShowService $vendorShowService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Vendor::class);

        $query = Vendor::query()->select([
            'id', 'brand_name', 'company_name', 'website', 'contact_name', 'contact_email', 'phone',
            'product_category', 'country', 'status', 'email_status', 'priority', 'created_at',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
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
                $query->orderByRaw("CASE WHEN priority = 'critical' THEN 1 WHEN priority = 'high' THEN 2 WHEN priority = 'medium' THEN 3 WHEN priority = 'low' THEN 4 ELSE 5 END")
                    ->orderByDesc('created_at');
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

    public function import(Request $request, CsvImportService $importService)
    {
        $this->authorize('create', Vendor::class);

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

    public function store(VendorRequest $request)
    {
        $this->authorize('create', Vendor::class);

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

    public function show($id)
    {
        $vendor = Vendor::findOrFail($id);
        $this->authorize('view', $vendor);

        $data = $this->vendorShowService->getVendorShowData($vendor->id);

        return view('vendors.show', $data);
    }

    public function update(VendorRequest $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $this->authorize('update', $vendor);

        $validated = $request->validated();
        $vendor->update($validated);

        $this->auditLog->log('updated', 'Vendor', $vendor->brand_name);

        return back()->with('status', 'Vendor updated successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $this->authorize('updateStatus', $vendor);

        $validated = $request->validate([
            'status' => 'required|in:new,researching,ready_to_contact,contacted,replied,interested,not_interested,approved,rejected,follow_up_required,opted_out,invalid_email,archived',
        ]);

        $vendor->update($validated);
        $this->auditLog->log('status_changed', 'Vendor', "{$vendor->brand_name}: {$validated['status']}");

        return back()->with('status', 'Status updated to ' . ucfirst(str_replace('_', ' ', $validated['status'])));
    }

    public function updatePriority(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $this->authorize('updatePriority', $vendor);

        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $vendor->update($validated);
        $this->auditLog->log('priority_changed', 'Vendor', "{$vendor->brand_name}: {$validated['priority']}");

        return back()->with('status', 'Priority updated to ' . ucfirst($validated['priority']));
    }

    public function action(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $action = $request->input('action');

        if ($action === 'research') {
            $this->authorize('research', $vendor);
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

    public function destroy(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $this->authorize('delete', $vendor);

        $vendor->update(['status' => 'archived']);
        $this->auditLog->log('archived', 'Vendor', $vendor->brand_name);

        return redirect()->route('vendors.index')->with('status', 'Vendor archived.');
    }

    public function bulkAction(Request $request)
    {
        $this->authorize('bulk', Vendor::class);

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
            $this->auditLog->log('bulk_campaign', 'Vendor', 'Assigned ' . count($newIds) . " vendors to {$campaign->name}");

            return back()->with('status', 'Assigned ' . count($newIds) . ' vendors to campaign.');
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

    public function export()
    {
        $this->authorize('export', Vendor::class);

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
}
