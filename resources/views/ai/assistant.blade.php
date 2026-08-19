<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">AI Assistant</h2>
        </div>
    </x-slot>

    @php
    $kimiService = app(\App\Services\AI\KimiService::class);
    $isConfigured = $kimiService->isConfigured();
    $vendors = \App\Models\Vendor::orderBy('brand_name')->limit(500)->get(['id','brand_name','contact_name']);
    $vendorId = request('vendor');
    $selectedVendor = $vendorId ? \App\Models\Vendor::find($vendorId) : null;
    $company = \App\Models\Company::where('is_active', true)->first();
    @endphp

    <div class="space-y-6">
        @if(!$isConfigured)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <p class="text-sm text-yellow-700 dark:text-yellow-400">⚠ Kimi AI is not configured. Please set the KIMI_API_KEY in <a href="{{ route('settings.ai') }}" class="underline">AI Configuration</a>.</p>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Vendor Selection & Options -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Generate Email</h3>
                <form method="POST" action="{{ route('ai-assistant') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Vendor</label>
                        <select name="vendor_id" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="">Choose a vendor...</option>
                            @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected((string)$vendorId === (string)$v->id)>{{ $v->brand_name }}{{ $v->contact_name ? ' - ' . $v->contact_name : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Objective</label>
                        <select name="objective" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                            <option value="Wholesale Authorization">Wholesale Authorization</option>
                            <option value="Reseller Authorization">Reseller Authorization</option>
                            <option value="Amazon Authorization">Amazon Authorization</option>
                            <option value="Distributor Pricing">Distributor Pricing</option>
                            <option value="Product Catalog Request">Product Catalog Request</option>
                            <option value="Dealer Application">Dealer Application</option>
                            <option value="MOQ and Pricing">MOQ and Pricing</option>
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom Instructions (optional)</label>
                        <textarea name="custom_instructions" rows="3" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm" placeholder="Any specific instructions for the AI..."></textarea>
                    </div>
                    <button type="submit" name="action" value="generate" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700" @disabled(!$isConfigured)>Generate Personalized Email</button>
                </form>

                @if($selectedVendor)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vendor Info</h4>
                    <div class="text-xs space-y-1 text-gray-500">
                        <div>Brand: {{ $selectedVendor->brand_name }}</div>
                        <div>Contact: {{ $selectedVendor->contact_name ?? '-' }}</div>
                        <div>Email: {{ $selectedVendor->contact_email ?? '-' }}</div>
                        <div>Category: {{ $selectedVendor->product_category ?? '-' }}</div>
                    </div>
                    <form method="POST" action="{{ route('ai-assistant') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="vendor_id" value="{{ $selectedVendor->id }}">
                        <button type="submit" name="action" value="research" class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700" @disabled(!$isConfigured)>Research Brand</button>
                    </form>
                </div>
                @endif
            </div>

            <!-- Right: Result -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Result</h3>

                @if(session('generated_email'))
                    @php $genEmail = is_object(session('generated_email')) ? session('generated_email') : \App\Models\GeneratedEmail::find(session('generated_email')['id'] ?? session('generated_email')); @endphp
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">Email Generated</span>
                            <a href="{{ route('emails.preview', $genEmail->id) }}" class="text-sm text-indigo-600 hover:underline">View & Edit →</a>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="text-sm font-semibold text-gray-800 dark:text-gray-300">{{ $genEmail->subject }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-3 whitespace-pre-wrap">{{ $genEmail->body }}</div>
                        </div>
                        @if($genEmail->personalization_notes)
                        <div class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-700/30 rounded p-3">
                            <strong>Personalization Notes:</strong> {{ $genEmail->personalization_notes }}
                        </div>
                        @endif
                        @if(!empty($genEmail->quality_checks['warnings']))
                        <div class="space-y-1">
                            @foreach($genEmail->quality_checks['warnings'] as $warning)
                            <div class="text-xs text-yellow-600">⚠ {{ $warning }}</div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                @elseif(session('research_data'))
                    <div class="space-y-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-700">Research Complete</span>
                        @php $research = session('research_data'); @endphp
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3 text-sm">
                            @if(!empty($research['summary']))
                            <div><strong class="text-gray-700 dark:text-gray-300">Summary:</strong> <span class="text-gray-600 dark:text-gray-400">{{ $research['summary'] }}</span></div>
                            @endif
                            @if(!empty($research['positioning']))
                            <div><strong class="text-gray-700 dark:text-gray-300">Positioning:</strong> <span class="text-gray-600 dark:text-gray-400">{{ $research['positioning'] }}</span></div>
                            @endif
                            @if(!empty($research['wholesale_info']))
                            <div><strong class="text-gray-700 dark:text-gray-300">Wholesale Info:</strong> <span class="text-gray-600 dark:text-gray-400">{{ $research['wholesale_info'] }}</span></div>
                            @endif
                            @if(!empty($research['suggested_approach']))
                            <div><strong class="text-gray-700 dark:text-gray-300">Suggested Approach:</strong> <span class="text-gray-600 dark:text-gray-400">{{ $research['suggested_approach'] }}</span></div>
                            @endif
                            @if(!empty($research['questions']))
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Questions to Ask:</strong>
                                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-1">
                                @foreach($research['questions'] as $q)<li>{{ $q }}</li>@endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                @elseif(session('error'))
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <p class="text-sm text-red-600">{{ session('error') }}</p>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        <p class="text-sm text-gray-500 mt-3">Select a vendor and generate a personalized email.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
