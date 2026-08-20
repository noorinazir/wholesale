<x-app-layout>
    @php
    $categories = \App\Support\CategoryOptions::categories();
    $countries = \App\Support\CategoryOptions::countries();
    $sources = \App\Support\CategoryOptions::sources();
    $priorities = \App\Enums\Priority::options();
    @endphp

    <x-page-header title="Add Vendor" :back="route('vendors.index')" />

    <div class="max-w-3xl mx-auto space-y-6">
        <form method="POST" action="{{ route('vendors.create') }}" class="space-y-6">
            @csrf

            <x-card title="Vendor Details">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="brand_name" label="Brand Name" required placeholder="e.g. Acme Pet Supplies" />
                    <x-input name="company_name" label="Company Name" placeholder="Legal entity name" />
                    <x-input name="website" label="Website" placeholder="https://..." />
                    <x-select name="product_category" label="Product Category" :value="old('product_category')" :options="$categories" placeholder="Select category" />
                    <x-select name="country" label="Country" :value="old('country')" :options="$countries" placeholder="Select country" />
                    <x-select name="priority" label="Priority" :value="old('priority', 'medium')" :options="$priorities" />
                </div>
            </x-card>

            <x-card title="Contact Information">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="contact_name" label="Contact Name" placeholder="Person to reach out to" />
                    <x-input name="contact_email" type="email" label="Contact Email" placeholder="name@company.com" />
                    <x-input name="phone" label="Phone" placeholder="+1..." />
                    <x-select name="contact_source" label="Contact Source" :value="old('contact_source')" :options="$sources" placeholder="How did you find them?" />
                </div>
            </x-card>

            <x-card title="Notes">
                <x-textarea name="notes" label="Internal Notes" placeholder="Any additional context about this vendor..." :rows="3" />
            </x-card>

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
