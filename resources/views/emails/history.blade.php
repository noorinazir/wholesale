<x-app-layout>
    @php
    $logs = \App\Models\EmailLog::with('vendor')->latest()->paginate(25);
    $logStatusColors = ['sent' => 'green', 'failed' => 'red', 'bounced' => 'orange', 'pending' => 'yellow', 'cancelled' => 'gray'];
    @endphp

    <x-page-header title="Email History" :back="route('dashboard')" :count="$logs->total() . ' total'" />

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $log->vendor?->brand_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $log->recipient }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $log->subject }}</td>
                        <td class="px-4 py-3"><x-badge :color="$logStatusColors[$log->status] ?? 'gray'">{{ ucfirst($log->status) }}</x-badge></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $log->sent_at?->format('M d, Y H:i') ?? $log->created_at?->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-red-500 max-w-xs truncate">{{ $log->error ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8">
                        <x-empty-state icon="archive" title="No email history" description="All email activity logs will appear here." />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $logs->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
