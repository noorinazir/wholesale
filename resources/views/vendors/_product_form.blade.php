@php
    $isEdit = !empty($product);
    $existingAsins = $isEdit ? [] : \App\Models\Product::whereNotNull('asin')->pluck('product_name', 'asin')->toArray();
@endphp

<div x-data="productCalculator()">
    {{-- Essential Fields --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product Name *</label>
            <input type="text" name="product_name" required value="{{ $product?->product_name }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ASIN</label>
            <input type="text" name="asin" value="{{ $product?->asin }}" x-model="asin" x-on:input="checkAsin()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            <p x-show="asinWarning" x-transition class="text-xs text-amber-600 dark:text-amber-400 mt-1" x-text="asinWarning"></p>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Category</label>
            <select name="product_category" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                <option value="">Select...</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" @selected($product?->product_category === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
            <select name="status" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                <option value="active" @selected($product?->status === 'active' || !$product)>Active</option>
                <option value="inactive" @selected($product?->status === 'inactive')>Inactive</option>
                <option value="discontinued" @selected($product?->status === 'discontinued')>Discontinued</option>
            </select>
        </div>
    </div>

    {{-- Costs (always visible - core to decision making) --}}
    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
        <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-2">Costs</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buying Price ($)</label>
                <input type="number" step="0.01" min="0" name="buying_price" x-model.number="buyingPrice" value="{{ $product?->buying_price ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Amazon Fee ($)</label>
                <input type="number" step="0.01" min="0" name="fba_fee" x-model.number="fbaFee" value="{{ $product?->fba_fee ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Shipping ($)</label>
                <input type="number" step="0.01" min="0" name="shipping_cost" x-model.number="shippingCost" value="{{ $product?->shipping_cost ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Labeling ($)</label>
                <input type="number" step="0.01" min="0" name="labeling_cost" x-model.number="labelingCost" value="{{ $product?->labeling_cost ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Other ($)</label>
                <input type="number" step="0.01" min="0" name="other_costs" x-model.number="otherCosts" value="{{ $product?->other_costs ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Op. Cost ($)</label>
                <input type="number" step="0.01" min="0" name="operation_cost" x-model.number="operationCost" value="{{ $product?->operation_cost ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            </div>
        </div>
    </div>

    {{-- Sell Price + Live Profit Calculator --}}
    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sell Price ($)</label>
                <input type="number" step="0.01" min="0" name="amazon_sell_price" x-model.number="sellPrice" value="{{ $product?->amazon_sell_price ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Referral Fee %</label>
                <input type="number" step="0.01" min="0" max="100" name="referral_fee_percent" x-model.number="referralPercent" value="{{ $product?->referral_fee_percent ?? 15.00 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
            </div>
            <div class="rounded-lg p-3" :class="netProfit > 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Total Cost</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-300" x-text="'$' + totalCost.toFixed(2)"></span>
                </div>
                <div class="flex items-center justify-between text-xs mt-1">
                    <span class="text-gray-500 dark:text-gray-400">Net Profit</span>
                    <span class="font-bold" :class="netProfit > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="'$' + netProfit.toFixed(2)"></span>
                </div>
                <div class="flex items-center justify-between text-xs mt-1">
                    <span class="text-gray-500 dark:text-gray-400">Margin</span>
                    <span class="font-bold" :class="marginPercent >= 15 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="marginPercent.toFixed(1) + '%'"></span>
                </div>
                <div class="flex items-center justify-between text-xs mt-1">
                    <span class="text-gray-500 dark:text-gray-400">ROI</span>
                    <span class="font-medium" :class="roiPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="roiPercent.toFixed(1) + '%'"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Collapsible Advanced Section --}}
    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
        <button type="button" @click="showAdvanced = !showAdvanced" class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            <svg class="w-3.5 h-3.5 transition-transform" :class="showAdvanced ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            Advanced (UPC, Amazon Market Data, Notes)
        </button>
        <div x-show="showAdvanced" x-transition class="mt-3 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">UPC</label>
                    <input type="text" name="upc" value="{{ $product?->upc }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Image URL</label>
                    <input type="text" name="image_url" value="{{ $product?->image_url }}" placeholder="https://..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">FBA Buy Box ($)</label>
                    <input type="number" step="0.01" min="0" name="fba_buy_box_price" value="{{ $product?->fba_buy_box_price }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">FBM Buy Box ($)</label>
                    <input type="number" step="0.01" min="0" name="fbm_buy_box_price" value="{{ $product?->fbm_buy_box_price }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"># of Sellers</label>
                    <input type="number" min="0" name="number_of_sellers" value="{{ $product?->number_of_sellers ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buy Box Type</label>
                    <select name="buy_box_type" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                        <option value="none" @selected($product?->buy_box_type === 'none' || !$product)>None</option>
                        <option value="fba" @selected($product?->buy_box_type === 'fba')>FBA</option>
                        <option value="fbm" @selected($product?->buy_box_type === 'fbm')>FBM</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">BSR Rank</label>
                    <input type="number" min="0" name="bsr_rank" value="{{ $product?->bsr_rank }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Reviews</label>
                    <input type="number" min="0" name="review_count" value="{{ $product?->review_count }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Rating (0-5)</label>
                    <input type="number" step="0.1" min="0" max="5" name="review_rating" value="{{ $product?->review_rating }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">{{ $product?->notes }}</textarea>
            </div>
        </div>
    </div>

    @if(!isset($hideScript) || !$hideScript)
    <script>
        function productCalculator() {
            return {
                showAdvanced: false,
                asin: '',
                asinWarning: '',
                buyingPrice: {{ $product?->buying_price ?? 0 }},
                fbaFee: {{ $product?->fba_fee ?? 0 }},
                shippingCost: {{ $product?->shipping_cost ?? 0 }},
                labelingCost: {{ $product?->labeling_cost ?? 0 }},
                otherCosts: {{ $product?->other_costs ?? 0 }},
                operationCost: {{ $product?->operation_cost ?? 0 }},
                sellPrice: {{ $product?->amazon_sell_price ?? 0 }},
                referralPercent: {{ $product?->referral_fee_percent ?? 15.00 }},
                existingAsins: @json($existingAsins),
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
    @endif
</div>
