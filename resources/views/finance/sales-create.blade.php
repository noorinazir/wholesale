<x-app-layout>
    <x-page-header title="Record Amazon Sale" :back="route('finance.sales.index')">
    </x-page-header>

    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-4" x-data="saleForm({
        products: {{ Js::from($productMap) }},
        taxRates: {{ Js::from($taxRates->pluck('combined_rate', 'state_code')) }}
    })">
        <!-- Main Form -->
        <form method="POST" action="{{ route('finance.sales.store') }}" class="lg:col-span-2 space-y-4" @submit="submitting = true">
            @csrf
            <input type="hidden" name="product_id" :value="selectedProductId">

            <!-- Mode Toggle -->
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-1.5 w-fit">
                <button type="button" @click="mode = 'quick'" :class="mode === 'quick' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400'" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors">Quick Entry</button>
                <button type="button" @click="mode = 'detailed'" :class="mode === 'detailed' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400'" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors">Detailed</button>
            </div>

            <!-- Product Selection -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Select Product</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product from Catalog</label>
                        <select x-model="selectedProductId" @change="autofillFromProduct()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="">— Select from catalog or fill manually below —</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->product_name }} @if($product->asin) · {{ $product->asin }} @endif · {{ $product->vendor?->brand_name }}</option>
                            @endforeach
                        </select>
                        <div x-show="selectedProductId" x-transition class="mt-2 flex items-center gap-3 text-xs">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full" :class="stockInfo.class">
                                <span x-text="stockInfo.label"></span>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product Name *</label>
                        <input type="text" name="product_name" required x-model="form.product_name" placeholder="Product name" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ASIN</label>
                        <input type="text" name="asin" x-model="form.asin" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Vendor</label>
                        <select name="vendor_id" x-model="form.vendor_id" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="">Select vendor...</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->brand_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Amazon Order ID</label>
                        <input type="text" name="amazon_order_id" x-model="form.amazon_order_id" placeholder="e.g. 112-1234567-1234567" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                </div>
            </div>

            <!-- Sale Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Sale Details</h3>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Order Date *</label>
                        <input type="date" name="order_date" required x-model="form.order_date" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Quantity *</label>
                        <input type="number" name="quantity" required min="1" x-model.number="form.quantity" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sale Price (unit) *</label>
                        <input type="number" name="sale_price" required step="0.01" min="0" x-model.number="form.sale_price" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fulfillment</label>
                        <select name="fulfillment_channel" x-model="form.fulfillment_channel" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="FBA">Amazon FBA</option>
                            <option value="FBM">Seller Fulfilled (FBM)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Order Status</label>
                        <select name="order_status" x-model="form.order_status" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="returned">Returned</option>
                            <option value="refunded">Refunded</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tax State</label>
                        <select name="tax_state" x-model="form.tax_state" @change="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="">None</option>
                            @foreach($taxRates as $rate)
                            <option value="{{ $rate->state_code }}">{{ $rate->state_code }} — {{ $rate->combined_rate }}%</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Costs (Detailed mode only) — NO duplicate hidden fields -->
            <div x-show="mode === 'detailed'" x-transition class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">3</div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Cost Breakdown <span class="text-xs font-normal text-gray-400">(per unit)</span></h3>
                    </div>
                    <span x-show="autofilled" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Auto-filled from product</span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product Cost</label>
                        <input type="number" name="product_cost" step="0.01" min="0" x-model.number="form.product_cost" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Amazon Fee (incl. referral)</label>
                        <input type="number" name="fba_fee" step="0.01" min="0" x-model.number="form.fba_fee" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <input type="hidden" name="amazon_referral_fee" value="0">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Shipping</label>
                        <input type="number" name="shipping_cost" step="0.01" min="0" x-model.number="form.shipping_cost" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Labeling</label>
                        <input type="number" name="labeling_cost" step="0.01" min="0" x-model.number="form.labeling_cost" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Other Costs</label>
                        <input type="number" name="other_costs" step="0.01" min="0" x-model.number="form.other_costs" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Operation Cost</label>
                        <input type="number" name="operation_cost" step="0.01" min="0" x-model.number="form.operation_cost" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Advertising</label>
                        <input type="number" name="advertising_cost" step="0.01" min="0" x-model.number="form.advertising_cost" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Return Cost</label>
                        <input type="number" name="return_cost" step="0.01" min="0" x-model.number="form.return_cost" @input="recalculate()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                </div>
            </div>

            <!-- Hidden cost fields for quick mode — uses :value binding, NOT x-model, to avoid duplicates -->
            <template x-if="mode === 'quick'">
                <div>
                    <input type="hidden" name="product_cost" :value="form.product_cost">
                    <input type="hidden" name="fba_fee" :value="form.fba_fee">
                    <input type="hidden" name="amazon_referral_fee" :value="form.amazon_referral_fee">
                    <input type="hidden" name="shipping_cost" :value="form.shipping_cost">
                    <input type="hidden" name="labeling_cost" :value="form.labeling_cost">
                    <input type="hidden" name="other_costs" :value="form.other_costs">
                    <input type="hidden" name="operation_cost" :value="form.operation_cost">
                    <input type="hidden" name="advertising_cost" :value="form.advertising_cost">
                    <input type="hidden" name="return_cost" :value="form.return_cost">
                </div>
            </template>

            <!-- Live Profit Calculator -->
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800 rounded-xl border border-indigo-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Live Profit Preview</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Revenue</div>
                        <div class="text-lg font-bold text-gray-800 dark:text-gray-200" x-text="'$' + calc.totalRevenue.toFixed(2)"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Cost</div>
                        <div class="text-lg font-bold text-red-600 dark:text-red-400" x-text="'$' + calc.totalCost.toFixed(2)"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Net Profit</div>
                        <div class="text-lg font-bold" :class="calc.netProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="'$' + calc.netProfit.toFixed(2)"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Margin</div>
                        <div class="text-lg font-bold" :class="calc.margin >= 15 ? 'text-green-600 dark:text-green-400' : (calc.margin >= 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400')" x-text="calc.margin.toFixed(1) + '%'"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Tax Collected</div>
                        <div class="text-lg font-bold text-gray-600 dark:text-gray-400" x-text="'$' + calc.tax.toFixed(2)"></div>
                    </div>
                </div>
                <!-- Cost breakdown bar -->
                <div class="mt-3 pt-3 border-t border-indigo-200 dark:border-gray-700">
                    <div class="flex items-center gap-1 h-2 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                        <template x-for="seg in costSegments" :key="seg.label">
                            <div class="h-full" :class="seg.color" :style="'width: ' + seg.pct + '%'" :title="seg.label + ': $' + seg.value.toFixed(2)"></div>
                        </template>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <template x-for="seg in costSegments" :key="seg.label">
                            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-2 h-2 rounded-full" :class="seg.color"></div>
                                <span x-text="seg.label"></span>
                                <span class="font-medium" x-text="'$' + seg.value.toFixed(2)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Optional: Shipping & Customer Info -->
            <div x-show="mode === 'detailed'" x-transition class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <button type="button" @click="showOptional = !showOptional" class="flex items-center gap-2 w-full text-left">
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="showOptional ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Shipping & Customer Info (optional)</h3>
                </button>
                <div x-show="showOptional" x-collapse class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Ship Date</label>
                        <input type="date" name="ship_date" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Delivery Date</label>
                        <input type="date" name="delivery_date" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Customer Name</label>
                        <input type="text" name="customer_name" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Customer State</label>
                        <input type="text" name="customer_state" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Customer City</label>
                        <input type="text" name="customer_city" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Customer ZIP</label>
                        <input type="text" name="customer_zip" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                        <input type="text" name="notes" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between gap-2 pt-2">
                <a href="{{ route('finance.sales.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancel</a>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg flex items-center gap-2" :disabled="submitting">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="submitting ? 'Saving...' : 'Record Sale'"></span>
                </button>
            </div>
        </form>

        <!-- Sidebar: Recent Sales -->
        <div class="lg:col-span-1 space-y-4">
            @if($recentSales->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Recent Sales</h3>
                <div class="space-y-2">
                    @foreach($recentSales as $sale)
                    <a href="{{ route('finance.sales.show', $sale->id) }}" class="block p-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ $sale->product_name }}</div>
                                <div class="text-xs text-gray-400">{{ $sale->order_date->format('M d') }} · {{ $sale->quantity }} units</div>
                            </div>
                            <div class="text-right ml-2">
                                <div class="text-sm font-medium {{ $sale->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $sale->net_profit >= 0 ? '+' : '' }}${{ number_format($sale->net_profit, 2) }}</div>
                                <div class="text-xs text-gray-400">{{ number_format($sale->margin_percent, 0) }}%</div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Tips -->
            <div class="bg-indigo-50 dark:bg-gray-800 rounded-xl border border-indigo-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-indigo-700 dark:text-indigo-400 mb-2">Tips</h3>
                <ul class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                    <li class="flex items-start gap-1.5"><svg class="w-3.5 h-3.5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Selecting a product auto-fills all costs and sale price</li>
                    <li class="flex items-start gap-1.5"><svg class="w-3.5 h-3.5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Quick mode hides cost details — they're auto-filled from product</li>
                    <li class="flex items-start gap-1.5"><svg class="w-3.5 h-3.5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Amazon Fee includes referral fee — no separate calculation needed</li>
                    <li class="flex items-start gap-1.5"><svg class="w-3.5 h-3.5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Sale auto-links to the latest PO for this product</li>
                    <li class="flex items-start gap-1.5"><svg class="w-3.5 h-3.5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Stock auto-deducts on sale, auto-restocks on return</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    function saleForm(data) {
        return {
            mode: 'quick',
            showOptional: false,
            submitting: false,
            autofilled: false,
            selectedProductId: '',
            products: data.products,
            taxRates: data.taxRates,
            form: {
                product_name: '',
                asin: '',
                vendor_id: '',
                amazon_order_id: '',
                order_date: new Date().toISOString().split('T')[0],
                quantity: 1,
                sale_price: 0,
                fulfillment_channel: 'FBA',
                order_status: 'pending',
                tax_state: '',
                product_cost: 0,
                fba_fee: 0,
                amazon_referral_fee: 0,
                shipping_cost: 0,
                labeling_cost: 0,
                other_costs: 0,
                operation_cost: 0,
                advertising_cost: 0,
                return_cost: 0,
            },
            calc: {
                totalRevenue: 0,
                totalCost: 0,
                netProfit: 0,
                margin: 0,
                tax: 0,
            },
            get stockInfo() {
                if (!this.selectedProductId) return { label: '', class: '' };
                const p = this.products[this.selectedProductId];
                if (!p) return { label: '', class: '' };
                const stock = p.stock || 0;
                if (stock <= 0) return { label: 'Out of stock', class: 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400' };
                if (stock < 10) return { label: 'Low stock: ' + stock + ' units', class: 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' };
                return { label: 'In stock: ' + stock + ' units', class: 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400' };
            },
            autofillFromProduct() {
                if (!this.selectedProductId) { this.autofilled = false; return; }
                const p = this.products[this.selectedProductId];
                if (!p) return;
                this.form.product_name = p.name;
                this.form.asin = p.asin || '';
                this.form.vendor_id = p.vendor_id || '';
                this.form.sale_price = p.sale_price || 0;
                this.form.product_cost = p.buying_price || 0;
                this.form.fba_fee = p.fba_fee || 0;
                this.form.shipping_cost = p.shipping_cost || 0;
                this.form.labeling_cost = p.labeling_cost || 0;
                this.form.other_costs = p.other_costs || 0;
                this.form.operation_cost = p.operation_cost || 0;
                this.form.amazon_referral_fee = 0;
                this.autofilled = true;
                this.recalculate();
            },
            recalculate() {
                const qty = this.form.quantity || 1;
                const price = this.form.sale_price || 0;
                const revenue = price * qty;
                const productCost = (this.form.product_cost || 0) * qty;
                const amazonFee = (this.form.fba_fee || 0) * qty;
                const shipping = (this.form.shipping_cost || 0) * qty;
                const labeling = (this.form.labeling_cost || 0) * qty;
                const other = (this.form.other_costs || 0) * qty;
                const operation = (this.form.operation_cost || 0) * qty;
                const advertising = (this.form.advertising_cost || 0) * qty;
                const returns = (this.form.return_cost || 0) * qty;
                const totalCost = productCost + amazonFee + shipping + labeling + other + operation + advertising + returns;
                const netProfit = revenue - totalCost;
                const margin = revenue > 0 ? (netProfit / revenue) * 100 : 0;
                let tax = 0;
                if (this.form.tax_state && this.taxRates[this.form.tax_state]) {
                    tax = revenue * (this.taxRates[this.form.tax_state] / 100);
                }
                this.calc = { totalRevenue: revenue, totalCost, netProfit, margin, tax };
            },
            get costSegments() {
                const qty = this.form.quantity || 1;
                const segments = [
                    { label: 'Product', value: (this.form.product_cost || 0) * qty, color: 'bg-blue-500' },
                    { label: 'Amazon Fee', value: (this.form.fba_fee || 0) * qty, color: 'bg-orange-500' },
                    { label: 'Shipping', value: (this.form.shipping_cost || 0) * qty, color: 'bg-cyan-500' },
                    { label: 'Labeling', value: (this.form.labeling_cost || 0) * qty, color: 'bg-purple-500' },
                    { label: 'Other', value: ((this.form.other_costs || 0) + (this.form.operation_cost || 0) + (this.form.advertising_cost || 0) + (this.form.return_cost || 0)) * qty, color: 'bg-gray-400' },
                ];
                const total = segments.reduce((s, seg) => s + seg.value, 0) || 1;
                return segments.map(s => ({ ...s, pct: (s.value / total) * 100 })).filter(s => s.value > 0);
            },
            init() { this.recalculate(); }
        }
    }
    </script>
</x-app-layout>
