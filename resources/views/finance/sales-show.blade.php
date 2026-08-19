<x-app-layout>
    @php
    $statusColors = \App\Models\AmazonOrder::statusColors();
    $sc = $statusColors[$order->order_status] ?? $statusColors['pending'];
    @endphp

    <x-page-header title="Sale Detail" :back="route('finance.sales.index')">
        <div class="flex items-center gap-2">
            <a href="{{ route('finance.sales.edit', $order->id) }}" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
        </div>
    </x-page-header>

    <div class="max-w-5xl mx-auto space-y-4">
        <!-- Order Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $order->product_name }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $sc['bg'] }} {{ $sc['text'] }}">{{ ucfirst($order->order_status) }}</span>
                        @if($order->fulfillment_channel === 'FBA')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400">FBA</span>
                        @else
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">FBM</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        @if($order->amazon_order_id)<span class="font-mono">{{ $order->amazon_order_id }}</span> · @endif
                        {{ $order->order_date->format('M d, Y') }}
                        @if($order->asin) · ASIN: {{ $order->asin }} @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">${{ number_format($order->total_revenue, 2) }}</div>
                    <div class="text-sm {{ $order->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $order->net_profit >= 0 ? '+' : '' }}${{ number_format($order->net_profit, 2) }} ({{ number_format($order->margin_percent, 1) }}%)
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Financial Breakdown -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Financial Breakdown</h3>
                <div class="space-y-2">
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Revenue ({{ $order->quantity }} × ${{ number_format($order->sale_price, 2) }})</span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">${{ number_format($order->total_revenue, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Product Cost</span>
                        <span class="text-sm text-red-600 dark:text-red-400">-${{ number_format($order->product_cost, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Amazon Fee (incl. referral)</span>
                        <span class="text-sm text-red-600 dark:text-red-400">-${{ number_format($order->fba_fee, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Shipping</span>
                        <span class="text-sm text-red-600 dark:text-red-400">-${{ number_format($order->shipping_cost, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Labeling</span>
                        <span class="text-sm text-red-600 dark:text-red-400">-${{ number_format($order->labeling_cost, 2) }}</span>
                    </div>
                    @if($order->operation_cost > 0)
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Operation</span>
                        <span class="text-sm text-red-600 dark:text-red-400">-${{ number_format($order->operation_cost, 2) }}</span>
                    </div>
                    @endif
                    @if($order->advertising_cost > 0)
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Advertising</span>
                        <span class="text-sm text-red-600 dark:text-red-400">-${{ number_format($order->advertising_cost, 2) }}</span>
                    </div>
                    @endif
                    @if($order->return_cost > 0)
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Return Cost</span>
                        <span class="text-sm text-red-600 dark:text-red-400">-${{ number_format($order->return_cost, 2) }}</span>
                    </div>
                    @endif
                    @if($order->other_costs > 0)
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Other Costs</span>
                        <span class="text-sm text-red-600 dark:text-red-400">-${{ number_format($order->other_costs, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3 bg-gray-50 dark:bg-gray-700/30 -mx-5 px-5">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Total Cost</span>
                        <span class="text-sm font-bold text-red-600 dark:text-red-400">-${{ number_format($order->total_cost, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Net Profit</span>
                        <span class="text-sm font-bold {{ $order->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $order->net_profit >= 0 ? '+' : '' }}${{ number_format($order->net_profit, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Order Info Sidebar -->
            <div class="space-y-4">
                <!-- Order Details -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Order Info</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Order Date</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->order_date->format('M d, Y') }}</dd></div>
                        @if($order->ship_date)<div class="flex justify-between"><dt class="text-gray-500">Ship Date</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->ship_date->format('M d, Y') }}</dd></div>@endif
                        @if($order->delivery_date)<div class="flex justify-between"><dt class="text-gray-500">Delivery Date</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->delivery_date->format('M d, Y') }}</dd></div>@endif
                        <div class="flex justify-between"><dt class="text-gray-500">Quantity</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->quantity }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Marketplace</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->amazon_marketplace ?? 'US' }}</dd></div>
                    </dl>
                </div>

                <!-- Linked Entities -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Linked Records</h3>
                    <dl class="space-y-2 text-sm">
                        @if($order->product)
                        <div class="flex justify-between"><dt class="text-gray-500">Product</dt><dd class="text-indigo-600 dark:text-indigo-400">{{ $order->product->product_name }}</dd></div>
                        @endif
                        @if($order->vendor)
                        <div class="flex justify-between"><dt class="text-gray-500">Vendor</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->vendor->brand_name }}</dd></div>
                        @endif
                        @if($order->purchaseOrder)
                        <div class="flex justify-between"><dt class="text-gray-500">Purchase Order</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->purchaseOrder->po_number }}</dd></div>
                        @endif
                        @if($order->product && $order->product->stock_quantity !== null)
                        <div class="flex justify-between"><dt class="text-gray-500">Current Stock</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->product->stock_quantity }} units</dd></div>
                        @endif
                    </dl>
                </div>

                <!-- Tax Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Tax</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">State</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->tax_state ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Rate</dt><dd class="text-gray-700 dark:text-gray-300">{{ number_format($order->tax_rate, 2) }}%</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Collected</dt><dd class="text-gray-700 dark:text-gray-300">${{ number_format($order->tax_collected, 2) }}</dd></div>
                    </dl>
                </div>

                <!-- Customer -->
                @if($order->customer_name || $order->customer_state)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Customer</h3>
                    <dl class="space-y-2 text-sm">
                        @if($order->customer_name)<div class="flex justify-between"><dt class="text-gray-500">Name</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->customer_name }}</dd></div>@endif
                        @if($order->customer_city)<div class="flex justify-between"><dt class="text-gray-500">City</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->customer_city }}</dd></div>@endif
                        @if($order->customer_state)<div class="flex justify-between"><dt class="text-gray-500">State</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->customer_state }}</dd></div>@endif
                        @if($order->customer_zip)<div class="flex justify-between"><dt class="text-gray-500">ZIP</dt><dd class="text-gray-700 dark:text-gray-300">{{ $order->customer_zip }}</dd></div>@endif
                    </dl>
                </div>
                @endif
            </div>
        </div>

        <!-- Related Expenses -->
        @if($expenses->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Related Expenses (±7 days)</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Date</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Category</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Description</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300">Amount</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($expenses as $expense)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 text-sm text-gray-500">{{ $expense->expense_date->format('M d, Y') }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">{{ \App\Models\Expense::categoryLabels()[$expense->category] ?? ucfirst($expense->category) }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $expense->description }}</td>
                            <td class="px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 text-right">${{ number_format($expense->amount, 2) }}</td>
                            <td class="px-3 py-2 text-center"><span class="text-xs px-1.5 py-0.5 rounded {{ $expense->status === 'paid' ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' }}">{{ ucfirst($expense->status) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($order->notes)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Notes</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $order->notes }}</p>
        </div>
        @endif
    </div>
</x-app-layout>
