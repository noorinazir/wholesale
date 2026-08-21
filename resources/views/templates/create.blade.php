<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Create Template</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('templates.create') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Name</label>
                    <input type="text" name="name" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="Wholesale Account Request">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select name="type" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <option value="wholesale_inquiry">Wholesale Inquiry</option>
                        <option value="amazon_reseller">Amazon Reseller Authorization</option>
                        <option value="distributor_inquiry">Distributor Inquiry</option>
                        <option value="catalog_request">Product Catalog Request</option>
                        <option value="dealer_application">Dealer Application</option>
                        <option value="pricing_request">Pricing Request</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject Template</label>
                    <input type="text" name="subject_template" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="Wholesale Partnership Inquiry - {{ '{{brand_name}}' }}">
                    <p class="text-xs text-gray-500 mt-1">Variables: {{ '{{brand_name}}, {{contact_name}}, {{company_name}}, {{category}}, {{vendor_company}}, {{vendor_website}}, {{vendor_country}}' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Body Template</label>
                    <textarea name="body_template" rows="12" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 font-mono text-sm" placeholder="Dear {{ '{{contact_name}}' }},..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Available variables: {{ '{{contact_name}}, {{brand_name}}, {{category}}, {{company_name}}, {{website}}, {{contact_person}}, {{contact_email}}, {{phone}}, {{tax_id}}, {{ein}}, {{amazon_store}}, {{signature}}, {{vendor_company}}, {{vendor_website}}, {{vendor_country}}' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <input type="text" name="description" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" checked class="rounded border-gray-300">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Create Template</button>
            </form>
        </div>
    </div>
</x-app-layout>
