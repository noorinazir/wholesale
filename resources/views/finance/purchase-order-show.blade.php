<x-app-layout>
    @php
    $statusColors = [
        'draft' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400'],
        'submitted' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400'],
        'confirmed' => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-400'],
        'in_production' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-400'],
        'shipped' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'text' => 'text-cyan-700 dark:text-cyan-400'],
        'received' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400'],
        'partial_received' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400'],
        'cancelled' => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400'],
    ];
    $sc = $statusColors[$po->status] ?? $statusColors['draft'];
    @endphp

    <x-page-header title="{{ $po->po_number }}" :back="route('finance.po.index')">
        @if(in_array($po->status, ['draft', 'submitted']) && \Illuminate\Support\Facades\Gate::check('manage-finance'))
        <x-button variant="primary" href="{{ route('finance.po.edit', $po->id) }}">
            Edit PO
        </x-button>
        @endif
        <x-button variant="secondary" href="{{ route('vendors.show', $po->vendor->id) }}">
            View Vendor
        </x-button>
    </x-page-header>

    <div class="space-y-6">
        <!-- PO Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- PO Info -->
            <x-card>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Order Info</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Vendor</span><a href="{{ route('vendors.show', $po->vendor->id) }}" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">{{ $po->vendor->brand_name }}</a></div>
                    <div class="flex justify-between"><span class="text-gray-500">Order Date</span><span class="text-gray-800 dark:text-gray-200">{{ $po->order_date->format('M d, Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Expected</span><span class="text-gray-800 dark:text-gray-200">{{ $po->expected_delivery_date?->format('M d, Y') ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Delivered</span><span class="text-gray-800 dark:text-gray-200">{{ $po->actual_delivery_date?->format('M d, Y') ?? '—' }}</span></div>
                    <div class="flex justify-between items-center"><span class="text-gray-500">Status</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $sc['bg'] }} {{ $sc['text'] }}">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Payment</span><span class="text-gray-800 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $po->payment_status)) }}</span></div>
                    @if($po->payment_terms)
                    <div class="flex justify-between"><span class="text-gray-500">Terms</span><span class="text-gray-800 dark:text-gray-200">{{ $po->payment_terms }}</span></div>
                    @endif
                </div>
                <!-- Status Update -->
                <form method="POST" action="{{ route('finance.po.status', $po->id) }}" class="mt-4 flex gap-2">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="flex-1 rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-1.5">
                        @foreach(['draft','submitted','confirmed','in_production','shipped','partial_received','received','cancelled'] as $st)
                        <option value="{{ $st }}" @selected($po->status === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>
                </form>
            </x-card>

            <!-- Financial Summary -->
            <x-card>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Financial Summary</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="text-gray-800 dark:text-gray-200">${{ number_format($po->subtotal, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Shipping</span><span class="text-gray-800 dark:text-gray-200">${{ number_format($po->shipping_cost, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Tax</span><span class="text-gray-800 dark:text-gray-200">${{ number_format($po->tax_amount, 2) }}</span></div>
                    @if($po->discount_amount > 0)
                    <div class="flex justify-between"><span class="text-gray-500">Discount</span><span class="text-green-600">-${{ number_format($po->discount_amount, 2) }}</span></div>
                    @endif
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-2 flex justify-between font-semibold"><span class="text-gray-700 dark:text-gray-300">Total</span><span class="text-gray-900 dark:text-gray-100">${{ number_format($po->total_amount, 2) }}</span></div>
                    @if(isset($po->total_expenses) && $po->total_expenses > 0)
                    <div class="flex justify-between"><span class="text-gray-500">Allocated Expenses</span><span class="text-orange-600 dark:text-orange-400">${{ number_format($po->total_expenses, 2) }}</span></div>
                    @endif
                    @if(isset($po->total_landed_cost) && $po->total_landed_cost > 0)
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-2 flex justify-between font-semibold"><span class="text-gray-700 dark:text-gray-300">Landed Cost</span><span class="text-indigo-600 dark:text-indigo-400">${{ number_format($po->total_landed_cost, 2) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-gray-500">Paid</span><span class="text-green-600">${{ number_format($po->amount_paid, 2) }}</span></div>
                    <div class="flex justify-between font-semibold"><span class="text-gray-700 dark:text-gray-300">Balance Due</span><span class="{{ $po->balance_due > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600' }}">${{ $po->balanceDue }}</span></div>
                </div>
                <!-- Payment Update -->
                <form method="POST" action="{{ route('finance.po.payment', $po->id) }}" class="mt-4 space-y-2">
                    @csrf
                    <select name="payment_status" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-1.5">
                        @foreach(['unpaid','partial_paid','paid','refunded'] as $ps)
                        <option value="{{ $ps }}" @selected($po->payment_status === $ps)>{{ ucfirst(str_replace('_', ' ', $ps)) }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="amount_paid" step="0.01" min="0" value="{{ $po->amount_paid }}" class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-1.5">
                    <button type="submit" class="w-full px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Update Payment</button>
                </form>
            </x-card>

            <!-- Receipt Progress -->
            <x-card>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Receipt Progress</h3>
                <div class="text-center py-4">
                    <div class="text-4xl font-bold {{ $po->received_percentage === 100 ? 'text-green-600' : ($po->received_percentage > 0 ? 'text-yellow-600' : 'text-gray-400') }}">{{ $po->received_percentage }}%</div>
                    <div class="text-xs text-gray-500 mt-1">Received</div>
                </div>
                <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div class="h-full rounded-full transition-all {{ $po->received_percentage === 100 ? 'bg-green-500' : 'bg-yellow-500' }}" style="width: {{ $po->received_percentage }}%"></div>
                </div>
                @if($po->notes)
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div class="text-xs font-medium text-gray-500 mb-1">Notes</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $po->notes }}</div>
                </div>
                @endif
            </x-card>
        </div>

        <!-- Line Items -->
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Line Items</h3>
                @if($po->status !== 'cancelled' && ! $po->is_fully_received)
                <form method="POST" action="{{ route('finance.po.receive-all', $po->id) }}" onsubmit="return confirm('Mark all items as fully received? This will update product costs and stock.')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Receive All
                    </button>
                </form>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Product</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">ASIN</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Ordered</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Received</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Unit Cost</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Landed/Unit</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Line Total</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Update Received</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($po->items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ $item->product_name }}
                                @if($item->product)
                                <a href="{{ route('products.show', $item->product->id) }}" class="text-xs text-indigo-600 hover:underline ml-1">→</a>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-500 font-mono">{{ $item->asin ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-center text-gray-700 dark:text-gray-300">{{ $item->quantity_ordered }}</td>
                            <td class="px-3 py-2 text-sm text-center">
                                @if($item->quantity_received >= $item->quantity_ordered)
                                <span class="text-green-600 font-medium">{{ $item->quantity_received }}</span>
                                @elseif($item->quantity_received > 0)
                                <span class="text-yellow-600 font-medium">{{ $item->quantity_received }}</span>
                                @else
                                <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">${{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right font-medium text-indigo-600 dark:text-indigo-400">${{ number_format($item->landed_cost_per_unit, 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right font-medium text-gray-800 dark:text-gray-200">${{ number_format($item->line_total, 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($po->status !== 'cancelled' && $item->quantity_received < $item->quantity_ordered)
                                <form method="POST" action="{{ route('finance.po.receive', $po->id) }}" class="inline-flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                    <input type="number" name="quantity_received" min="0" max="{{ $item->quantity_ordered }}" value="{{ $item->quantity_received }}" class="w-16 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-xs py-1 text-center">
                                    <button type="submit" class="text-xs text-indigo-600 hover:underline">Save</button>
                                </form>
                                @else
                                <span class="text-xs {{ $item->quantity_received >= $item->quantity_ordered ? 'text-green-600' : 'text-gray-400' }}">{{ $item->quantity_received >= $item->quantity_ordered ? '✓' : '—' }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- PO Sales Performance -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            <x-card>
                <div class="text-xs font-medium text-gray-500 mb-1">PO Cost (Landed)</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format(isset($po->total_landed_cost) && $po->total_landed_cost > 0 ? $po->total_landed_cost : $poCost, 2) }}</div>
            </x-card>
            <x-card>
                <div class="text-xs font-medium text-gray-500 mb-1">Revenue from Sales</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">${{ number_format($poRevenue, 2) }}</div>
            </x-card>
            <x-card>
                <div class="text-xs font-medium text-gray-500 mb-1">Net Profit (Rev - PO Cost)</div>
                <div class="text-2xl font-bold {{ $poNetProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($poNetProfit, 2) }}</div>
            </x-card>
            <x-card>
                <div class="text-xs font-medium text-gray-500 mb-1">ROI</div>
                <div class="text-2xl font-bold {{ $poRoi >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($poRoi, 1) }}%</div>
            </x-card>
        </div>

        <!-- Per-Item Sales Summary -->
        @if($po->items->isNotEmpty())
        <x-card>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Per-Item Sales Performance</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Product</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Received</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Units Sold</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Remaining</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Orders</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Revenue</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($po->items as $item)
                        @php $stats = $itemSales[$item->id] ?? []; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ $item->product_name }}
                                @if($item->asin)<span class="text-xs text-gray-400 font-mono ml-1">{{ $item->asin }}</span>@endif
                            </td>
                            <td class="px-3 py-2 text-sm text-center text-gray-700 dark:text-gray-300">{{ $item->quantity_received }}</td>
                            <td class="px-3 py-2 text-sm text-center">
                                @if($stats['units_sold'] > 0)
                                <span class="font-medium text-green-600 dark:text-green-400">{{ $stats['units_sold'] }}</span>
                                @else
                                <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-center">
                                @if($stats['units_remaining'] > 0)
                                <span class="font-medium text-yellow-600 dark:text-yellow-400">{{ $stats['units_remaining'] }}</span>
                                @else
                                <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-center text-gray-700 dark:text-gray-300">{{ $stats['orders_count'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-sm text-right font-medium text-blue-600 dark:text-blue-400">${{ number_format($stats['revenue'] ?? 0, 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right font-medium {{ ($stats['profit'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($stats['profit'] ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
        @endif

        <!-- Linked Sales -->
        @if($linkedSales->isNotEmpty())
        <x-card>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Linked Sales ({{ $linkedSales->count() }} orders)</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Date</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Order ID</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Product</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Qty</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Revenue</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Profit</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($linkedSales as $sale)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 text-sm text-gray-500">{{ $sale->order_date?->format('M d, Y') }}</td>
                            <td class="px-3 py-2 text-sm">
                                <a href="{{ route('finance.sales.show', $sale->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-mono text-xs">{{ $sale->amazon_order_id ?? '#' . $sale->id }}</a>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ $sale->product_name }}
                                @if($sale->asin)<span class="text-xs text-gray-400 font-mono ml-1">{{ $sale->asin }}</span>@endif
                            </td>
                            <td class="px-3 py-2 text-sm text-center text-gray-700 dark:text-gray-300">{{ $sale->quantity }}</td>
                            <td class="px-3 py-2 text-sm text-right font-medium text-blue-600 dark:text-blue-400">${{ number_format((float)$sale->total_revenue, 2) }}</td>
                            <td class="px-3 py-2 text-sm text-right font-medium {{ (float)$sale->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format((float)$sale->net_profit, 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                @php $colors = \App\Models\AmazonOrder::statusColors(); $sc = $colors[$sale->order_status] ?? $colors['pending']; @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs {{ $sc['bg'] }} {{ $sc['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                                    {{ ucfirst($sale->order_status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
        @else
        <x-card>
            <div class="text-center py-8">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <p class="text-sm text-gray-400">No sales linked to this PO yet. Sales will auto-link when products from this PO are sold.</p>
            </div>
        </x-card>
        @endif

        <!-- Linked Expenses -->
        @if($po->expenses->isNotEmpty())
        <x-card>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Linked Expenses ({{ $po->expenses->count() }})</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Expense #</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Description</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Category</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Service Vendor</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Date</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Amount</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($po->expenses as $expense)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 text-sm font-mono text-gray-500">{{ $expense->expense_number }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $expense->description }}</td>
                            <td class="px-3 py-2">
                                @php $catLabels = \App\Models\Expense::categoryLabels(); @endphp
                                <span class="px-2 py-0.5 text-xs rounded-md bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400">{{ $catLabels[$expense->category] ?? ucfirst($expense->category) }}</span>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                @if($expense->relationLoaded('serviceVendor') && $expense->serviceVendor)
                                    <span class="text-xs">{{ $expense->serviceVendor->brand_name }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-500">{{ $expense->expense_date->format('M d, Y') }}</td>
                            <td class="px-3 py-2 text-sm font-medium text-right text-red-600 dark:text-red-400">${{ number_format($expense->amount, 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-0.5 text-xs rounded-md {{ $expense->status === 'approved' ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400' : ($expense->status === 'rejected' ? 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">{{ ucfirst($expense->status) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
        @endif
    </div>
</x-app-layout>
