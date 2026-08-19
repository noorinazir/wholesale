<x-app-layout>
    @php
    $s = $summary;
    $netProfit = $s['net_profit'];
    $margin = $s['margin_percent'];
    @endphp

    <x-page-header title="Profit & Loss Report" :back="route('dashboard')">
    </x-page-header>

    <div class="space-y-6">
        <!-- Period Selector -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <select name="period" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3" onchange="this.form.submit()">
                    <option value="week" @selected($period === 'week')>This Week</option>
                    <option value="month" @selected($period === 'month')>This Month</option>
                    <option value="quarter" @selected($period === 'quarter')>This Quarter</option>
                    <option value="year" @selected($period === 'year')>This Year</option>
                    <option value="all" @selected($period === 'all')>All Time</option>
                </select>
                <input type="date" name="date_from" value="{{ $startDate->format('Y-m-d') }}" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                <input type="date" name="date_to" value="{{ $endDate->format('Y-m-d') }}" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Apply</button>
            </form>
        </div>

        <!-- Top-Level Summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Revenue</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">${{ number_format($s['total_revenue'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">{{ $s['orders_count'] }} orders · {{ $s['units_sold'] }} units</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Cost</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">${{ number_format($s['total_cost'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">COGS + Expenses</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net Profit</div>
                <div class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($netProfit, 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Margin</div>
                <div class="text-2xl font-bold {{ $margin >= 15 ? 'text-green-600 dark:text-green-400' : ($margin >= 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">{{ number_format($margin, 1) }}%</div>
            </div>
        </div>

        <!-- P&L Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue & Costs -->
            <x-card>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Revenue & Cost Breakdown</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between font-semibold pb-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-700 dark:text-gray-300">Revenue</span>
                        <span class="text-green-600 dark:text-green-400">${{ number_format($s['total_revenue'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span class="pl-4">Product Cost (COGS)</span>
                        <span class="text-red-600 dark:text-red-400">-${{ number_format($s['total_product_cost'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span class="pl-4">Amazon Fees (incl. referral)</span>
                        <span class="text-red-600 dark:text-red-400">-${{ number_format($s['total_fba_fees'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span class="pl-4">Shipping</span>
                        <span class="text-red-600 dark:text-red-400">-${{ number_format($s['total_shipping'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span class="pl-4">Labeling</span>
                        <span class="text-red-600 dark:text-red-400">-${{ number_format($s['total_labeling'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span class="pl-4">Operation Costs</span>
                        <span class="text-red-600 dark:text-red-400">-${{ number_format($s['total_operation_cost'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span class="pl-4">Other Costs</span>
                        <span class="text-red-600 dark:text-red-400">-${{ number_format($s['total_other_costs'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span class="pl-4">Operating Expenses</span>
                        <span class="text-red-600 dark:text-red-400">-${{ number_format($s['total_expenses'], 2) }}</span>
                    </div>
                    @if($s['total_returns_cost'] > 0)
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span class="pl-4">Returns/Refunds</span>
                        <span class="text-red-600 dark:text-red-400">-${{ number_format($s['total_returns_cost'], 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-semibold pt-2 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-gray-700 dark:text-gray-300">Gross Profit</span>
                        <span class="{{ $s['gross_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($s['gross_profit'], 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold pt-2 border-t-2 border-gray-200 dark:border-gray-600">
                        <span class="text-gray-900 dark:text-gray-100">Net Profit</span>
                        <span class="{{ $netProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($netProfit, 2) }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Tax & Expense Breakdown -->
            <div class="space-y-6">
                <x-card>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Tax Summary (US)</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Tax Collected</span><span class="text-gray-800 dark:text-gray-200">${{ number_format($s['tax_collected'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Tax Owed</span><span class="text-gray-800 dark:text-gray-200">${{ number_format($s['tax_owed'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Units Returned</span><span class="text-gray-800 dark:text-gray-200">{{ $s['units_returned'] }}</span></div>
                    </div>
                </x-card>

                <x-card>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Expense Breakdown</h3>
                    @if(!empty($expenseBreakdown))
                    <div class="space-y-2 text-sm">
                        @foreach($expenseBreakdown as $label => $amount)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                            <span class="text-red-600 dark:text-red-400">${{ number_format($amount, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-sm text-gray-400 text-center py-4">No expenses in this period</div>
                    @endif
                </x-card>
            </div>
        </div>

        <!-- Monthly Trend Chart -->
        <x-card>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">12-Month Profit Trend</h3>
            <div class="space-y-2">
                @php $maxRevenue = max(array_column($monthlyTrend, 'revenue')) ?: 1; @endphp
                @foreach($monthlyTrend as $month)
                <div class="flex items-center gap-3">
                    <div class="w-20 text-xs text-gray-500 dark:text-gray-400 shrink-0">{{ $month['label'] }}</div>
                    <div class="flex-1 relative h-6 bg-gray-50 dark:bg-gray-700/30 rounded-lg overflow-hidden">
                        <div class="absolute inset-y-0 left-0 rounded-lg transition-all {{ $month['profit'] >= 0 ? 'bg-green-400 dark:bg-green-600/50' : 'bg-red-400 dark:bg-red-600/50' }}" style="width: {{ min(100, abs($month['revenue']) / $maxRevenue * 100) }}%"></div>
                        <div class="absolute inset-0 flex items-center justify-between px-2 text-xs font-medium">
                            <span class="text-gray-700 dark:text-gray-300">${{ number_format($month['revenue'], 0) }}</span>
                            <span class="{{ $month['profit'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">{{ $month['margin'] }}% · ${{ number_format($month['profit'], 0) }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>

        <!-- Per-Vendor Breakdown -->
        <x-card>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Profit by Vendor</h3>
            @if(!empty($vendorBreakdown))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Vendor</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Revenue</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Cost</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Profit</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Margin</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Units</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($vendorBreakdown as $v)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $v['vendor_name'] }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">${{ number_format($v['total_revenue'], 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right text-red-600 dark:text-red-400">${{ number_format($v['total_cost'], 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right font-medium {{ $v['net_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($v['net_profit'], 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right {{ $v['margin_percent'] >= 15 ? 'text-green-600' : ($v['margin_percent'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">{{ number_format($v['margin_percent'], 1) }}%</td>
                            <td class="px-3 py-2 text-sm text-center text-gray-500">{{ $v['units_sold'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-sm text-gray-400 text-center py-4">No vendor data in this period</div>
            @endif
        </x-card>

        <!-- Per-Product Breakdown -->
        <x-card>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Profit by Product</h3>
            @if(!empty($productBreakdown))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Product</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Vendor</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Revenue</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Profit</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Margin</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Units</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($productBreakdown as $p)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ $p['product_name'] }}
                                @if($p['asin'])<span class="text-xs text-gray-400 font-mono ml-1">{{ $p['asin'] }}</span>@endif
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-500">{{ $p['vendor_name'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">${{ number_format($p['total_revenue'], 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right font-medium {{ $p['net_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($p['net_profit'], 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right {{ $p['margin_percent'] >= 15 ? 'text-green-600' : ($p['margin_percent'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">{{ number_format($p['margin_percent'], 1) }}%</td>
                            <td class="px-3 py-2 text-sm text-center text-gray-500">{{ $p['units_sold'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-sm text-gray-400 text-center py-4">No product data in this period</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
