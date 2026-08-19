<x-app-layout>
    <x-page-header title="Import Amazon Sales (CSV)" :back="route('finance.sales.index')">
    </x-page-header>

    <div class="max-w-3xl mx-auto space-y-4">
        <!-- Import Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Upload CSV File</h3>
            <form method="POST" action="{{ route('finance.sales.import.csv.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">CSV File *</label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:text-white file:text-sm file:font-medium file:cursor-pointer hover:file:bg-indigo-700">
                    <p class="text-xs text-gray-400 mt-1">Max 5MB. CSV format only.</p>
                </div>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Import Sales
                </button>
            </form>
        </div>

        <!-- CSV Format Guide -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">CSV Format</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Your CSV should have a header row with these columns (case-insensitive). Only <strong>product_name</strong> is required — others auto-fill from product catalog.</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Column</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Required</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">product_name</td><td class="px-3 py-2 text-red-500">Yes</td><td class="px-3 py-2 text-gray-500">Product name (or "title")</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">asin</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Used to auto-match product</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">amazon_order_id</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Amazon order ID (or "order_id")</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">order_date</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Date (or "date"). Defaults to today</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">quantity</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Units sold (or "qty"). Defaults to 1</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">sale_price</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Unit price (or "price", "item_price")</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">fulfillment</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">FBA or FBM. Defaults to FBA</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">status</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Order status. Defaults to delivered</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">product_cost</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Per-unit cost (or "cost")</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">fba_fee</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Amazon fee per unit (incl. referral)</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">shipping_cost</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Shipping cost</td></tr>
                        <tr><td class="px-3 py-2 font-mono text-gray-700 dark:text-gray-300">tax_state</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">2-letter state code (or "state")</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sample CSV -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Sample CSV</h3>
                <button type="button" onclick="copySample()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Copy to clipboard</button>
            </div>
            <pre id="sampleCsv" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 text-xs text-gray-700 dark:text-gray-300 overflow-x-auto">product_name,asin,amazon_order_id,order_date,quantity,sale_price,fulfillment,status,product_cost,fba_fee,shipping_cost,tax_state
"Sample Product B0123",B0123ABC,112-1234567-1234567,2025-01-15,2,29.99,FBA,delivered,12.50,4.50,0,CA
"Another Product",B0456DEF,112-7654321-7654321,2025-01-16,1,49.99,FBA,shipped,20.00,5.00,0,NY</pre>
        </div>

        @if(session('status'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
        @endif
    </div>

    <script>
    function copySample() {
        const text = document.getElementById('sampleCsv').textContent;
        navigator.clipboard.writeText(text);
    }
    </script>
</x-app-layout>
