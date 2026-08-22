<x-app-layout>
    <x-page-header title="Upload Amazon Settlement" :back="route('finance.settlements.index')">
    </x-page-header>

    <div class="max-w-3xl mx-auto space-y-4">
        @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</div>
        @endif

        <!-- Upload Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Upload Amazon Settlement File</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Upload an Amazon settlement report (CSV, TSV, or text). The file will be parsed using AI (Kimi) to extract transactions, fees, and refunds. You'll review the results before anything is imported.
            </p>
            <form method="POST" action="{{ route('finance.settlements.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Settlement File *</label>
                    <input type="file" name="file" accept=".csv,.txt,.tsv,.xml" required class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:text-white file:text-sm file:font-medium file:cursor-pointer hover:file:bg-indigo-700">
                    <p class="text-xs text-gray-400 mt-1">Max 10MB. Supported: CSV, TSV, TXT, XML.</p>
                </div>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Parse & Preview
                </button>
            </form>
        </div>

        <!-- How It Works -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">How It Works</h3>
            <ol class="space-y-3 text-xs text-gray-600 dark:text-gray-400">
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-semibold text-[10px]">1</span>
                    <span><strong class="text-gray-700 dark:text-gray-300">Upload</strong> your Amazon settlement report file from Seller Central.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-semibold text-[10px]">2</span>
                    <span><strong class="text-gray-700 dark:text-gray-300">AI Parse</strong> — Kimi AI extracts and normalizes each transaction (orders, fees, refunds, adjustments).</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-semibold text-[10px]">3</span>
                    <span><strong class="text-gray-700 dark:text-gray-300">Auto-Match</strong> — transactions are matched to existing orders, products, and vendors by order ID, ASIN, or SKU.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-semibold text-[10px]">4</span>
                    <span><strong class="text-gray-700 dark:text-gray-300">Review</strong> — preview all transactions, check for duplicates, and review unmatched items.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-semibold text-[10px]">5</span>
                    <span><strong class="text-gray-700 dark:text-gray-300">Commit</strong> — confirm to auto-create expense records for fees and reconcile orders with actual settlement amounts.</span>
                </li>
            </ol>
        </div>

        <!-- Where to get settlement files -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <h4 class="text-xs font-semibold text-blue-800 dark:text-blue-400 mb-1">Where to get Amazon settlement reports</h4>
            <p class="text-xs text-blue-600 dark:text-blue-500">
                Seller Central → Reports → Payments → Settlement Report → Download (CSV/TSV format).
                Or via SP-API: Report type <code class="text-[10px] bg-blue-100 dark:bg-blue-900/40 px-1 rounded">GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE</code>
            </p>
        </div>
    </div>
</x-app-layout>
