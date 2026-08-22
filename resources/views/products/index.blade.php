<x-app-layout>

    <x-page-header title="Products" :back="route('dashboard')" :count="$products->total() . ' total'">
        <x-button variant="secondary" href="{{ route('vendors.index') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Manage Vendors
        </x-button>
        <div x-data="{ show: false }" @open-add-product.window="show = true" @keydown.escape.window="show = false">
            <x-button variant="primary" @click="$dispatch('open-add-product')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Product
            </x-button>
        </div>
    </x-page-header>

    <div class="space-y-4">
        <!-- Summary Stats -->
        @if($products->isNotEmpty())
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Products</div>
                    <div class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $products->total() }}</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg {{ (($products->avg('margin_percent') ?? 0) >= 15) ? 'bg-green-50 dark:bg-green-900/30' : 'bg-red-50 dark:bg-red-900/30' }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 {{ (($products->avg('margin_percent') ?? 0) >= 15) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Avg Margin</div>
                    <div class="text-xl font-bold {{ (($products->avg('margin_percent') ?? 0) >= 15) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($products->avg('margin_percent') ?? 0, 1) }}%</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Cost</div>
                    <div class="text-xl font-bold text-gray-800 dark:text-gray-200">${{ number_format($products->sum('total_cost') ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg {{ (($products->sum('net_profit') ?? 0) > 0) ? 'bg-green-50 dark:bg-green-900/30' : 'bg-red-50 dark:bg-red-900/30' }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 {{ (($products->sum('net_profit') ?? 0) > 0) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Profit</div>
                    <div class="text-xl font-bold {{ (($products->sum('net_profit') ?? 0) > 0) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($products->sum('net_profit') ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product, ASIN, UPC, vendor..." class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <select name="vendor_id" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Vendors</option>
                    @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->brand_name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    <option value="discontinued" @selected(request('status') === 'discontinued')>Discontinued</option>
                </select>
                <select name="category" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $val => $label)
                    <option value="{{ $val }}" @selected(request('category') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="sort" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="latest" @selected(request('sort') === 'latest')>Latest</option>
                    <option value="margin" @selected(request('sort') === 'margin')>Highest Margin</option>
                    <option value="profit" @selected(request('sort') === 'profit')>Highest Profit</option>
                    <option value="roi" @selected(request('sort') === 'roi')>Highest ROI</option>
                    <option value="price" @selected(request('sort') === 'price')>Highest Price</option>
                    <option value="name" @selected(request('sort') === 'name')>Name A-Z</option>
                </select>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'vendor_id', 'category', 'sort']))
                <a href="{{ route('products.index') }}" class="px-3 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors">Clear</a>
                @endif
            </form>
        </div>

        <!-- Products Table -->
        <div x-data="{ selectedIds: [], productIds: @js($products->pluck('id')->toArray()), get allSelected() { return this.productIds.length > 0 && this.selectedIds.length === this.productIds.length }, toggleAll() { if (this.allSelected) { this.selectedIds = [] } else { this.selectedIds = [...this.productIds] } } }" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Bulk Action Bar -->
            <div x-show="selectedIds.length > 0" x-cloak x-transition class="bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-200 dark:border-indigo-800 px-4 py-2.5 flex items-center gap-3">
                <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300" x-text="selectedIds.length + ' selected'"></span>
                <form method="POST" action="{{ route('products.bulk') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="action" value="set_status">
                    <input type="hidden" name="product_ids" :value="selectedIds.join(',')">
                    <select name="status" onchange="if(this.value) this.form.submit()" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 py-1 px-2">
                        <option value="">Set status...</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="discontinued">Discontinued</option>
                    </select>
                </form>
                <form method="POST" action="{{ route('products.bulk') }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="recalculate">
                    <input type="hidden" name="product_ids" :value="selectedIds.join(',')">
                    <button type="submit" class="px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 rounded-md transition-colors">Recalculate</button>
                </form>
                <form method="POST" action="{{ route('products.bulk') }}" class="inline" onsubmit="return confirm('Delete selected products?')">
                    @csrf
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_ids" :value="selectedIds.join(',')">
                    <button type="submit" class="px-2.5 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-400 dark:bg-red-900/30 dark:hover:bg-red-900/50 rounded-md transition-colors">Delete</button>
                </form>
                <button @click="selectedIds = []" class="ml-auto text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear selection</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3.5 w-10">
                                <input type="checkbox" @click="toggleAll()" :checked="allSelected" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Product</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Buying</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Amazon Fee</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Op. Cost</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Total Cost</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Sell Price</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Profit</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Margin</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ROI</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($products as $product)
                        @php
                        $margin = (float)$product->margin_percent;
                        $profit = (float)$product->net_profit;
                        $roi = (float)$product->roi_percent;
                        $marginBarWidth = min(max($margin, 0), 100);
                        $statusColors = [
                            'active' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400', 'dot' => 'bg-green-500'],
                            'inactive' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => 'bg-gray-400'],
                            'discontinued' => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400', 'dot' => 'bg-red-500'],
                        ];
                        $sc = $statusColors[$product->status] ?? $statusColors['inactive'];
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                            <!-- Checkbox -->
                            <td class="px-4 py-3">
                                <input type="checkbox" value="{{ $product->id }}" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <!-- Product -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="" class="w-11 h-11 rounded-lg object-cover shrink-0 ring-1 ring-gray-200 dark:ring-gray-700">
                                    @else
                                    <div class="w-11 h-11 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center shrink-0 ring-1 ring-gray-200 dark:ring-gray-700">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('products.show', $product->id) }}" class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors block">{{ $product->product_name }}</a>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 mt-0.5">
                                            @if($product->asin)
                                            <span class="font-mono">{{ $product->asin }}</span>
                                            @if($product->amazon_sync_status === 'synced')
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500" title="Synced from Amazon {{ $product->amazon_last_synced_at?->diffForHumans() }}"></span>
                                            @elseif($product->amazon_sync_status === 'error')
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500" title="Amazon sync error"></span>
                                            @endif
                                            @endif
                                            @if($product->upc)
                                            <span class="text-gray-300 dark:text-gray-600">|</span>
                                            <span class="font-mono">{{ $product->upc }}</span>
                                            @endif
                                        </div>
                                        @if($product->product_category)
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $product->product_category }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <!-- Vendor -->
                            <td class="px-4 py-3">
                                @if($product->vendor)
                                <a href="{{ route('vendors.show', $product->vendor->id) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    {{ $product->vendor->brand_name }}
                                </a>
                                @if($product->vendor->company_name)
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $product->vendor->company_name }}</div>
                                @endif
                                @else
                                <span class="text-sm text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <!-- Buying Price -->
                            <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400 tabular-nums">${{ number_format((float)$product->buying_price, 2) }}</td>
                            <!-- Amazon Fee -->
                            <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400 tabular-nums">${{ number_format((float)$product->fba_fee, 2) }}</td>
                            <!-- Operation Cost -->
                            <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400 tabular-nums">${{ number_format((float)$product->operation_cost, 2) }}</td>
                            <!-- Total Cost -->
                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300 tabular-nums">${{ number_format((float)$product->total_cost, 2) }}</td>
                            <!-- Sell Price -->
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-gray-100 tabular-nums">${{ number_format((float)$product->amazon_sell_price, 2) }}</td>
                            <!-- Net Profit -->
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm font-bold tabular-nums {{ $profit > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $profit >= 0 ? '+' : '-' }}${{ number_format(abs($profit), 2) }}
                                </span>
                            </td>
                            <!-- Margin with bar -->
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                        <div class="h-full rounded-full transition-all
                                            {{ $margin >= 30 ? 'bg-green-500' : ($margin >= 15 ? 'bg-blue-500' : ($margin >= 0 ? 'bg-yellow-500' : 'bg-red-500')) }}"
                                            style="width: {{ $marginBarWidth }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium tabular-nums {{ $margin >= 30 ? 'text-green-600 dark:text-green-400' : ($margin >= 15 ? 'text-blue-600 dark:text-blue-400' : ($margin >= 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400')) }}">
                                        {{ number_format($margin, 1) }}%
                                    </span>
                                </div>
                            </td>
                            <!-- ROI -->
                            <td class="px-4 py-3 text-right text-sm tabular-nums {{ $roi > 0 ? 'text-gray-700 dark:text-gray-300' : 'text-red-600 dark:text-red-400' }}">{{ number_format($roi, 1) }}%</td>
                            <!-- Status -->
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $sc['bg'] }} {{ $sc['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                                    {{ ucfirst($product->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="px-4 py-16">
                                <div class="text-center">
                                    <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">No products found</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add products from a vendor's detail page to track pricing, costs, and margins.</p>
                                    <a href="{{ route('vendors.index') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        View Vendors
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($products->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $products->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Add Product Modal (Full inline form) -->
    <div x-data="{ show: false }" @open-add-product.window="show = true" @keydown.escape.window="show = false" x-cloak>
        <div x-show="show" x-transition.opacity class="fixed inset-0 z-50 bg-black/50" @click="show = false"></div>
        <div x-show="show" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="show = false">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Add Product</h3>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('products.store') }}" class="p-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Vendor *</label>
                        <select name="vendor_id" required class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select a vendor...</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->brand_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('vendors._product_form', ['product' => null, 'categories' => $categories, 'hideScript' => true])
                    <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="show = false" class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" name="add_another" value="1" class="px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                            Save & Add Another
                        </button>
                        <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        function productCalculator() {
            return {
                showAdvanced: false,
                asin: '',
                asinWarning: '',
                buyingPrice: 0,
                fbaFee: 0,
                shippingCost: 0,
                labelingCost: 0,
                otherCosts: 0,
                operationCost: 0,
                sellPrice: 0,
                referralPercent: 0,
                existingAsins: @json(\App\Models\Product::whereNotNull('asin')->pluck('product_name', 'asin')->toArray()),
                get totalCost() {
                    return (this.buyingPrice || 0) + (this.fbaFee || 0) + (this.shippingCost || 0) +
                           (this.labelingCost || 0) + (this.otherCosts || 0) + (this.operationCost || 0);
                },
                get referralFee() {
                    return (this.sellPrice || 0) * (this.referralPercent || 0) / 100;
                },
                get netProfit() {
                    return (this.sellPrice || 0) - this.totalCost - this.referralFee;
                },
                get marginPercent() {
                    return (this.sellPrice || 0) > 0 ? (this.netProfit / (this.sellPrice || 0)) * 100 : 0;
                },
                get roiPercent() {
                    return (this.buyingPrice || 0) > 0 ? (this.netProfit / (this.buyingPrice || 0)) * 100 : 0;
                },
                checkAsin() {
                    if (this.asin && this.existingAsins[this.asin]) {
                        this.asinWarning = 'Warning: ASIN ' + this.asin + ' already exists as "' + this.existingAsins[this.asin] + '"';
                    } else {
                        this.asinWarning = '';
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
