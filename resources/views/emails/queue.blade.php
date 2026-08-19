<x-app-layout>
    @php
    $sendingService = app(\App\Services\EmailSendingService::class);
    $items = \App\Models\EmailQueue::with('vendor')->whereIn('status', ['pending', 'scheduled', 'sending'])->orderBy('scheduled_at')->orderBy('id')->paginate(25);
    $queueStatusColors = ['pending' => 'yellow', 'scheduled' => 'blue', 'sending' => 'indigo'];
    @endphp

    <x-page-header title="Sending Queue" :back="route('dashboard')" :count="$items->total() . ' in queue'">
        @if($sendingService->isSendingPaused())
        <x-badge color="red">Paused</x-badge>
        @else
        <x-badge color="green">Active</x-badge>
        @endif
    </x-page-header>

    <div class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <x-stat-card label="Pending" value="{{ \App\Models\EmailQueue::where('status', 'pending')->count() }}" color="yellow" />
            <x-stat-card label="Scheduled" value="{{ \App\Models\EmailQueue::where('status', 'scheduled')->count() }}" color="blue" />
            <x-stat-card label="Sending" value="{{ \App\Models\EmailQueue::where('status', 'sending')->count() }}" color="indigo" />
            <x-stat-card label="Sent" value="{{ \App\Models\EmailQueue::where('status', 'sent')->count() }}" color="green" />
            <x-stat-card label="Failed" value="{{ \App\Models\EmailQueue::where('status', 'failed')->count() }}" color="red" />
            <x-stat-card label="Cancelled" value="{{ \App\Models\EmailQueue::where('status', 'cancelled')->count() }}" color="gray" />
        </div>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $item->vendor?->brand_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->recipient_email }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->subject }}</td>
                            <td class="px-4 py-3"><x-badge :color="$queueStatusColors[$item->status] ?? 'gray'">{{ ucfirst($item->status) }}</x-badge></td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->scheduled_at?->format('M d, H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->attempts }}</td>
                            <td class="px-4 py-3 text-sm text-red-500 max-w-xs truncate">{{ $item->last_error ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8">
                            <x-empty-state icon="inbox" title="Queue is empty" description="Pending and scheduled emails will appear here." />
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $items->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
