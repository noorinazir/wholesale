<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyDocumentController extends Controller
{
    public function __construct(private AuditLogService $auditLog)
    {
    }

    public function upload(Request $request): RedirectResponse
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

        $doc = CompanyDocument::create([
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

    public function delete(int $id): RedirectResponse
    {
        $doc = CompanyDocument::findOrFail($id);

        if (!Storage::disk('local')->exists($doc->file_path)) {
            return back()->with('error', 'Document file was not found on disk.');
        }

        Storage::disk('local')->delete($doc->file_path);
        $doc->delete();

        $this->auditLog->log('deleted', 'Company Document', "{$doc->type}: {$doc->original_name}");

        return back()->with('status', 'Document deleted.');
    }

    public function download(Request $request, int $id)
    {
        abort_unless($request->hasValidSignature(), 403);

        $doc = CompanyDocument::findOrFail($id);

        if (!Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('local')->download(
            $doc->file_path,
            $doc->original_name,
            ['Content-Type' => $doc->mime_type]
        );
    }
}
