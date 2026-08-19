<x-app-layout>
    @php
    $vendor = \App\Models\Vendor::findOrFail(request()->route('id'));
    $statusOptions = \App\Enums\VendorStatus::options();
    $emailStatusOptions = \App\Enums\EmailStatus::options();
    $priorityOptions = \App\Enums\Priority::options();
    $categories = \App\Support\CategoryOptions::categories();
    $countries = \App\Support\CategoryOptions::countries();
    $sources = \App\Support\CategoryOptions::sources();
    @endphp

    <x-page-header title="Edit Vendor" :back="route('vendors.show', $vendor->id)" />

    <div class="max-w-3xl mx-auto space-y-6">
        <form method="POST" action="{{ route('vendors.edit', $vendor->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-card title="Vendor Details">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="brand_name" label="Brand Name" required :value="$vendor->brand_name" />
                    <x-input name="company_name" label="Company Name" :value="$vendor->company_name" />
                    <x-input name="website" label="Website" :value="$vendor->website" placeholder="https://..." />
                    <x-input name="amazon_brand_store" label="Amazon Brand Store" :value="$vendor->amazon_brand_store" placeholder="Amazon store URL" />
                    <x-select name="product_category" label="Product Category" :value="$vendor->product_category" :options="$categories" placeholder="Select category" />
                    <x-select name="priority" label="Priority" :value="$vendor->priority" :options="$priorityOptions" />
                </div>
            </x-card>

            <x-card title="Contact Information">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="contact_name" label="Contact Name" :value="$vendor->contact_name" />
                    <x-input name="contact_email" type="email" label="Contact Email" :value="$vendor->contact_email" />
                    <x-input name="secondary_email" type="email" label="Secondary Email" :value="$vendor->secondary_email" />
                    <x-input name="phone" label="Phone" :value="$vendor->phone" />
                    <x-select name="contact_source" label="Contact Source" :value="$vendor->contact_source" :options="$sources" placeholder="How did you find them?" />
                </div>
            </x-card>

            <x-card title="Location">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-select name="country" label="Country" :value="$vendor->country" :options="$countries" placeholder="Select country" />
                    <x-input name="state" label="State" :value="$vendor->state" />
                    <x-input name="city" label="City" :value="$vendor->city" />
                </div>
            </x-card>

            <x-card title="Status & Follow-up">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-select name="status" label="Status" :value="$vendor->status" :options="$statusOptions" />
                    <x-select name="email_status" label="Email Status" :value="$vendor->email_status" :options="$emailStatusOptions" />
                    <x-input name="next_follow_up" type="date" label="Next Follow Up" :value="$vendor->next_follow_up?->format('Y-m-d')" />
                </div>
            </x-card>

            <x-card title="Notes">
                <x-textarea name="notes" label="Internal Notes" :value="$vendor->notes" :rows="3" />
            </x-card>

            <div class="flex items-center justify-between">
                <form method="POST" action="{{ route('vendors.destroy', $vendor->id) }}" onsubmit="return confirm('Archive this vendor?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Archive
                    </x-button>
                </form>
                <div class="flex items-center gap-3">
                    <x-button variant="secondary" href="{{ route('vendors.show', $vendor->id) }}">Cancel</x-button>
                    <x-button type="submit" variant="primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Update Vendor
                    </x-button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
