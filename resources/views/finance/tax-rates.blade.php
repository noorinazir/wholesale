<x-app-layout>
    <x-page-header title="US Sales Tax Rates" :back="route('dashboard')" :count="$rates->count() . ' states'">
        <form method="POST" action="{{ route('finance.tax.seed') }}" class="inline">
            @csrf
            <x-button variant="primary" type="submit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Seed All US States
            </x-button>
        </form>
    </x-page-header>

    <div class="space-y-4">
        @if(session('status'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">State</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Code</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">State Rate</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Local Rate</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Combined</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Marketplace Facilitator</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($rates as $rate)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $rate->state_name }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ $rate->state_code }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">{{ number_format($rate->sales_tax_rate, 2) }}%</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-500">{{ number_format($rate->additional_rate, 2) }}%</td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-800 dark:text-gray-200">{{ number_format($rate->combined_rate, 2) }}%</td>
                            <td class="px-4 py-3 text-center">
                                @if($rate->has_marketplace_facilitator)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Yes
                                </span>
                                @else
                                <span class="text-xs text-gray-400">No</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">No tax rates configured yet.</div>
                                <div class="text-xs text-gray-400">Click "Seed All US States" to populate all 50 states + DC with current sales tax rates.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                    <p><strong>Marketplace Facilitator:</strong> In these states, Amazon is responsible for collecting and remitting sales tax on your behalf. You do not need to collect tax separately for orders shipped to these states.</p>
                    <p><strong>No Sales Tax States:</strong> AK, DE, MT, NH, OR do not have state sales tax. Local jurisdictions may still have applicable taxes.</p>
                    <p><strong>Note:</strong> Tax rates are approximate and should be verified with your accountant or tax advisor before filing.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
