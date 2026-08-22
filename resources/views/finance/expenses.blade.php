<x-app-layout>
    @php
    $statusColors = [
        'pending' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => 'bg-gray-400'],
        'processing' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400', 'dot' => 'bg-blue-500'],
        'shipped' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'text' => 'text-cyan-700 dark:text-cyan-400', 'dot' => 'bg-cyan-500'],
        'delivered' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400', 'dot' => 'bg-green-500'],
        'returned' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400', 'dot' => 'bg-yellow-500'],
        'refunded' => ['bg' => 'bg-orange-50 dark:bg-orange-900/30', 'text' => 'text-orange-700 dark:text-orange-400', 'dot' => 'bg-orange-500'],
        'cancelled' => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400', 'dot' => 'bg-red-500'],
    ];
    $catBadgeColors = [
        'shipping' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        'labeling' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        'inventory' => 'bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400',
        'amazon_fees' => 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        'fba_fees' => 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        'amazon_referral' => 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        'prep' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        'inspection' => 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400',
        'customs' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        'freight' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        'insurance' => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        'advertising' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        'storage' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
        'returns' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        'supplies' => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        'software' => 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400',
        'fees' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
        'other' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
    ];
    @endphp

    <x-page-header title="Expenses" :back="route('dashboard')" :count="number_format($totalExpenses, 2) . ' total'">
        <div x-data="{ show: false }" @open-expense-modal.window="show = true" @keydown.escape.window="show = false">
            <x-button variant="primary" @click="$dispatch('open-expense-modal')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Expense
            </x-button>
        </div>
    </x-page-header>

    <div class="space-y-4">
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Expenses</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">${{ number_format($totalExpenses, 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pending Approval</div>
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">${{ number_format($pendingExpenses, 2) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 col-span-2">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">By Category</div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($categoryTotals as $cat => $total)
                    @if($total > 0)
                    <span class="px-2 py-1 text-xs rounded-md {{ $catBadgeColors[$cat] ?? $catBadgeColors['other'] }}">
                        {{ $categoryLabels[$cat] ?? ucfirst($cat) }}: ${{ number_format($total, 0) }}
                    </span>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description or #" class="flex-1 min-w-[180px] rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                <select name="category" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Categories</option>
                    @foreach($categoryLabels as $val => $label)
                    <option value="{{ $val }}" @selected(request('category') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3">
                    <option value="">All Statuses</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Filter</button>
                @if(request()->hasAny(['search', 'category', 'status', 'vendor_id', 'date_from', 'date_to']))
                <a href="{{ route('finance.expenses.index') }}" class="px-3 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-600">Clear</a>
                @endif
            </form>
        </div>

        <!-- Expenses Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Expense #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Service Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Amount</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($expenses as $expense)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ $expense->expense_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $expense->description }}
                                @if($expense->vendor_name)
                                    <div class="text-xs text-gray-400">{{ $expense->vendor_name }}</div>
                                @elseif($expense->vendor)
                                    <div class="text-xs text-gray-400">{{ $expense->vendor->brand_name }}</div>
                                @endif
                                @if($expense->purchase_order_id)
                                    <a href="{{ route('finance.po.show', $expense->purchase_order_id) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View PO</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-xs rounded-md {{ $catBadgeColors[$expense->category] ?? $catBadgeColors['other'] }}">{{ $categoryLabels[$expense->category] ?? ucfirst($expense->category) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if($expense->relationLoaded('serviceVendor') && $expense->serviceVendor)
                                    <span class="text-xs">{{ $expense->serviceVendor->brand_name }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $expense->expense_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-red-600 dark:text-red-400 text-right">${{ number_format($expense->amount, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('finance.expenses.status', $expense->id) }}" class="inline">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 py-1 px-2">
                                        <option value="pending" @selected($expense->status === 'pending')>Pending</option>
                                        <option value="approved" @selected($expense->status === 'approved')>Approved</option>
                                        <option value="paid" @selected($expense->status === 'paid')>Paid</option>
                                        <option value="rejected" @selected($expense->status === 'rejected')>Rejected</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="text-sm text-gray-500 dark:text-gray-400">No expenses recorded yet.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($expenses->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $expenses->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div x-data="{ show: false, linkedPO: '' }" @open-expense-modal.window="show = true" @keydown.escape.window="show = false" x-cloak>
        <div x-show="show" x-transition.opacity class="fixed inset-0 z-50 bg-black/50" @click="show = false"></div>
        <div x-show="show" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="show = false">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">New Expense</h3>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('finance.expenses.store') }}" class="p-5 space-y-4">
                    @csrf

                    {{-- Section: Basics --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description *</label>
                            <input type="text" name="description" required placeholder="e.g. Freight charges for PO-0042" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Category *</label>
                                <select name="category" required class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach($categoryLabels as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Amount *</label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                    <input type="number" name="amount" required step="0.01" min="0" placeholder="0.00" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm pl-6 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date *</label>
                                <input type="date" name="expense_date" required value="{{ date('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-gray-100 dark:border-gray-700"></div>

                    {{-- Section: Vendor & PO Link --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Vendor & Purchase Order</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Service Vendor</label>
                                <select name="service_vendor_id" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">— None —</option>
                                    @foreach($vendors ?? [] as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->brand_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product Vendor</label>
                                <select name="vendor_id" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">— None —</option>
                                    @foreach($vendors ?? [] as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->brand_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Linked Purchase Order</label>
                            <select name="purchase_order_id" x-model="linkedPO" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">— None —</option>
                                @foreach($purchaseOrders ?? [] as $po)
                                <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->vendor?->brand_name ?? 'Unknown' }} (${{ number_format($po->total_amount, 0) }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Select the PO this expense belongs to for landed cost calculation.</p>
                        </div>
                        <div x-show="linkedPO !== ''" x-transition x-cloak>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Allocation Method</label>
                            <select name="allocation_method" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach(\App\Models\Expense::allocationMethodLabels() as $val => $label)
                                <option value="{{ $val }}" @selected($val === 'by_quantity')>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">How to distribute this expense across PO line items.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                            <select name="status" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-gray-100 dark:border-gray-700"></div>

                    {{-- Section: Additional --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Additional Details</h4>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Payment Method</label>
                            <input type="text" name="payment_method" placeholder="Credit Card, Wire Transfer, etc." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                            <textarea name="notes" rows="2" placeholder="Any additional context..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="show = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
