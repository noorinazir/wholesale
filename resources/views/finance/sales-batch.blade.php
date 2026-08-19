<x-app-layout>
    <x-page-header title="Batch Sales Entry" :back="route('finance.sales.index')">
        <a href="{{ route('finance.sales.create') }}" class="px-3.5 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700">Single Entry</a>
    </x-page-header>

    <div x-data="batchSales()" x-init="init()" class="max-w-6xl mx-auto space-y-4">
        @if(session('status'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
        @endif

        <!-- Info Banner -->
        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-sm text-indigo-700 dark:text-indigo-300">
                    Select a product to autofill costs and pricing. Add up to 20 rows per batch. All rows share the same order date and fulfillment channel — set them once below.
                </div>
            </div>
        </div>

        <!-- Shared Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Shared Settings (applies to all rows)</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Order Date</label>
                    <input type="date" x-model="sharedDate" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fulfillment</label>
                    <select x-model="sharedFulfillment" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                        <option value="FBA">FBA</option>
                        <option value="FBM">FBM</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Order Status</label>
                    <select x-model="sharedStatus" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                        <option value="delivered">Delivered</option>
                        <option value="shipped">Shipped</option>
                        <option value="processing">Processing</option>
                        <option value="pending">Pending</option>
                        <option value="returned">Returned</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Batch Table -->
        <form method="POST" action="{{ route('finance.sales.batch.store') }}" @submit="prepareSubmit($event)">
            @csrf
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-8">#</th>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase min-w-[200px]">Product</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-20">Qty</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-28">Sale Price</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-28">Revenue</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-28">Costs</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-24">Profit</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-28">Margin</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20" :class="{ 'bg-red-50 dark:bg-red-900/10': row.error }">
                                    <td class="px-3 py-2 text-xs text-gray-400 font-medium" x-text="idx + 1"></td>
                                    <td class="px-3 py-2">
                                        <select x-model="row.product_id" @change="autofill(idx)" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-xs">
                                            <option value="">— Select Product —</option>
                                            <template x-for="p in productList" :key="p.id">
                                                <option :value="p.id" x-text="p.name + (p.asin ? ' (' + p.asin + ')' : '')"></option>
                                            </template>
                                        </select>
                                        <input type="hidden" :name="`rows[${idx}][product_id]`" :value="row.product_id">
                                        <input type="hidden" :name="`rows[${idx}][product_name]`" :value="row.product_name">
                                        <input type="hidden" :name="`rows[${idx}][asin]`" :value="row.asin">
                                        <input type="hidden" :name="`rows[${idx}][order_date]`" :value="sharedDate">
                                        <input type="hidden" :name="`rows[${idx}][fulfillment_channel]`" :value="sharedFulfillment">
                                        <input type="hidden" :name="`rows[${idx}][order_status]`" :value="sharedStatus">
                                        <input type="hidden" :name="`rows[${idx}][product_cost]`" :value="row.product_cost">
                                        <input type="hidden" :name="`rows[${idx}][fba_fee]`" :value="row.fba_fee">
                                        <input type="hidden" :name="`rows[${idx}][shipping_cost]`" :value="row.shipping_cost">
                                        <input type="hidden" :name="`rows[${idx}][labeling_cost]`" :value="row.labeling_cost">
                                        <input type="hidden" :name="`rows[${idx}][other_costs]`" :value="row.other_costs">
                                        <input type="hidden" :name="`rows[${idx}][operation_cost]`" :value="row.operation_cost">
                                        <input type="hidden" :name="`rows[${idx}][amazon_referral_fee]`" :value="0">
                                        <input type="hidden" :name="`rows[${idx}][amazon_order_id]`" :value="row.amazon_order_id">
                                        <div class="text-[10px] text-gray-400 mt-0.5" x-show="row.vendor_name" x-text="row.vendor_name"></div>
                                        <div class="text-[10px] text-gray-400 mt-0.5" x-show="row.stock !== null" x-text="'Stock: ' + row.stock"></div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="row.quantity" min="1" class="block w-16 rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-xs text-center">
                                        <input type="hidden" :name="`rows[${idx}][quantity]`" :value="row.quantity">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" x-model.number="row.sale_price" class="block w-24 rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-xs text-right">
                                        <input type="hidden" :name="`rows[${idx}][sale_price]`" :value="row.sale_price">
                                    </td>
                                    <td class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-300" x-text="formatCurrency(revenue(idx))"></td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-500 dark:text-gray-400" x-text="formatCurrency(totalCost(idx))"></td>
                                    <td class="px-3 py-2 text-right text-xs font-medium" :class="profit(idx) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="formatCurrency(profit(idx))"></td>
                                    <td class="px-3 py-2 text-center text-xs" :class="margin(idx) >= 15 ? 'text-green-600 dark:text-green-400' : (margin(idx) >= 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400')" x-text="margin(idx).toFixed(0) + '%'"></td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeRow(idx)" x-show="rows.length > 1" class="text-gray-400 hover:text-red-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700/30 border-t-2 border-gray-200 dark:border-gray-700">
                            <tr>
                                <td colspan="4" class="px-3 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Totals</td>
                                <td class="px-3 py-3 text-right text-sm font-bold text-gray-800 dark:text-gray-200" x-text="formatCurrency(totalRevenue())"></td>
                                <td class="px-3 py-3 text-right text-sm font-medium text-gray-600 dark:text-gray-400" x-text="formatCurrency(totalCosts())"></td>
                                <td class="px-3 py-3 text-right text-sm font-bold" :class="totalProfit() >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="formatCurrency(totalProfit())"></td>
                                <td class="px-3 py-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400" x-text="avgMargin().toFixed(0) + '%'"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-2">
                <button type="button" @click="addRow()" x-show="rows.length < 20" class="px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Row
                </button>
                <div class="flex items-center gap-3 ml-auto">
                    <a href="{{ route('finance.sales.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Cancel</a>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save Batch (<span x-text="validRowCount()"></span> rows)
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        const productMap = @json($productMap);
        const taxRates = @json($taxRates->pluck('rate', 'state_code'));

        function batchSales() {
            return {
                rows: [createRow()],
                sharedDate: new Date().toISOString().split('T')[0],
                sharedFulfillment: 'FBA',
                sharedStatus: 'delivered',
                productList: Object.values(productMap).sort((a, b) => a.name.localeCompare(b.name)),

                init() {},

                addRow() {
                    if (this.rows.length < 20) {
                        this.rows.push(createRow());
                    }
                },

                removeRow(idx) {
                    this.rows.splice(idx, 1);
                },

                autofill(idx) {
                    const row = this.rows[idx];
                    const p = productMap[row.product_id];
                    if (!p) return;

                    row.product_name = p.name;
                    row.asin = p.asin;
                    row.vendor_name = p.vendor_name;
                    row.sale_price = p.sale_price;
                    row.product_cost = p.buying_price * (row.quantity || 1);
                    row.fba_fee = p.fba_fee * (row.quantity || 1);
                    row.shipping_cost = p.shipping_cost * (row.quantity || 1);
                    row.labeling_cost = p.labeling_cost * (row.quantity || 1);
                    row.other_costs = p.other_costs * (row.quantity || 1);
                    row.operation_cost = p.operation_cost * (row.quantity || 1);
                    row.stock = p.stock;
                },

                revenue(idx) {
                    const r = this.rows[idx];
                    return (r.sale_price || 0) * (r.quantity || 0);
                },

                totalCost(idx) {
                    const r = this.rows[idx];
                    return (parseFloat(r.product_cost) || 0) + (parseFloat(r.fba_fee) || 0) +
                           (parseFloat(r.shipping_cost) || 0) + (parseFloat(r.labeling_cost) || 0) +
                           (parseFloat(r.other_costs) || 0) + (parseFloat(r.operation_cost) || 0);
                },

                profit(idx) {
                    return this.revenue(idx) - this.totalCost(idx);
                },

                margin(idx) {
                    const rev = this.revenue(idx);
                    return rev > 0 ? (this.profit(idx) / rev) * 100 : 0;
                },

                totalRevenue() {
                    return this.rows.reduce((sum, _, idx) => sum + this.revenue(idx), 0);
                },

                totalCosts() {
                    return this.rows.reduce((sum, _, idx) => sum + this.totalCost(idx), 0);
                },

                totalProfit() {
                    return this.totalRevenue() - this.totalCosts();
                },

                avgMargin() {
                    const rev = this.totalRevenue();
                    return rev > 0 ? (this.totalProfit() / rev) * 100 : 0;
                },

                validRowCount() {
                    return this.rows.filter(r => r.product_id || r.product_name).length;
                },

                formatCurrency(val) {
                    return '$' + (val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                prepareSubmit(e) {
                    this.rows = this.rows.filter(r => r.product_id || r.product_name);
                    if (this.validRowCount() === 0) {
                        e.preventDefault();
                        alert('Add at least one product before saving.');
                    }
                },
            }
        }

        function createRow() {
            return {
                product_id: '',
                product_name: '',
                asin: '',
                vendor_name: '',
                quantity: 1,
                sale_price: 0,
                product_cost: 0,
                fba_fee: 0,
                shipping_cost: 0,
                labeling_cost: 0,
                other_costs: 0,
                operation_cost: 0,
                amazon_order_id: '',
                stock: null,
            };
        }
    </script>
    @endpush
</x-app-layout>
