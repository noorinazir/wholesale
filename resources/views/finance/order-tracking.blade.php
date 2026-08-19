<x-app-layout>
    @php
    $statusColors = [
        'pending' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => 'bg-gray-400'],
        'processing' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400', 'dot' => 'bg-blue-500'],
        'shipped' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'text' => 'text-cyan-700 dark:text-cyan-400', 'dot' => 'bg-cyan-500'],
    ];
    $statusSteps = ['pending', 'processing', 'shipped', 'delivered'];
    @endphp

    <x-page-header title="Order Tracking" :back="route('dashboard')" :count="$orders->total() . ' active'">
    </x-page-header>

    <div class="space-y-4">
        <!-- Status Summary -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div><div class="text-xs text-gray-500">Pending</div><div class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $statusCounts['pending'] ?? 0 }}</div></div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div><div class="text-xs text-gray-500">Processing</div><div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $statusCounts['processing'] ?? 0 }}</div></div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div><div class="text-xs text-gray-500">Shipped</div><div class="text-xl font-bold text-cyan-600 dark:text-cyan-400">{{ $statusCounts['shipped'] ?? 0 }}</div></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order ID or product..." class="flex-1 min-w-[200px] rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                <select name="status" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Active</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="processing" @selected(request('status') === 'processing')>Processing</option>
                    <option value="shipped" @selected(request('status') === 'shipped')>Shipped</option>
                </select>
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Filter</button>
            </form>
        </div>

        <!-- Orders with Progress -->
        <div class="space-y-3">
            @forelse($orders as $order)
            @php
            $sc = $statusColors[$order->order_status] ?? $statusColors['pending'];
            $currentStepIndex = array_search($order->order_status, $statusSteps);
            if ($currentStepIndex === false) $currentStepIndex = 0;
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-mono text-gray-500">{{ $order->amazon_order_id ?? '#' . $order->id }}</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $sc['bg'] }} {{ $sc['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                                {{ ucfirst($order->order_status) }}
                            </span>
                        </div>
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-1">{{ $order->product_name }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            {{ $order->vendor?->brand_name ?? '—' }} · {{ $order->quantity }} units · ${{ number_format($order->total_revenue, 2) }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('finance.sales.status', $order->id) }}" class="shrink-0">
                        @csrf
                        <select name="order_status" onchange="this.form.submit()" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 py-1.5 px-2">
                            <option value="pending" @selected($order->order_status === 'pending')>Pending</option>
                            <option value="processing" @selected($order->order_status === 'processing')>Processing</option>
                            <option value="shipped" @selected($order->order_status === 'shipped')>Shipped</option>
                            <option value="delivered" @selected($order->order_status === 'delivered')>Delivered</option>
                            <option value="returned" @selected($order->order_status === 'returned')>Returned</option>
                            <option value="cancelled" @selected($order->order_status === 'cancelled')>Cancelled</option>
                        </select>
                    </form>
                </div>

                <!-- Progress Bar -->
                <div class="flex items-center gap-1">
                    @foreach($statusSteps as $i => $step)
                    <div class="flex-1 flex items-center {{ $i < count($statusSteps) - 1 ? 'gap-1' : '' }}">
                        <div class="flex flex-col items-center gap-1 shrink-0">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium
                                {{ $i <= $currentStepIndex ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}">
                                @if($i < $currentStepIndex)
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @else
                                {{ $i + 1 }}
                                @endif
                            </div>
                            <span class="text-[10px] {{ $i <= $currentStepIndex ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-400' }}">{{ ucfirst($step) }}</span>
                        </div>
                        @if($i < count($statusSteps) - 1)
                        <div class="flex-1 h-0.5 mx-1 {{ $i < $currentStepIndex ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <!-- Dates -->
                <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
                    <span>Ordered: {{ $order->order_date->format('M d, Y') }}</span>
                    @if($order->ship_date)<span class="text-cyan-600">Shipped: {{ $order->ship_date->format('M d, Y') }}</span>@endif
                    @if($order->delivery_date)<span class="text-green-600">Delivered: {{ $order->delivery_date->format('M d, Y') }}</span>@endif
                </div>
            </div>
            @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-16 text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">No active orders to track. All orders are delivered or cancelled.</div>
            </div>
            @endforelse
        </div>

        @if($orders->hasPages())
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-3">
            {{ $orders->links() }}
        </div>
        @endif

        <!-- Purchase Order Tracking -->
        <div class="pt-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Purchase Orders</h2>
                <span class="text-xs text-gray-500">{{ $purchaseOrders->count() }} active</span>
            </div>

            @php
            $poStatusColors = [
                'submitted' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400'],
                'confirmed' => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-400'],
                'in_production' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-400'],
                'shipped' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'text' => 'text-cyan-700 dark:text-cyan-400'],
                'partial_received' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400'],
            ];
            $poSteps = ['submitted', 'confirmed', 'in_production', 'shipped', 'received'];
            @endphp

            <div class="space-y-3">
                @forelse($purchaseOrders as $po)
                @php
                $psc = $poStatusColors[$po->status] ?? $poStatusColors['submitted'];
                $poStepIndex = array_search($po->status, $poSteps);
                if ($poStepIndex === false) $poStepIndex = 0;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('finance.po.show', $po->id) }}" class="text-sm font-mono text-indigo-600 dark:text-indigo-400 hover:underline">{{ $po->po_number }}</a>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $psc['bg'] }} {{ $psc['text'] }}">
                                    {{ ucfirst(str_replace('_', ' ', $po->status)) }}
                                </span>
                                @if($po->payment_status === 'paid')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400">Paid</span>
                                @elseif($po->payment_status === 'partial_paid')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">Partial Paid</span>
                                @endif
                            </div>
                            <div class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-1">{{ $po->vendor?->brand_name ?? '—' }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $po->items->count() }} items · {{ $po->items->sum('quantity_ordered') }} units · ${{ number_format($po->total_amount, 2) }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('finance.po.status', $po->id) }}" class="shrink-0">
                            @csrf
                            <select name="status" onchange="this.form.submit()" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 py-1.5 px-2">
                                @foreach(['submitted','confirmed','in_production','shipped','partial_received','received','cancelled'] as $st)
                                <option value="{{ $st }}" @selected($po->status === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <!-- Progress Bar -->
                    <div class="flex items-center gap-1">
                        @foreach($poSteps as $i => $step)
                        <div class="flex-1 flex items-center {{ $i < count($poSteps) - 1 ? 'gap-1' : '' }}">
                            <div class="flex flex-col items-center gap-1 shrink-0">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium
                                    {{ $i <= $poStepIndex ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}">
                                    @if($i < $poStepIndex)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                    {{ $i + 1 }}
                                    @endif
                                </div>
                                <span class="text-[10px] {{ $i <= $poStepIndex ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-400' }}">{{ ucfirst(str_replace('_', ' ', $step)) }}</span>
                            </div>
                            @if($i < count($poSteps) - 1)
                            <div class="flex-1 h-0.5 mx-1 {{ $i < $poStepIndex ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Receipt Progress -->
                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
                        <span>Ordered: {{ $po->order_date->format('M d, Y') }}</span>
                        @if($po->expected_delivery_date)<span class="text-cyan-600">Expected: {{ $po->expected_delivery_date->format('M d, Y') }}</span>@endif
                        <span class="ml-auto">Received: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $po->received_percentage }}%</span></span>
                    </div>
                </div>
                @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">No active purchase orders. All POs are received, drafted, or cancelled.</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
