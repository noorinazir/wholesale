<x-app-layout>
    <x-page-header title="Settlement Reconciliation" :back="route('finance.dashboard')">
    </x-page-header>

    <div class="space-y-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-400">
            Compare Amazon settlement totals against recorded sales and expenses. Discrepancies indicate missing or incorrect data.
        </div>

        @forelse($reconciliation as $row)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $row['import']->file_name }}</div>
                    <div class="text-xs text-gray-400">Settlement: {{ $row['import']->settlement_id ?? 'N/A' }} • {{ $row['import']->settlement_start_date?->format('M d') }} – {{ $row['import']->settlement_end_date?->format('M d, Y') }}</div>
                </div>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">Imported</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase">Metric</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase">Amazon Settlement</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase">System Recorded</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase">Difference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-medium">Revenue</td>
                            <td class="px-4 py-2 text-right text-green-600 dark:text-green-400">${{ number_format($row['settlement_revenue'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($row['recorded_revenue'], 2) }}</td>
                            <td class="px-4 py-2 text-right
                                @if(abs($row['revenue_diff']) > 0.01) text-amber-600 dark:text-amber-400 font-medium @else text-gray-400 @endif
                            ">
                                @if(abs($row['revenue_diff']) > 0.01) ⚠ @endif
                                ${{ number_format(abs($row['revenue_diff']), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-medium">Fees</td>
                            <td class="px-4 py-2 text-right text-red-600 dark:text-red-400">${{ number_format($row['settlement_fees'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($row['recorded_fees'], 2) }}</td>
                            <td class="px-4 py-2 text-right
                                @if(abs($row['fees_diff']) > 0.01) text-amber-600 dark:text-amber-400 font-medium @else text-gray-400 @endif
                            ">
                                @if(abs($row['fees_diff']) > 0.01) ⚠ @endif
                                ${{ number_format(abs($row['fees_diff']), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-medium">Refunds</td>
                            <td class="px-4 py-2 text-right text-orange-600 dark:text-orange-400">${{ number_format($row['settlement_refunds'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-400">—</td>
                            <td class="px-4 py-2 text-right text-gray-400">—</td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-700/30">
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200 font-bold">Net Settlement</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-800 dark:text-gray-200">${{ number_format($row['settlement_net'], 2) }}</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-800 dark:text-gray-200">${{ number_format($row['recorded_revenue'] - $row['recorded_fees'], 2) }}</td>
                            <td class="px-4 py-2 text-right font-bold
                                @if(abs($row['settlement_net'] - ($row['recorded_revenue'] - $row['recorded_fees'])) > 0.01) text-amber-600 dark:text-amber-400 @else text-gray-400 @endif
                            ">
                                ${{ number_format(abs($row['settlement_net'] - ($row['recorded_revenue'] - $row['recorded_fees'])), 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <p class="text-sm text-gray-400">No imported settlements to reconcile yet.</p>
            <a href="{{ route('finance.settlements.upload') }}" class="mt-3 inline-flex px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Upload Settlement</a>
        </div>
        @endforelse
    </div>
</x-app-layout>
