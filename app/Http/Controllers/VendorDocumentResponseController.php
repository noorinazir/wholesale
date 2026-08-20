<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Vendor;
use App\Services\AI\EmailPersonalizationService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class VendorDocumentResponseController extends Controller
{
    public function __construct(private AuditLogService $auditLog)
    {
    }

    public function generate(Request $request, int $id)
    {
        $vendor = Vendor::findOrFail($id);
        $this->authorize('generateDocumentResponse', $vendor);

        $request->validate([
            'reply_id' => 'required|exists:email_replies,id',
        ]);

        $reply = \App\Models\EmailReply::where('id', $request->input('reply_id'))
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        $company = Company::where('is_active', true)->first();
        $personalizationService = app(EmailPersonalizationService::class);

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
}
