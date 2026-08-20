<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
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
            'is_active' => 'boolean',
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
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $template->update($validated);

        $this->auditLog->log('updated', 'Template', $template->name);

        return back()->with('status', 'Template updated.');
    }
}
