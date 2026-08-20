<x-app-layout>
    @php
    $categories = \App\Support\CategoryOptions::categories();
    $countries = \App\Support\CategoryOptions::countries();
    $sources = \App\Support\CategoryOptions::sources();
    $priorities = \App\Enums\Priority::options();
    @endphp

    <x-page-header title="Add Vendor" :back="route('vendors.index')" />

    <div class="max-w-4xl mx-auto">
        <form method="POST" action="{{ route('vendors.create') }}" class="space-y-4">
            @csrf

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Brand Name <span class="text-red-500">*</span></label>
                        <input type="text" name="brand_name" required value="{{ old('brand_name') }}" placeholder="Acme Pet Supplies" autofocus class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email') }}" placeholder="name@company.com" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+1 555-0100" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Website</label>
                        <input type="text" name="website" value="{{ old('website') }}" placeholder="https://..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Company Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Legal entity" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contact Name</label>
                        <input type="text" name="contact_name" value="{{ old('contact_name') }}" placeholder="John Doe" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Category</label>
                        <select name="product_category" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select...</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('product_category') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Country</label>
                        <select name="country" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select...</option>
                            @foreach($countries as $code => $name)
                            <option value="{{ $code }}" @selected(old('country') === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Priority</label>
                        <select name="priority" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($priorities as $val => $label)
                            <option value="{{ $val }}" @selected(old('priority', 'medium') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contact Source</label>
                        <select name="contact_source" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select...</option>
                            @foreach($sources as $val => $label)
                            <option value="{{ $val }}" @selected(old('contact_source') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Any additional context..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
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
