<x-app-layout>
    @php
    $categories = \App\Support\CategoryOptions::categories();
    $countries = \App\Support\CategoryOptions::countries();
    $sources = \App\Support\CategoryOptions::sources();
    $priorities = \App\Enums\Priority::options();
    @endphp

    <x-page-header title="Add Vendor" :back="route('vendors.index')" />

    <div class="max-w-2xl mx-auto">
        <form method="POST" action="{{ route('vendors.create') }}" class="space-y-4" x-data="{ showMore: false }">
            @csrf

            {{-- Essential fields only --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Brand Name <span class="text-red-500">*</span></label>
                        <input type="text" name="brand_name" required value="{{ old('brand_name') }}" placeholder="e.g. Acme Pet Supplies" autofocus class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email') }}" placeholder="name@company.com" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+1 555-0100" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Website / URL</label>
                        <input type="text" name="website" value="{{ old('website') }}" placeholder="https://..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>

            {{-- Collapsible extra details --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <button type="button" @click="showMore = !showMore" class="w-full flex items-center justify-between px-5 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 transition-transform" :class="showMore ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        More details (optional)
                    </span>
                </button>
                <div x-show="showMore" x-transition class="px-5 pb-5 space-y-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input name="company_name" label="Company Name" :value="old('company_name')" placeholder="Legal entity name" />
                        <x-input name="contact_name" label="Contact Name" :value="old('contact_name')" placeholder="Person to reach out to" />
                        <x-select name="product_category" label="Product Category" :value="old('product_category')" :options="$categories" placeholder="Select category" />
                        <x-select name="country" label="Country" :value="old('country')" :options="$countries" placeholder="Select country" />
                        <x-select name="priority" label="Priority" :value="old('priority', 'medium')" :options="$priorities" />
                        <x-select name="contact_source" label="Contact Source" :value="old('contact_source')" :options="$sources" placeholder="How did you find them?" />
                    </div>
                    <x-textarea name="notes" label="Internal Notes" :value="old('notes')" placeholder="Any additional context about this vendor..." :rows="2" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <x-button variant="secondary" href="{{ route('vendors.index') }}">Cancel</x-button>
                <button type="submit" name="add_another" value="1" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Save & Add Another
                </button>
                <x-button type="submit" variant="primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Create Vendor
                </x-button>
            </div>
        </form>
    </div>
</x-app-layout>
