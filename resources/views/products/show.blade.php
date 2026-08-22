<x-app-layout>
    @php
    $margin = (float)$product->margin_percent;
    $profit = (float)$product->net_profit;
    $roi = (float)$product->roi_percent;
    $totalCost = (float)$product->total_cost;
    $sellPrice = (float)$product->amazon_sell_price;
    $amazonFee = (float)$product->amazon_fee;
    $statusColors = [
        'active' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400', 'dot' => 'bg-green-500'],
        'inactive' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => 'bg-gray-400'],
        'discontinued' => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400', 'dot' => 'bg-red-500'],
    ];
    $sc = $statusColors[$product->status] ?? $statusColors['inactive'];
    @endphp

    <x-page-header title="{{ $product->product_name }}" :back="route('products.index')">
        <x-button variant="secondary" href="{{ route('vendors.show', $product->vendor->id) }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            View Vendor
        </x-button>
        @if($product->asin && !empty(\App\Models\SystemSetting::get('amazon_lwa_client_id')))
        <form method="POST" action="{{ route('settings.amazon.sync') }}" class="inline">
            @csrf
            <input type="hidden" name="type" value="products">
            <x-button variant="secondary" type="submit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Sync from Amazon
            </x-button>
        </form>
        @endif
        <div x-data="{ show: false }" @open-edit-product.window="show = true" @keydown.escape.window="show = false">
            <x-button variant="primary" @click="$dispatch('open-edit-product')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Product
            </x-button>
        </div>
    </x-page-header>

    <div class="space-y-6">
        <!-- Product Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-start gap-5">
                @if($product->image_url)
                <img src="{{ $product->image_url }}" alt="" class="w-24 h-24 rounded-xl object-cover ring-1 ring-gray-200 dark:ring-gray-700 shrink-0">
                @else
                <div class="w-24 h-24 rounded-xl bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/40 dark:to-indigo-800/40 flex items-center justify-center shrink-0 ring-1 ring-indigo-100 dark:ring-indigo-900/30">
                    <svg class="w-10 h-10 text-indigo-400 dark:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $product->product_name }}</h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $sc['bg'] }} {{ $sc['text'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                            {{ ucfirst($product->status) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                        @if($product->asin)
                        <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg> ASIN: {{ $product->asin }}</span>
                        @if($product->amazon_sync_status === 'synced')
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400" title="Last synced: {{ $product->amazon_last_synced_at?->diffForHumans() }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Synced {{ $product->amazon_last_synced_at?->diffForHumans() }}
                        </span>
                        @elseif($product->amazon_sync_status === 'error')
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400">Sync Error</span>
                        @endif
                        @endif
                        @if($product->upc)
                        <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2V4m0 0V3m0 0a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V5a2 2 0 00-2-2h-6z"/></svg> UPC: {{ $product->upc }}</span>
                        @endif
                        @if($product->product_category)
                        <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $categories[$product->product_category] ?? $product->product_category }}</span>
                        @endif
                    </div>
                    <div class="mt-2 text-sm">
                        <span class="text-gray-500">Vendor: </span>
                        <a href="{{ route('vendors.show', $product->vendor->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">{{ $product->vendor->brand_name }}</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profitability Summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net Profit</div>
                <div class="text-2xl font-bold {{ $profit > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format($profit, 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Margin</div>
                <div class="text-2xl font-bold {{ $margin >= 15 ? 'text-green-600 dark:text-green-400' : ($margin >= 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">{{ number_format($margin, 1) }}%</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">ROI</div>
                <div class="text-2xl font-bold {{ $roi > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($roi, 1) }}%</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Cost</div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">${{ number_format($totalCost, 2) }}</div>
            </div>
        </div>

        <!-- Cost Breakdown & Market Data -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Cost Breakdown -->
            <x-card>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Cost Breakdown</h3>
                <div class="space-y-3">
                    @php $costItems = [
                        ['Buying Price', $product->buying_price],
                        ['Amazon Fee', $product->fba_fee],
                        ['Shipping', $product->shipping_cost],
                        ['Labeling', $product->labeling_cost],
                        ['Other Costs', $product->other_costs],
                        ['Operation Cost', $product->operation_cost],
                    ]; @endphp
                    @foreach($costItems as $item)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item[0] }}</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">${{ number_format($item[1], 2) }}</span>
                    </div>
                    @endforeach
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Total Cost</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">${{ number_format($totalCost, 2) }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Market Data -->
            <x-card>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Amazon Market Data</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Sell Price</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">${{ number_format($product->amazon_sell_price, 2) }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Amazon Fee</span>
                        <span class="text-sm font-medium text-red-600 dark:text-red-400">${{ number_format($amazonFee, 2) }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">FBA Buy Box</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">${{ number_format($product->fba_buy_box_price ?? 0, 2) }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">FBM Buy Box</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">${{ number_format($product->fbm_buy_box_price ?? 0, 2) }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Referral Fee %</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ number_format($product->referral_fee_percent, 2) }}%</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400"># Sellers</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $product->number_of_sellers ?? 0 }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Buy Box</span>
                        @if($product->buy_box_type === 'fba')
                        <span class="inline-flex w-fit px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded-full">FBA</span>
                        @elseif($product->buy_box_type === 'fbm')
                        <span class="inline-flex w-fit px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">FBM</span>
                        @else
                        <span class="text-sm text-gray-400">—</span>
                        @endif
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">BSR Rank</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $product->bsr_rank ? number_format($product->bsr_rank) : '—' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Reviews</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $product->review_count ? number_format($product->review_count) : '—' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Rating</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $product->review_rating ? number_format($product->review_rating, 1) . ' / 5' : '—' }}</span>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Notes -->
        @if($product->notes)
        <x-card>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Notes</h3>
            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $product->notes }}</div>
        </x-card>
        @endif

        <!-- Danger Zone -->
        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Delete Product</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Permanently remove this product from the system.</div>
            </div>
            <form method="POST" action="{{ route('products.destroy', $product->id) }}" onsubmit="return confirm('Delete this product?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Delete</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div x-data="{ show: false }" @open-edit-product.window="show = true" @keydown.escape.window="show = false" x-cloak>
        <div x-show="show" x-transition.opacity class="fixed inset-0 z-50 bg-black/50" @click="show = false"></div>
        <div x-show="show" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="show = false">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Edit Product</h3>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('products.update', $product->id) }}" class="p-4 space-y-3">
                    @csrf
                    @method('PUT')
                    @include('vendors._product_form', ['product' => $product, 'categories' => $categories])
                    <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="show = false" class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Product Calculator Script -->
    <script>
        function productCalculator(initial) {
            initial = initial || {};
            return {
                showAdvanced: false,
                asin: initial.asin || '',
                asinWarning: '',
                buyingPrice: initial.buyingPrice || 0,
                fbaFee: initial.fbaFee || 0,
                shippingCost: initial.shippingCost || 0,
                labelingCost: initial.labelingCost || 0,
                otherCosts: initial.otherCosts || 0,
                operationCost: initial.operationCost || 0,
                sellPrice: initial.sellPrice || 0,
                referralPercent: initial.referralPercent || 0,
                existingAsins: initial.existingAsins || {},
                get totalCost() {
                    const referral = (this.sellPrice || 0) * (this.referralPercent || 0) / 100;
                    return (this.buyingPrice || 0) + (this.fbaFee || 0) + referral + (this.shippingCost || 0) +
                           (this.labelingCost || 0) + (this.otherCosts || 0) + (this.operationCost || 0);
                },
                get referralFee() {
                    return (this.sellPrice || 0) * (this.referralPercent || 0) / 100;
                },
                get netProfit() {
                    return (this.sellPrice || 0) - this.totalCost;
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
            };
        }
    </script>
</x-app-layout>
