<x-app-layout>
    @php
    $items = \App\Models\EmailQueue::with('vendor')->where('status', 'sent')->latest('sent_at')->paginate(25);
    @endphp

    <x-page-header title="Sent Emails" :back="route('dashboard')" :count="$items->total() . ' total'" />

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMTP Response</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $item->vendor?->brand_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->recipient_email }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->subject }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->sent_at?->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3"><x-badge color="green">{{ $item->smtp_response ?? 'Accepted' }}</x-badge></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8">
                        <x-empty-state icon="mail" title="No sent emails" description="Successfully sent emails will appear here." />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $items->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
