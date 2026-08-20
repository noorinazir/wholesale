<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Company Profile</h2>
    </x-slot>

    @php
    $company = $companyProfile ?? new \App\Models\Company();
    $documents = $companyDocuments ?? collect();
    $docTypes = [
        'resell_tax_id' => 'Resale Tax ID',
        'ein' => 'EIN Document',
        'business_license' => 'Business License',
        'other' => 'Other Document',
    ];
    @endphp

    <div class="max-w-4xl mx-auto">
        <x-settings-tabs active="company" />
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('settings.company') }}" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $company->company_name) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Legal Company Name</label>
                        <input type="text" name="legal_company_name" value="{{ old('legal_company_name', $company->legal_company_name) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resale Tax ID</label>
                        <input type="text" name="resell_tax_id" value="{{ old('resell_tax_id', $company->resell_tax_id) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="e.g. 123456789">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">EIN (Employer Identification Number)</label>
                        <input type="text" name="ein" value="{{ old('ein', $company->ein) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="e.g. 12-3456789">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                        <input type="text" name="website" value="{{ old('website', $company->website) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $company->contact_person) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $company->contact_email) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Business Description</label>
                    <textarea name="business_description" rows="3" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">{{ old('business_description', $company->business_description) }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                        <input type="text" name="country" value="{{ old('country', $company->country) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State/Province</label>
                        <input type="text" name="state_province" value="{{ old('state_province', $company->state_province) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
                        <input type="text" name="city" value="{{ old('city', $company->city) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Business Address</label>
                    <input type="text" name="business_address" value="{{ old('business_address', $company->business_address) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amazon Store URL</label>
                        <input type="text" name="amazon_store_url" value="{{ old('amazon_store_url', $company->amazon_store_url) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amazon Marketplace</label>
                        <input type="text" name="amazon_marketplace" value="{{ old('amazon_marketplace', $company->amazon_marketplace) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="Amazon US">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Years in Business</label>
                        <input type="number" name="years_in_business" value="{{ old('years_in_business', $company->years_in_business) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Business Model</label>
                        <input type="text" name="business_model" value="{{ old('business_model', $company->business_model) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="E-commerce / Amazon Reseller">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Categories</label>
                        <textarea name="product_categories" rows="2" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">{{ old('product_categories', $company->product_categories) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brands Represented</label>
                        <textarea name="brands_represented" rows="2" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">{{ old('brands_represented', $company->brands_represented) }}</textarea>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sales Channels</label>
                        <input type="text" name="sales_channels" value="{{ old('sales_channels', $company->sales_channels) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="Amazon, Walmart, Shopify">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Brands</label>
                        <input type="text" name="target_brands" value="{{ old('target_brands', $company->target_brands) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Est. Annual Purchasing Volume ($)</label>
                        <input type="number" step="0.01" name="estimated_annual_purchasing_volume" value="{{ old('estimated_annual_purchasing_volume', $company->estimated_annual_purchasing_volume) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Est. Monthly Purchasing Volume ($)</label>
                        <input type="number" step="0.01" name="estimated_monthly_purchasing_volume" value="{{ old('estimated_monthly_purchasing_volume', $company->estimated_monthly_purchasing_volume) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Additional Information</label>
                    <textarea name="additional_information" rows="3" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">{{ old('additional_information', $company->additional_information) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom Notes</label>
                    <textarea name="custom_notes" rows="2" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">{{ old('custom_notes', $company->custom_notes) }}</textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save Company Profile</button>
            </form>
        </div>

        <!-- Documents Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Business Documents</h3>
            <p class="text-sm text-gray-500 mb-4">Upload your Resale Tax ID, EIN, Business License, and other documents. These will be available when vendors request them during outreach.</p>

            <!-- Upload Form -->
            <form method="POST" action="{{ route('settings.company.upload-document') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 mb-6">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document Type</label>
                    <select name="type" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @foreach($docTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File (PDF, JPG, PNG, DOC - max 10MB)</label>
                    <input type="file" name="document" required class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Upload</button>
            </form>

            <!-- Uploaded Documents -->
            @if($documents->isNotEmpty())
            <div class="space-y-2">
                @foreach($documents as $doc)
                <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $docTypes[$doc->type] ?? ucfirst(str_replace('_', ' ', $doc->type)) }}</div>
                            <div class="text-xs text-gray-500">{{ $doc->original_name }} · {{ number_format($doc->file_size / 1024, 1) }} KB</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ $doc->url }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">View</a>
                        <form method="POST" action="{{ route('settings.company.delete-document', $doc->id) }}" onsubmit="return confirm('Delete this document?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Delete</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-6 text-sm text-gray-400">
                No documents uploaded yet. Upload your Resale Tax ID, EIN, or Business License to speed up vendor approvals.
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
