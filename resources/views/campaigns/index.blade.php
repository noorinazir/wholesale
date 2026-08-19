<x-app-layout>
    @php
    $campaigns = \App\Models\Campaign::withCount(['vendors', 'emailLogs as sent_count' => fn($q) => $q->where('status', 'sent')])->latest()->paginate(25);
    $campaignStatusColors = ['draft' => 'gray', 'active' => 'green', 'paused' => 'yellow', 'completed' => 'blue', 'cancelled' => 'red'];
    @endphp

    <x-page-header title="Campaigns" :back="route('dashboard')" :count="$campaigns->total() . ' total'">
        <x-button variant="primary" href="{{ route('campaigns.create') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Campaign
        </x-button>
    </x-page-header>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objective</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendors</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($campaigns as $campaign)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $campaign->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $campaign->objective }}</td>
                        <td class="px-4 py-3">
                            <x-badge :color="$campaignStatusColors[$campaign->status] ?? 'gray'">{{ ucfirst($campaign->status) }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $campaign->vendors_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $campaign->sent_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $campaign->started_at?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end">
                                <a href="{{ route('campaigns.show', $campaign->id) }}" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8">
                        <x-empty-state icon="folder" title="No campaigns yet" description="Create your first campaign to start sending outreach emails." actionText="Create Campaign" actionHref="{{ route('campaigns.create') }}" />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $campaigns->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
