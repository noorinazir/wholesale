<x-app-layout>
    <x-page-header title="Edit Sale" :back="route('finance.sales.show', $order->id)">
    </x-page-header>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('finance.sales.update', $order->id) }}">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Sale Details</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Amazon Order ID</label>
                        <input type="text" name="amazon_order_id" value="{{ $order->amazon_order_id }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Order Date *</label>
                        <input type="date" name="order_date" required value="{{ $order->order_date->format('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product Name *</label>
                        <input type="text" name="product_name" required value="{{ $order->product_name }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ASIN</label>
                        <input type="text" name="asin" value="{{ $order->asin }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Quantity *</label>
                        <input type="number" name="quantity" required min="1" value="{{ $order->quantity }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sale Price *</label>
                        <input type="number" name="sale_price" required step="0.01" min="0" value="{{ $order->sale_price }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fulfillment</label>
                        <select name="fulfillment_channel" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="FBA" @selected($order->fulfillment_channel === 'FBA')>FBA</option>
                            <option value="FBM" @selected($order->fulfillment_channel === 'FBM')>FBM</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                        <select name="order_status" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            @foreach(['pending','processing','shipped','delivered','returned','refunded','cancelled'] as $st)
                            <option value="{{ $st }}" @selected($order->order_status === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 pt-2">Costs (per unit)</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product Cost</label>
                        <input type="number" name="product_cost" step="0.01" min="0" value="{{ $order->product_cost / max(1, $order->quantity) }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Amazon Fee (incl. referral)</label>
                        <input type="number" name="fba_fee" step="0.01" min="0" value="{{ $order->fba_fee / max(1, $order->quantity) }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <input type="hidden" name="amazon_referral_fee" value="0">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Shipping</label>
                        <input type="number" name="shipping_cost" step="0.01" min="0" value="{{ $order->shipping_cost / max(1, $order->quantity) }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Labeling</label>
                        <input type="number" name="labeling_cost" step="0.01" min="0" value="{{ $order->labeling_cost / max(1, $order->quantity) }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Operation</label>
                        <input type="number" name="operation_cost" step="0.01" min="0" value="{{ $order->operation_cost / max(1, $order->quantity) }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Advertising</label>
                        <input type="number" name="advertising_cost" step="0.01" min="0" value="{{ $order->advertising_cost / max(1, $order->quantity) }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Return Cost</label>
                        <input type="number" name="return_cost" step="0.01" min="0" value="{{ $order->return_cost / max(1, $order->quantity) }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                </div>

                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 pt-2">Tax & Customer</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tax State</label>
                        <select name="tax_state" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="">None</option>
                            @foreach($taxRates as $rate)
                            <option value="{{ $rate->state_code }}" @selected($order->tax_state === $rate->state_code)>{{ $rate->state_code }} ({{ number_format($rate->combined_rate, 2) }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Customer Name</label>
                        <input type="text" name="customer_name" value="{{ $order->customer_name }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Customer State</label>
                        <input type="text" name="customer_state" value="{{ $order->customer_state }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">{{ $order->notes }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-4">
                <a href="{{ route('finance.sales.show', $order->id) }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancel</a>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save Changes</button>
            </div>
        </form>
    </div>
</x-app-layout>
