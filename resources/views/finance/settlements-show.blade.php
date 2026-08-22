<x-app-layout>
    <x-page-header title="Settlement Review" :back="route('finance.settlements.index')">
        @if($import->status === 'parsed')
        <form method="POST" action="{{ route('finance.settlements.commit', $import->id) }}" onsubmit="return confirm('Commit this settlement? This will create expense records and update orders.')" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Commit Import
            </button>
        </form>
        @endif
    </x-page-header>

    <div class="space-y-4">
        @if(session('status'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4 text-sm text-green-700 dark:text-green-400">{{ session('status') }}</div>
        @endif

        <!-- Import Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">File</div>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-200 mt-1">{{ $import->file_name }}</div>
                <div class="text-xs text-gray-400 mt-1">Settlement ID: {{ $import->settlement_id ?? 'N/A' }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">Period</div>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-200 mt-1">
                    @if($import->settlement_start_date)
                        {{ $import->settlement_start_date->format('M d') }} – {{ $import->settlement_end_date?->format('M d, Y') }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">Total Amount</div>
                <div class="text-lg font-bold text-gray-800 dark:text-gray-200 mt-1">${{ number_format((float)$import->total_amount, 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">Status</div>
                @php
                    $statusColors = [
                        'pending' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                        'parsed' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        'imported' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'failed' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    ];
                    $color = $statusColors[$import->status] ?? $statusColors['pending'];
                @endphp
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $color }} mt-1">{{ ucfirst($import->status) }}</span>
            </div>
        </div>

        <!-- Match Stats -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['matched_orders'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Orders Matched</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['matched_products'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Products Matched</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['duplicates'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Duplicates</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                <div class="text-2xl font-bold text-gray-400">{{ $stats['unmatched'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Unmatched</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['fees'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Fee Transactions</div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                <div class="text-xs text-green-600 dark:text-green-400">Settlement Revenue</div>
                <div class="text-xl font-bold text-green-700 dark:text-green-300 mt-1">${{ number_format($stats['total_revenue'], 2) }}</div>
                <div class="text-xs text-green-500 dark:text-green-500 mt-1">{{ $stats['orders'] }} order transactions</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                <div class="text-xs text-red-600 dark:text-red-400">Settlement Fees</div>
                <div class="text-xl font-bold text-red-700 dark:text-red-300 mt-1">${{ number_format($stats['total_fees'], 2) }}</div>
                <div class="text-xs text-red-500 dark:text-red-500 mt-1">{{ $stats['fees'] }} fee transactions</div>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-4">
                <div class="text-xs text-orange-600 dark:text-orange-400">Settlement Refunds</div>
                <div class="text-xl font-bold text-orange-700 dark:text-orange-300 mt-1">${{ number_format($stats['total_refunds'], 2) }}</div>
                <div class="text-xs text-orange-500 dark:text-orange-500 mt-1">{{ $stats['refunds'] }} refund transactions</div>
            </div>
        </div>

        @if($import->status === 'parsed')
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-xs text-blue-700 dark:text-blue-400">
            <strong>Review the transactions below.</strong> When ready, click "Commit Import" to create expense records for fees and reconcile orders. Duplicates will be automatically skipped.
        </div>
        @endif

        <!-- Transactions Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Transactions ({{ $import->transactions->count() }})</h3>
            </div>
            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Type</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Description</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Order ID / ASIN</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Match</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($import->transactions as $txn)
                        @php
                            $matchColors = \App\Models\AmazonSettlementTransaction::matchStatusColors();
                            $mc = $matchColors[$txn->match_status] ?? $matchColors['unmatched'];
                            $typeLabels = \App\Models\AmazonSettlementTransaction::transactionTypeLabels();
                            $typeLabel = $typeLabels[$txn->transaction_type] ?? ucfirst($txn->transaction_type);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 text-xs">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium
                                    @if($txn->transaction_type === 'order') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($txn->transaction_type === 'refund') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                    @elseif(in_array($txn->transaction_type, ['fee','service_fee','storage_fee','advertising'])) bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @else bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 @endif
                                ">{{ $typeLabel }}</span>
                                @if($txn->fee_type)
                                <div class="text-[10px] text-gray-400 mt-0.5">{{ ucfirst($txn->fee_type) }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300 max-w-xs truncate" title="{{ $txn->transaction_description }}">
                                {{ $txn->transaction_description ?? $txn->product_name ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                                @if($txn->order_id)
                                    <div>{{ $txn->order_id }}</div>
                                @endif
                                @if($txn->asin)
                                    <div class="text-gray-400 text-[10px]">ASIN: {{ $txn->asin }}</div>
                                @endif
                                @if($txn->sku)
                                    <div class="text-gray-400 text-[10px]">SKU: {{ $txn->sku }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-right font-medium
                                @if((float)$txn->amount < 0) text-red-600 dark:text-red-400 @else text-green-600 dark:text-green-400 @endif
                            ">
                                ${{ number_format((float)$txn->amount, 2) }}
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $txn->posted_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium {{ $mc['bg'] }} {{ $mc['text'] }}">
                                    {{ \App\Models\AmazonSettlementTransaction::matchStatusLabels()[$txn->match_status] ?? $txn->match_status }}
                                </span>
                                @if($txn->match_notes)
                                <div class="text-[10px] text-gray-400 mt-0.5 max-w-[150px] truncate" title="{{ $txn->match_notes }}">{{ $txn->match_notes }}</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
