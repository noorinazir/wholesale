<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\AI\EmailPersonalizationService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(private AuditLogService $auditLog)
    {
    }

    public function store(Request $request)
    {
        $this->authorize('create', EmailTemplate::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'subject_template' => 'required|string',
            'body_template' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $template = EmailTemplate::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'is_active' => $request->has('is_active'),
        ]));

        $this->auditLog->log('created', 'Template', $template->name);

        return redirect()->route('templates.index')->with('status', 'Template created.');
    }

    public function update(Request $request, int $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'subject_template' => 'required|string',
            'body_template' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $template->update($validated);

        $this->auditLog->log('updated', 'Template', $template->name);

        return back()->with('status', 'Template updated.');
    }

    public function aiGenerate(Request $request)
    {
        $this->authorize('create', EmailTemplate::class);

        $validated = $request->validate([
            'type' => 'required|string',
            'tone' => 'required|string',
            'custom_instructions' => 'nullable|string',
        ]);

        $service = app(EmailPersonalizationService::class);
        $result = $service->generateTemplate(
            $validated['type'],
            $validated['tone'],
            $validated['custom_instructions'] ?? null,
            auth()->user()
        );

        if ($result['success']) {
            return back()->with('ai_template', $result['template'])->withInput();
        }

        return back()->with('ai_error', $result['error'] ?? 'AI generation failed')->withInput();
    }
}
