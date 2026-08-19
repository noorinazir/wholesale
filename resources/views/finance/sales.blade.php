<x-app-layout>
    @php
    $statusColors = \App\Models\AmazonOrder::statusColors();
    @endphp

    <x-page-header title="Amazon Sales" :back="route('dashboard')" :count="$totalOrders . ' orders'">
        <div class="flex items-center gap-2">
            <a href="{{ route('finance.sales.batch') }}" class="px-3.5 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Batch Entry
            </a>
            <a href="{{ route('finance.sales.import.csv') }}" class="px-3.5 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Import CSV
            </a>
            <a href="{{ route('finance.sales.create') }}" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Record Sale
            </a>
        </div>
    </x-page-header>

    <div class="space-y-4">
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Revenue</div>
                <div class="text-xl font-bold text-gray-800 dark:text-gray-200">${{ number_format($totalRevenue, 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net Profit</div>
                <div class="text-xl font-bold {{ $totalProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($totalProfit, 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Avg Margin</div>
                @php $avgMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0; @endphp
                <div class="text-xl font-bold {{ $avgMargin >= 15 ? 'text-green-600 dark:text-green-400' : ($avgMargin >= 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">{{ number_format($avgMargin, 1) }}%</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Orders</div>
                <div class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $totalOrders }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Units Sold</div>
                <div class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $totalUnits }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order ID, ASIN, product..." class="flex-1 min-w-[200px] rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                <select name="status" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Statuses</option>
                    @foreach(['pending','processing','shipped','delivered','returned','refunded','cancelled'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <select name="vendor_id" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Vendors</option>
                    @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->brand_name }}</option>
                    @endforeach
                </select>
                <select name="product_id" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->product_name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                <select name="fulfillment" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Fulfillment</option>
                    <option value="FBA" @selected(request('fulfillment') === 'FBA')>FBA</option>
                    <option value="FBM" @selected(request('fulfillment') === 'FBM')>FBM</option>
                </select>
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Filter</button>
                @if(request()->hasAny(['search', 'status', 'vendor_id', 'product_id', 'date_from', 'date_to', 'sort']))
                <a href="{{ route('finance.sales.index') }}" class="px-3 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-600">Clear</a>
                @endif
            </form>
        </div>

        <!-- Sales Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Order ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Qty</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Channel</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Revenue</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Cost</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Profit</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Margin</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($orders as $order)
                        @php $sc = $statusColors[$order->order_status] ?? $statusColors['pending']; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-500">
                                <a href="{{ route('finance.sales.show', $order->id) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $order->amazon_order_id ?? '#' . $order->id }}</a>
                                @if($order->amazon_sync_status === 'synced')
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 ml-1" title="Synced from Amazon"></span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $order->product_name }}
                                @if($order->asin)<div class="text-xs text-gray-400 font-mono">{{ $order->asin }}</div>@endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $order->vendor?->brand_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $order->order_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300">{{ $order->quantity }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($order->fulfillment_channel === 'FBA')
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400">FBA</span>
                                @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">FBM</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200 text-right">${{ number_format($order->total_revenue, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 text-right">${{ number_format($order->total_cost, 2) }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-right {{ $order->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($order->net_profit, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                @php $margin = (float)$order->margin_percent; @endphp
                                <span class="font-medium {{ $margin >= 15 ? 'text-green-600 dark:text-green-400' : ($margin >= 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">{{ number_format($margin, 1) }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('finance.sales.status', $order->id) }}" class="inline">
                                    @csrf
                                    <select name="order_status" onchange="this.form.submit()" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 py-1 px-2">
                                        @foreach(['pending','processing','shipped','delivered','returned','refunded','cancelled'] as $st)
                                        <option value="{{ $st }}" @selected($order->order_status === $st)>{{ ucfirst($st) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="px-4 py-16 text-center">
                                <div class="text-sm text-gray-500 dark:text-gray-400">No sales recorded yet.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
