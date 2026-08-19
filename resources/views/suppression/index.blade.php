<x-app-layout>
    @php
    $suppressed = \App\Models\SuppressionList::with('vendor')->latest()->paginate(25);
    @endphp

    <x-page-header title="Suppression List" :back="route('dashboard')" :count="$suppressed->total() . ' total'" />

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($suppressed as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $item->email }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->vendor?->brand_name ?? '-' }}</td>
                        <td class="px-4 py-3"><x-badge color="red">{{ ucfirst(str_replace('_', ' ', $item->reason)) }}</x-badge></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->notes ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->suppressed_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8">
                        <x-empty-state icon="ban" title="No suppressed emails" description="Emails that bounce or opt-out will be listed here." />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppressed->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $suppressed->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
