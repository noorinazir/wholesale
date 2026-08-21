<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('templates.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Create Template</h2>
        </div>
    </x-slot>

    @php
    $kimiService = app(\App\Services\AI\KimiService::class);
    $isConfigured = $kimiService->isConfigured();
    $aiTemplate = session('ai_template');
    $aiError = session('ai_error');
    @endphp

    <div class="max-w-4xl mx-auto space-y-6">
        @if($aiError)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-sm text-red-600">⚠ {{ $aiError }}</p>
        </div>
        @endif

        @if($aiTemplate)
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <p class="text-sm font-medium text-green-700 dark:text-green-400">AI template generated! Review and edit below, then click Create Template to save.</p>
            </div>
        </div>
        @endif

        <!-- AI Generation Panel -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">AI Template Generator</h3>
                @if(!$isConfigured)
                <span class="text-xs text-yellow-600 ml-auto">Not configured</span>
                @endif
            </div>
            <form method="POST" action="{{ route('templates.ai-generate') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Type</label>
                        <select name="type" id="ai_type" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="wholesale_inquiry">Wholesale Inquiry</option>
                            <option value="amazon_reseller">Amazon Reseller Authorization</option>
                            <option value="distributor_inquiry">Distributor Inquiry</option>
                            <option value="catalog_request">Product Catalog Request</option>
                            <option value="dealer_application">Dealer Application</option>
                            <option value="pricing_request">Pricing Request</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tone</label>
                        <select name="tone" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="professional">Professional</option>
                            <option value="friendly">Friendly</option>
                            <option value="concise">Concise</option>
                            <option value="formal">Formal</option>
                            <option value="relationship_focused">Relationship-focused</option>
                            <option value="direct">Direct</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom Instructions (optional)</label>
                    <textarea name="custom_instructions" rows="2" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm" placeholder="e.g. Emphasize our Amazon expertise, mention we're a US-based company..."></textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 @disabled(!$isConfigured)" @disabled(!$isConfigured)>
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Generate with AI
                </button>
                @if(!$isConfigured)
                <p class="text-xs text-gray-500">Configure Kimi AI in <a href="{{ route('settings.ai') }}" class="underline">AI Configuration</a> to use this feature.</p>
                @endif
            </form>
        </div>

        <!-- Template Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Template Details</h3>
            <form method="POST" action="{{ route('templates.create') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Name</label>
                    <input type="text" name="name" required value="{{ $aiTemplate['name'] ?? old('name') }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="Wholesale Account Request">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select name="type" id="form_type" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <option value="wholesale_inquiry" @selected(old('type') === 'wholesale_inquiry')>Wholesale Inquiry</option>
                        <option value="amazon_reseller" @selected(old('type') === 'amazon_reseller')>Amazon Reseller Authorization</option>
                        <option value="distributor_inquiry" @selected(old('type') === 'distributor_inquiry')>Distributor Inquiry</option>
                        <option value="catalog_request" @selected(old('type') === 'catalog_request')>Product Catalog Request</option>
                        <option value="dealer_application" @selected(old('type') === 'dealer_application')>Dealer Application</option>
                        <option value="pricing_request" @selected(old('type') === 'pricing_request')>Pricing Request</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject Template</label>
                    <input type="text" name="subject_template" required value="{{ $aiTemplate['subject_template'] ?? old('subject_template') }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="Wholesale Partnership Inquiry">
                    @verbatim
                    <p class="text-xs text-gray-500 mt-1">Variables: {{brand_name}}, {{contact_name}}, {{company_name}}, {{category}}, {{vendor_company}}, {{vendor_website}}, {{vendor_country}}</p>
                    @endverbatim
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Body Template</label>
                    <textarea name="body_template" rows="14" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 font-mono text-sm" placeholder="Dear {{contact_name}},...">{{ $aiTemplate['body_template'] ?? old('body_template') }}</textarea>
                    @verbatim
                    <p class="text-xs text-gray-500 mt-1">Available variables: {{contact_name}}, {{brand_name}}, {{category}}, {{company_name}}, {{website}}, {{contact_person}}, {{contact_email}}, {{phone}}, {{tax_id}}, {{ein}}, {{amazon_store}}, {{signature}}, {{vendor_company}}, {{vendor_website}}, {{vendor_country}}</p>
                    @endverbatim
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <input type="text" name="description" value="{{ $aiTemplate['description'] ?? old('description') }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" checked class="rounded border-gray-300">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Create Template</button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('ai_type').addEventListener('change', function() {
        document.getElementById('form_type').value = this.value;
    });
    </script>
</x-app-layout>
