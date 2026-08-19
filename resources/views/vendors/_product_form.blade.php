<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product Name *</label>
        <input type="text" name="product_name" required value="{{ $product?->product_name }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ASIN</label>
        <input type="text" name="asin" value="{{ $product?->asin }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">UPC</label>
        <input type="text" name="upc" value="{{ $product?->upc }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Image URL</label>
        <input type="text" name="image_url" value="{{ $product?->image_url }}" placeholder="https://..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
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

<div class="border-t border-gray-100 dark:border-gray-700 pt-3">
    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-2">Costs</h4>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buying Price ($)</label>
            <input type="number" step="0.01" min="0" name="buying_price" value="{{ $product?->buying_price ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Amazon Fee ($)</label>
            <input type="number" step="0.01" min="0" name="fba_fee" value="{{ $product?->fba_fee ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Shipping ($)</label>
            <input type="number" step="0.01" min="0" name="shipping_cost" value="{{ $product?->shipping_cost ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Labeling ($)</label>
            <input type="number" step="0.01" min="0" name="labeling_cost" value="{{ $product?->labeling_cost ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Other ($)</label>
            <input type="number" step="0.01" min="0" name="other_costs" value="{{ $product?->other_costs ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Operation Cost ($)</label>
            <input type="number" step="0.01" min="0" name="operation_cost" value="{{ $product?->operation_cost ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
    </div>
</div>

<div class="border-t border-gray-100 dark:border-gray-700 pt-3">
    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-2">Amazon Market Data</h4>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sell Price ($)</label>
            <input type="number" step="0.01" min="0" name="amazon_sell_price" value="{{ $product?->amazon_sell_price ?? 0 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">FBA Buy Box ($)</label>
            <input type="number" step="0.01" min="0" name="fba_buy_box_price" value="{{ $product?->fba_buy_box_price }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">FBM Buy Box ($)</label>
            <input type="number" step="0.01" min="0" name="fbm_buy_box_price" value="{{ $product?->fbm_buy_box_price }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Referral Fee %</label>
            <input type="number" step="0.01" min="0" max="100" name="referral_fee_percent" value="{{ $product?->referral_fee_percent ?? 15.00 }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
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
</div>

<div>
    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
    <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">{{ $product?->notes }}</textarea>
</div>
