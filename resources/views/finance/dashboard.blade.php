<x-app-layout>
    <x-page-header title="Finance Dashboard" :back="route('dashboard')">
        <div class="flex items-center gap-2">
            @if($amazonConfigured)
            <form method="POST" action="{{ route('settings.amazon.sync') }}" class="inline">
                @csrf
                <input type="hidden" name="type" value="full">
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sync Amazon
                </button>
            </form>
            @else
            <a href="{{ route('settings.amazon') }}" class="px-3.5 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Connect Amazon API
            </a>
            @endif
            <a href="{{ route('finance.po.create') }}" class="px-3.5 py-2 rounded-lg bg-purple-600 text-white text-sm font-medium hover:bg-purple-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Create PO
            </a>
            <a href="{{ route('finance.sales.create') }}" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Record Sale
            </a>
        </div>
    </x-page-header>

    <div class="space-y-4">
        <!-- KPI Cards Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Revenue This Month -->
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-8 -mt-8"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-indigo-100">Revenue (This Month)</div>
                        <span class="text-xs font-medium {{ $revenueTrend >= 0 ? 'text-green-200' : 'text-red-200' }}">{{ $revenueTrend >= 0 ? '↑' : '↓' }} {{ abs(round($revenueTrend)) }}%</span>
                    </div>
                    <div class="text-2xl font-bold">${{ number_format($monthSummary['total_revenue'], 2) }}</div>
                    <div class="text-xs text-indigo-200 mt-1">{{ $monthSummary['orders_count'] }} orders · {{ $monthSummary['units_sold'] }} units</div>
                </div>
            </div>
            <!-- Net Profit This Month -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-8 -mt-8"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-green-100">Net Profit (This Month)</div>
                        <span class="text-xs font-medium {{ $profitTrend >= 0 ? 'text-green-200' : 'text-red-200' }}">{{ $profitTrend >= 0 ? '↑' : '↓' }} {{ abs(round($profitTrend)) }}%</span>
                    </div>
                    <div class="text-2xl font-bold">${{ number_format($monthSummary['net_profit'], 2) }}</div>
                    <div class="text-xs text-green-200 mt-1">Margin: {{ number_format($monthSummary['margin_percent'], 1) }}% · Expenses: ${{ number_format($monthExpenses, 2) }}</div>
                </div>
            </div>
            <!-- Inventory Value -->
            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-4 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-8 -mt-8"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-cyan-100">Inventory Value</div>
                        <svg class="w-4 h-4 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="text-2xl font-bold">${{ number_format($inventoryValue, 2) }}</div>
                    <div class="text-xs text-cyan-200 mt-1">{{ $inventoryUnits }} units in stock · {{ $productCount }} products</div>
                </div>
            </div>
            <!-- Pending POs -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-8 -mt-8"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-purple-100">Pending POs</div>
                        <svg class="w-4 h-4 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="text-2xl font-bold">{{ $pendingPOs }}</div>
                    <div class="text-xs text-purple-200 mt-1">${{ number_format($pendingPOValue, 2) }} value · {{ $pendingReceiptUnits }} units pending receipt</div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-200">${{ number_format($totalRevenue, 0) }}</div>
                    <div class="text-xs text-gray-500">All-Time Revenue</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-lg font-bold {{ $totalProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($totalProfit, 0) }}</div>
                    <div class="text-xs text-gray-500">All-Time Profit</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $totalOrders }}</div>
                    <div class="text-xs text-gray-500">Total Orders · {{ $totalReturns }} returns</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-lg font-bold text-red-600 dark:text-red-400">${{ number_format($monthExpenses, 0) }}</div>
                    <div class="text-xs text-gray-500">Expenses This Month</div>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Left: Charts + Activity -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Revenue & Profit Trend Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Revenue & Profit Trend</h3>
                        <div class="flex items-center gap-3 text-xs">
                            <div class="flex items-center gap-1.5 text-gray-500"><div class="w-3 h-3 rounded bg-indigo-500"></div> Revenue</div>
                            <div class="flex items-center gap-1.5 text-gray-500"><div class="w-3 h-3 rounded bg-green-500"></div> Profit</div>
                        </div>
                    </div>
                    <div class="flex items-end gap-3 h-48">
                        @php $maxRev = max(array_column(array_map(fn($t) => ['r' => $t['revenue']], $monthlyTrend), 'r')) ?: 1; @endphp
                        @foreach($monthlyTrend as $trend)
                        <div class="flex-1 flex flex-col items-center gap-1 group">
                            <div class="text-[10px] text-gray-400 font-medium opacity-0 group-hover:opacity-100 transition-opacity">${{ number_format($trend['profit'], 0) }}</div>
                            <div class="w-full flex flex-col justify-end" style="height: 160px;">
                                <div class="w-full bg-indigo-500 rounded-t transition-all hover:bg-indigo-400" style="height: {{ ($trend['revenue'] / $maxRev) * 100 }}%" title="Revenue: ${{ number_format($trend['revenue'], 2) }}"></div>
                                <div class="w-full bg-green-500 rounded-b transition-all hover:bg-green-400" style="height: {{ max(2, ($trend['profit'] / $maxRev) * 100) }}%" title="Profit: ${{ number_format($trend['profit'], 2) }}"></div>
                            </div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ $trend['label'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Two-column: Recent Sales + Recent POs -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Recent Sales -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Recent Sales</h3>
                            <a href="{{ route('finance.sales.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all →</a>
                        </div>
                        <div class="space-y-2">
                            @forelse($recentSales as $sale)
                            <a href="{{ route('finance.sales.show', $sale->id) }}" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ $sale->product_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $sale->order_date->format('M d') }} · {{ $sale->quantity }}u · {{ $sale->fulfillment_channel }}</div>
                                </div>
                                <div class="text-right ml-2 shrink-0">
                                    <div class="text-sm font-medium {{ $sale->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $sale->net_profit >= 0 ? '+' : '' }}${{ number_format($sale->net_profit, 2) }}</div>
                                    <div class="text-xs text-gray-400">{{ number_format($sale->margin_percent, 0) }}%</div>
                                </div>
                            </a>
                            @empty
                            <div class="text-sm text-gray-400 text-center py-6">No sales recorded yet</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Recent POs -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Recent POs</h3>
                            <a href="{{ route('finance.po.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all →</a>
                        </div>
                        <div class="space-y-2">
                            @forelse($recentPOs as $po)
                            @php
                            $poStatusColor = [
                                'draft' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                'submitted' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'confirmed' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                'in_production' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                'shipped' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                                'received' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'partial_received' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            ][$po->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <a href="{{ route('finance.po.show', $po->id) }}" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-mono font-medium text-gray-700 dark:text-gray-300">{{ $po->po_number }}</span>
                                        <span class="px-1.5 py-0.5 text-[10px] rounded {{ $poStatusColor }}">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $po->vendor?->brand_name }} · {{ $po->items->count() }} items</div>
                                </div>
                                <div class="text-right ml-2 shrink-0">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">${{ number_format($po->total_amount, 0) }}</div>
                                    <div class="text-xs {{ $po->payment_status === 'paid' ? 'text-green-600' : ($po->payment_status === 'partial_paid' ? 'text-yellow-600' : 'text-red-500') }}">{{ ucfirst(str_replace('_', ' ', $po->payment_status)) }}</div>
                                </div>
                            </a>
                            @empty
                            <div class="text-sm text-gray-400 text-center py-6">No POs created yet</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Top Products This Month -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Top Products This Month</h3>
                    <div class="space-y-2">
                        @forelse($topProducts as $idx => $product)
                        <div class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="w-7 h-7 rounded-full {{ $idx === 0 ? 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600' : ($idx === 1 ? 'bg-gray-100 dark:bg-gray-700 text-gray-500' : ($idx === 2 ? 'bg-orange-50 dark:bg-orange-900/30 text-orange-600' : 'bg-gray-50 dark:bg-gray-700/50 text-gray-400')) }} flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $idx + 1 }}</div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ $product['product_name'] }}</div>
                                <div class="text-xs text-gray-400">{{ $product['orders_count'] }} orders · {{ $product['units_sold'] }} units</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-sm font-medium text-green-600 dark:text-green-400">${{ number_format($product['net_profit'], 2) }}</div>
                                <div class="text-xs text-gray-400">{{ number_format($product['margin_percent'], 0) }}% margin</div>
                            </div>
                        </div>
                        @empty
                        <div class="text-sm text-gray-400 text-center py-6">No sales this month yet</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right: Sidebar Widgets -->
            <div class="space-y-4">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Quick Actions</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <a href="{{ route('finance.sales.create') }}" class="flex flex-col items-center gap-1.5 p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300 text-center">Record Sale</span>
                        </a>
                        <a href="{{ route('finance.sales.batch') }}" class="flex flex-col items-center gap-1.5 p-3 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 hover:bg-cyan-100 dark:hover:bg-cyan-900/30 transition-colors">
                            <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300 text-center">Batch Entry</span>
                        </a>
                        <a href="{{ route('finance.sales.import.csv') }}" class="flex flex-col items-center gap-1.5 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300 text-center">Import CSV</span>
                        </a>
                        <a href="{{ route('finance.po.create') }}" class="flex flex-col items-center gap-1.5 p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300 text-center">Create PO</span>
                        </a>
                        <a href="{{ route('finance.tracking') }}" class="flex flex-col items-center gap-1.5 p-3 rounded-lg bg-teal-50 dark:bg-teal-900/20 hover:bg-teal-100 dark:hover:bg-teal-900/30 transition-colors">
                            <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/></svg>
                            <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300 text-center">Order Tracking</span>
                        </a>
                        <a href="{{ route('finance.pnl') }}" class="flex flex-col items-center gap-1.5 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300 text-center">P&L Report</span>
                        </a>
                    </div>
                </div>

                <!-- PO Status Overview -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Purchase Order Status</h3>
                    <div class="space-y-2">
                        @php
                        $poStatuses = [
                            'draft' => ['label' => 'Draft', 'color' => 'bg-gray-400'],
                            'submitted' => ['label' => 'Submitted', 'color' => 'bg-blue-500'],
                            'confirmed' => ['label' => 'Confirmed', 'color' => 'bg-indigo-500'],
                            'in_production' => ['label' => 'In Production', 'color' => 'bg-purple-500'],
                            'shipped' => ['label' => 'Shipped', 'color' => 'bg-cyan-500'],
                            'partial_received' => ['label' => 'Partial Received', 'color' => 'bg-yellow-500'],
                            'received' => ['label' => 'Received', 'color' => 'bg-green-500'],
                            'cancelled' => ['label' => 'Cancelled', 'color' => 'bg-red-500'],
                        ];
                        $poTotal = array_sum($poStatusCounts) ?: 1;
                        @endphp
                        @foreach($poStatuses as $status => $info)
                        @if(($poStatusCounts[$status] ?? 0) > 0)
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $info['color'] }} shrink-0"></div>
                            <div class="flex-1 text-xs text-gray-600 dark:text-gray-400">{{ $info['label'] }}</div>
                            <div class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $poStatusCounts[$status] }}</div>
                            <div class="w-20 h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full {{ $info['color'] }} rounded-full" style="width: {{ ($poStatusCounts[$status] / $poTotal) * 100 }}%"></div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                        @if(empty($poStatusCounts))
                        <div class="text-sm text-gray-400 text-center py-4">No POs yet</div>
                        @endif
                        <div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-xs text-gray-500">Total PO Value</span>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">${{ number_format($poTotalValue, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Received Value</span>
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">${{ number_format($poReceivedValue, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                @if($lowStock->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-yellow-200 dark:border-yellow-800 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Low Stock Alerts</h3>
                    </div>
                    <div class="space-y-2">
                        @foreach($lowStock as $product)
                        <a href="{{ route('products.show', $product->id) }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ $product->product_name }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ $product->asin }}</div>
                            </div>
                            <span class="text-xs font-bold {{ $product->stock_quantity <= 0 ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">{{ $product->stock_quantity }} left</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Expense Breakdown -->
                @if(!empty($expenseBreakdown))
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Expense Breakdown</h3>
                        <a href="{{ route('finance.expenses.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all →</a>
                    </div>
                    <div class="space-y-2">
                        @php $maxExpense = max($expenseBreakdown) ?: 1; @endphp
                        @foreach($expenseBreakdown as $label => $amount)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                                <span class="font-medium text-red-600 dark:text-red-400">${{ number_format($amount, 2) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-red-400 rounded-full" style="width: {{ ($amount / $maxExpense) * 100 }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Amazon Sync Status -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Amazon SP-API</h3>
                        @if($amazonConfigured)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Connected
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 text-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Not connected
                        </span>
                        @endif
                    </div>
                    @if($amazonConfigured && $lastSync)
                    <div class="text-xs text-gray-400">Last sync: {{ $lastSync->diffForHumans() }}</div>
                    @elseif(!$amazonConfigured)
                    <div class="text-xs text-gray-400 mb-2">Connect to auto-sync products, orders, and inventory</div>
                    <a href="{{ route('settings.amazon') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Configure API →</a>
                    @else
                    <div class="text-xs text-gray-400">No sync performed yet</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
