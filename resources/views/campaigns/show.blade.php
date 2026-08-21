<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('campaigns.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Campaign Details</h2>
            </div>
        </div>
    </x-slot>

    @php
    $campaign = \App\Models\Campaign::with('vendors')->findOrFail(request()->route('id'));
    $kimiService = app(\App\Services\AI\KimiService::class);
    $availableVendors = \App\Models\Vendor::active()->whereNotIn('id', $campaign->vendors->pluck('id'))->orderBy('brand_name')->limit(500)->get(['id','brand_name','contact_email']);
    $stats = [
        'selected' => $campaign->vendors()->wherePivot('status', 'selected')->count(),
        'generated' => $campaign->vendors()->wherePivot('status', 'email_generated')->count(),
        'approved' => $campaign->vendors()->wherePivot('status', 'approved')->count(),
        'sent' => $campaign->vendors()->wherePivot('status', 'sent')->count(),
        'failed' => $campaign->vendors()->wherePivot('status', 'failed')->count(),
    ];
    @endphp

    <div class="space-y-6">
        <!-- Campaign Info & Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $campaign->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $campaign->objective }}</p>
                    @if($campaign->description)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ $campaign->description }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $campaign->status === 'active' ? 'bg-green-100 text-green-700' : ($campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst($campaign->status) }}</span>
                    @if($campaign->status === 'draft' || $campaign->status === 'paused')
                    <form method="POST" action="{{ route('campaigns.show', $campaign->id) }}/start">
                        @csrf
                        <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Start</button>
                    </form>
                    @endif
                    @if($campaign->status === 'active')
                    <form method="POST" action="{{ route('campaigns.show', $campaign->id) }}/pause">
                        @csrf
                        <button type="submit" class="px-3 py-1 bg-yellow-600 text-white rounded-lg text-sm font-medium hover:bg-yellow-700">Pause</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <x-stat-card label="Selected" value="{{ $stats['selected'] }}" color="gray" />
            <x-stat-card label="Generated" value="{{ $stats['generated'] }}" color="indigo" />
            <x-stat-card label="Approved" value="{{ $stats['approved'] }}" color="blue" />
            <x-stat-card label="Sent" value="{{ $stats['sent'] }}" color="green" />
            <x-stat-card label="Failed" value="{{ $stats['failed'] }}" color="red" />
        </div>

        <!-- Automation Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Automation Settings</h3>
            <form method="POST" action="{{ route('campaigns.show', $campaign->id) }}/update-automation" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <input type="checkbox" name="auto_approve" {{ $campaign->auto_approve ? 'checked' : '' }} class="mt-0.5 rounded border-gray-300">
                        <div>
                            <div class="text-sm font-medium text-gray-800 dark:text-gray-200">Auto-Approve & Queue</div>
                            <div class="text-xs text-gray-500">Automatically approve AI-generated emails and queue them for sending without manual review.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <input type="checkbox" name="auto_followup_enabled" {{ $campaign->auto_followup_enabled ? 'checked' : '' }} class="mt-0.5 rounded border-gray-300">
                        <div>
                            <div class="text-sm font-medium text-gray-800 dark:text-gray-200">Auto Follow-Up</div>
                            <div class="text-xs text-gray-500">Automatically send follow-up emails if no reply is received.</div>
                        </div>
                    </label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Follow-up Delay (days)</label>
                        <input type="number" name="followup_delay_days" min="1" max="30" value="{{ $campaign->followup_delay_days ?? 5 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                        <p class="text-[10px] text-gray-400 mt-1">Days to wait before sending each follow-up</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Max Follow-Ups</label>
                        <input type="number" name="max_followups" min="0" max="10" value="{{ $campaign->max_followups ?? 2 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                        <p class="text-[10px] text-gray-400 mt-1">Maximum number of follow-up emails per vendor</p>
                    </div>
                </div>
                <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save Automation Settings</button>
            </form>
        </div>

        <!-- Add Vendors -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Add Vendors to Campaign</h3>
            @if($availableVendors->isNotEmpty())
            <form method="POST" action="{{ route('campaigns.show', $campaign->id) }}/add-vendors" class="space-y-3">
                @csrf
                <div class="max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-3 space-y-1">
                    @foreach($availableVendors as $v)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="vendor_ids[]" value="{{ $v->id }}" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $v->brand_name }}{{ $v->contact_email ? ' (' . $v->contact_email . ')' : '' }}</span>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Add Selected Vendors</button>
            </form>
            @else
            <p class="text-sm text-gray-500">No available vendors to add.</p>
            @endif
        </div>

        <!-- Bulk Generate Emails -->
        @if($stats['selected'] > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Bulk Generate Emails</h3>
            <form method="POST" action="{{ route('campaigns.show', $campaign->id) }}/generate-emails" class="flex flex-wrap gap-3 items-end" onsubmit="return confirm('Generate emails for {{ $stats['selected'] }} vendors?')">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objective</label>
                    <select name="objective" class="rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                        <option value="Wholesale Authorization">Wholesale Authorization</option>
                        <option value="Reseller Authorization">Reseller Authorization</option>
                        <option value="Amazon Authorization">Amazon Authorization</option>
                        <option value="Distributor Pricing">Distributor Pricing</option>
                        <option value="Product Catalog Request">Product Catalog Request</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tone</label>
                    <select name="tone" class="rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                        <option value="professional">Professional</option>
                        <option value="friendly">Friendly</option>
                        <option value="concise">Concise</option>
                        <option value="formal">Formal</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 pb-2">
                    <input type="checkbox" name="use_ai" class="rounded border-gray-300" @disabled(!$kimiService->isConfigured())>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Use AI personalization {{ !$kimiService->isConfigured() ? '(not configured)' : '(uses AI credits)' }}</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">Generate for {{ $stats['selected'] }} Vendors</button>
            </form>
            <p class="text-xs text-gray-500 mt-2">By default, emails are generated from templates with vendor details (name, brand, category) — no AI tokens used. Check "Use AI personalization" only if you want AI-generated opening lines.</p>
        </div>
        @endif

        <!-- Vendors in Campaign -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Vendors in Campaign ({{ $campaign->vendors->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent At</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($campaign->vendors as $vendor)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                                <a href="{{ route('vendors.show', $vendor->id) }}" class="text-indigo-600 hover:underline">{{ $vendor->brand_name }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $vendor->contact_email ?? '-' }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">{{ ucfirst(str_replace('_', ' ', $vendor->pivot->status)) }}</span></td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $vendor->pivot->sent_at?->format('M d, Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <form method="POST" action="{{ route('campaigns.show', $campaign->id) }}/remove-vendor" class="inline" onsubmit="return confirm('Remove this vendor from campaign?')">
                                    @csrf
                                    <input type="hidden" name="vendor_id" value="{{ $vendor->id }}">
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8">
                            <x-empty-state icon="users" title="No vendors in campaign" description="Add vendors to this campaign to generate and send emails." />
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
