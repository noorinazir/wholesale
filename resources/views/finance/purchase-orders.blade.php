<x-app-layout>
    @php
    $statusColors = [
        'draft' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => 'bg-gray-400'],
        'submitted' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400', 'dot' => 'bg-blue-500'],
        'confirmed' => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-400', 'dot' => 'bg-indigo-500'],
        'in_production' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-400', 'dot' => 'bg-purple-500'],
        'shipped' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'text' => 'text-cyan-700 dark:text-cyan-400', 'dot' => 'bg-cyan-500'],
        'received' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400', 'dot' => 'bg-green-500'],
        'partial_received' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400', 'dot' => 'bg-yellow-500'],
        'cancelled' => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400', 'dot' => 'bg-red-500'],
    ];
    $paymentColors = [
        'unpaid' => 'text-red-600 dark:text-red-400',
        'partial_paid' => 'text-yellow-600 dark:text-yellow-400',
        'paid' => 'text-green-600 dark:text-green-400',
        'refunded' => 'text-gray-500',
    ];
    @endphp

    <x-page-header title="Purchase Orders" :back="route('dashboard')" :count="$purchaseOrders->total() . ' total'">
        <x-button variant="primary" href="{{ route('finance.po.create') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New PO
        </x-button>
    </x-page-header>

    <div class="space-y-4">
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total POs</div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $purchaseOrders->total() }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pending Receipt</div>
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ ($statusCounts['submitted'] ?? 0) + ($statusCounts['confirmed'] ?? 0) + ($statusCounts['in_production'] ?? 0) + ($statusCounts['shipped'] ?? 0) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Received</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $statusCounts['received'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Unpaid</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $unpaidCount ?? 0 }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PO # or vendor..." class="flex-1 min-w-[180px] rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <select name="vendor_id" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Vendors</option>
                    @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->brand_name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Statuses</option>
                    @foreach(['draft','submitted','confirmed','in_production','shipped','partial_received','received','cancelled'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>
                <select name="sort" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="latest" @selected(request('sort') === 'latest')>Latest</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    <option value="amount" @selected(request('sort') === 'amount')>Highest Amount</option>
                    <option value="vendor" @selected(request('sort') === 'vendor')>Vendor A-Z</option>
                </select>
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Filter</button>
                @if(request()->hasAny(['search', 'status', 'vendor_id', 'sort']))
                <a href="{{ route('finance.po.index') }}" class="px-3 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-600">Clear</a>
                @endif
            </form>
        </div>

        <!-- PO Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">PO Number</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Order Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Expected</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Items</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Payment</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($purchaseOrders as $po)
                        @php $sc = $statusColors[$po->status] ?? $statusColors['draft']; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('finance.po.show', $po->id) }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ $po->po_number }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $po->vendor->brand_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $po->order_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $po->expected_delivery_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200 text-right">${{ number_format($po->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-center text-gray-600 dark:text-gray-400">{{ $po->items->count() }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $sc['bg'] }} {{ $sc['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                                    {{ ucfirst(str_replace('_', ' ', $po->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-medium {{ $paymentColors[$po->payment_status] ?? '' }}">{{ ucfirst(str_replace('_', ' ', $po->payment_status)) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(in_array($po->status, ['draft', 'submitted']) && \Illuminate\Support\Facades\Gate::check('manage-finance'))
                                <a href="{{ route('finance.po.edit', $po->id) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Edit</a>
                                @else
                                <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-16 text-center">
                                <div class="text-sm text-gray-500 dark:text-gray-400">No purchase orders yet. Create your first PO to start tracking orders.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($purchaseOrders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $purchaseOrders->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
