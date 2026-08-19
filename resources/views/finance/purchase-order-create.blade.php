<x-app-layout>
    <x-page-header title="Create Purchase Order" :back="route('finance.po.index')">
    </x-page-header>

    <div class="max-w-4xl mx-auto space-y-4" x-data="poForm({ products: {{ Js::from($productMap) }} })">
        <form method="POST" action="{{ route('finance.po.store') }}" id="po-form">
            @csrf
            <!-- PO Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">PO Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">PO Number</label>
                        <input type="text" value="{{ $poNumber }}" disabled class="block w-full rounded-lg bg-gray-50 dark:bg-gray-700/50 border-gray-300 dark:border-gray-600 dark:text-gray-400 text-sm font-mono">
                        <input type="hidden" name="po_number" value="{{ $poNumber }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Vendor *</label>
                        <select name="vendor_id" required x-model="vendorId" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="">Select vendor...</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->brand_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Order Date *</label>
                        <input type="date" name="order_date" required value="{{ date('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Expected Delivery</label>
                        <input type="date" name="expected_delivery_date" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                        <select name="status" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="confirmed">Confirmed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Payment Status</label>
                        <select name="payment_status" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="unpaid">Unpaid</option>
                            <option value="partial_paid">Partial Paid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Payment Method</label>
                        <input type="text" name="payment_method" placeholder="Wire, CC, Net 30..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Payment Terms</label>
                        <input type="text" name="payment_terms" placeholder="Net 30, Net 60..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Amount Paid</label>
                        <input type="number" name="amount_paid" step="0.01" min="0" value="0" x-model.number="amountPaid" @input="recalc()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Shipping Cost</label>
                        <input type="number" name="shipping_cost" step="0.01" min="0" value="0" x-model.number="shippingCost" @input="recalc()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tax Amount</label>
                        <input type="number" name="tax_amount" step="0.01" min="0" value="0" x-model.number="taxAmount" @input="recalc()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Discount</label>
                        <input type="number" name="discount_amount" step="0.01" min="0" value="0" x-model.number="discountAmount" @input="recalc()" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm"></textarea>
                </div>
            </div>

            <!-- Line Items -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Line Items</h3>
                    <button type="button" @click="addItem()" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>
                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                                <div class="md:col-span-4">
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Product from Catalog</label>
                                    <select @change="autofillItem(index)" x-model="item.product_id" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                                        <option value="">— Manual entry —</option>
                                        <template x-for="(p, pid) in products" :key="pid">
                                            <option :value="pid" x-text="p.name + ' · ' + p.asin"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Product Name *</label>
                                    <input type="text" :name="`items[${item.id}][product_name]`" required x-model="item.product_name" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">ASIN</label>
                                    <input type="text" :name="`items[${item.id}][asin]`" x-model="item.asin" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                                </div>
                                <div class="md:col-span-3 flex items-end gap-2">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Qty *</label>
                                        <input type="number" :name="`items[${item.id}][quantity_ordered]`" required min="1" x-model.number="item.qty" @input="recalc()" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                                    </div>
                                    <button type="button" x-show="items.length > 1" @click="items.splice(index, 1); recalc()" class="px-2 py-1.5 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md">Remove</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-6 gap-2 mt-2">
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Unit Cost *</label>
                                    <input type="number" :name="`items[${item.id}][unit_cost]`" required step="0.01" min="0" x-model.number="item.unit_cost" @input="recalc()" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Unit Shipping</label>
                                    <input type="number" :name="`items[${item.id}][unit_shipping]`" step="0.01" min="0" x-model.number="item.unit_shipping" @input="recalc()" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Unit Labeling</label>
                                    <input type="number" :name="`items[${item.id}][unit_labeling]`" step="0.01" min="0" x-model.number="item.unit_labeling" @input="recalc()" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Other Costs</label>
                                    <input type="number" :name="`items[${item.id}][unit_other_costs]`" step="0.01" min="0" x-model.number="item.unit_other_costs" @input="recalc()" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Line Total</label>
                                    <div class="px-2 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 rounded-md" x-text="'$' + lineTotal(item).toFixed(2)"></div>
                                </div>
                            </div>
                            <input type="hidden" :name="`items[${item.id}][product_id]`" :value="item.product_id">
                            <input type="hidden" :name="`items[${item.id}][upc]`" :value="item.upc">
                        </div>
                    </template>
                </div>
            </div>

            <!-- Live PO Summary -->
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800 rounded-xl border border-indigo-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 7v3m0 0h.01M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">PO Summary</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Subtotal</div>
                        <div class="text-lg font-bold text-gray-800 dark:text-gray-200" x-text="'$' + subtotal.toFixed(2)"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Shipping</div>
                        <div class="text-lg font-bold text-gray-800 dark:text-gray-200" x-text="'$' + (shippingCost || 0).toFixed(2)"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Tax</div>
                        <div class="text-lg font-bold text-gray-800 dark:text-gray-200" x-text="'$' + (taxAmount || 0).toFixed(2)"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Discount</div>
                        <div class="text-lg font-bold text-red-600 dark:text-red-400" x-text="'-$' + (discountAmount || 0).toFixed(2)"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
                        <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400" x-text="'$' + grandTotal.toFixed(2)"></div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-indigo-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Balance Due: </span>
                        <span class="text-sm font-bold" :class="balanceDue > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" x-text="'$' + balanceDue.toFixed(2)"></span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Items: </span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="items.length"></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('finance.po.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancel</a>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Create Purchase Order
                </button>
            </div>
        </form>
    </div>

    <script>
    function poForm(data) {
        return {
            products: data.products,
            vendorId: '',
            shippingCost: 0,
            taxAmount: 0,
            discountAmount: 0,
            amountPaid: 0,
            items: [{ id: 0, product_id: '', product_name: '', asin: '', upc: '', qty: 1, unit_cost: 0, unit_shipping: 0, unit_labeling: 0, unit_other_costs: 0 }],
            nextId: 1,
            addItem() {
                this.items.push({ id: this.nextId++, product_id: '', product_name: '', asin: '', upc: '', qty: 1, unit_cost: 0, unit_shipping: 0, unit_labeling: 0, unit_other_costs: 0 });
                this.recalc();
            },
            autofillItem(index) {
                const item = this.items[index];
                if (!item.product_id) return;
                const p = this.products[item.product_id];
                if (!p) return;
                item.product_name = p.name;
                item.asin = p.asin || '';
                item.upc = p.upc || '';
                item.unit_cost = p.buying_price || 0;
                item.unit_shipping = p.shipping_cost || 0;
                item.unit_labeling = p.labeling_cost || 0;
                item.unit_other_costs = p.other_costs || 0;
                if (!this.vendorId && p.vendor_id) this.vendorId = p.vendor_id;
                this.recalc();
            },
            lineTotal(item) {
                return ((item.unit_cost || 0) + (item.unit_shipping || 0) + (item.unit_labeling || 0) + (item.unit_other_costs || 0)) * (item.qty || 1);
            },
            get subtotal() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            get grandTotal() {
                return this.subtotal + (this.shippingCost || 0) + (this.taxAmount || 0) - (this.discountAmount || 0);
            },
            get balanceDue() {
                return this.grandTotal - (this.amountPaid || 0);
            },
            recalc() { /* getters auto-recalc */ }
        }
    }
    </script>
</x-app-layout>
