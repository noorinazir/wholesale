<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('vendors.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Import Vendors</h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Step indicator -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                @foreach(['Upload', 'Map Columns', 'Preview', 'Import'] as $i => $stepName)
                <div class="flex items-center {{ $i < 3 ? 'flex-1' : '' }}">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium {{ $i + 1 <= ($step ?? 1) ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' }}">{{ $i + 1 }}</div>
                        <span class="text-sm {{ $i + 1 <= ($step ?? 1) ? 'text-indigo-600 font-medium' : 'text-gray-500' }}">{{ $stepName }}</span>
                    </div>
                    @if($i < 3)
                    <div class="flex-1 h-px bg-gray-200 mx-3"></div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Step 1: Upload -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Upload CSV File</h3>
            <form method="POST" action="{{ route('vendors.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CSV File</label>
                    <input type="file" name="csv_file" accept=".csv,.xlsx,.xls" required class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-gray-500 mt-1">Supported formats: CSV, XLSX, XLS. Max 5000 rows.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Expected columns (header row required):</p>
                    <code class="text-xs text-gray-500">brand_name, company_name, contact_name, email, website, category, notes</code>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Upload & Preview</button>
            </form>
        </div>

        <!-- Example CSV -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Example CSV Format</h3>
            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 overflow-x-auto">
                <pre class="text-xs text-gray-600 dark:text-gray-400">brand_name,company_name,contact_name,email,website,category,notes
Brand A,ABC Distribution,John Smith,john@example.com,https://example.com,Pet Supplies,Wholesale application required
Brand B,XYZ Inc,Sarah Lee,sarah@example.com,https://example2.com,Home Goods,Amazon authorization needed</pre>
            </div>
        </div>
    </div>
</x-app-layout>
