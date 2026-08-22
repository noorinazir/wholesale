<x-app-layout>
    <x-page-header title="Amazon Settlement Imports" :back="route('finance.dashboard')">
        <a href="{{ route('finance.settlements.upload') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Upload Settlement
        </a>
    </x-page-header>

    <div class="space-y-4">
        @if(session('status'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4 text-sm text-green-700 dark:text-green-400">{{ session('status') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">File</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Settlement ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Period</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Transactions</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($imports as $import)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <div class="font-medium">{{ $import->file_name }}</div>
                                <div class="text-xs text-gray-400">{{ $import->created_at->format('M d, Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $import->settlement_id ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if($import->settlement_start_date)
                                    {{ $import->settlement_start_date->format('M d') }} – {{ $import->settlement_end_date?->format('M d, Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-700 dark:text-gray-300">${{ number_format((float)$import->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300">
                                <div>{{ $import->transactions_count }}</div>
                                <div class="text-[10px] text-gray-400">
                                    {{ $import->matched_orders_count }} matched · {{ $import->duplicates_count }} dup · {{ $import->unmatched_count }} unmatched
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                        'parsed' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'imported' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'failed' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                    $color = $statusColors[$import->status] ?? $statusColors['pending'];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">{{ ucfirst($import->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('finance.settlements.show', $import->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">View</a>
                                @if($import->status === 'parsed')
                                <form method="POST" action="{{ route('finance.settlements.commit', $import->id) }}" class="inline" onsubmit="return confirm('Commit this settlement import? This will create expenses and update orders.')">
                                    @csrf
                                    <button type="submit" class="text-green-600 dark:text-green-400 hover:underline text-xs font-medium">Commit</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('finance.settlements.destroy', $import->id) }}" class="inline" onsubmit="return confirm('Delete this import? This will also delete any auto-created expenses.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-xs font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">
                                No settlement imports yet. <a href="{{ route('finance.settlements.upload') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Upload your first Amazon settlement file</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $imports->links() }}
    </div>
</x-app-layout>
