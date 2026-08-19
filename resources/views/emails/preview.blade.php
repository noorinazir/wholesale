<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="javascript:history.back()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Email Preview</h2>
        </div>
    </x-slot>

    @php
    $email = \App\Models\GeneratedEmail::with('vendor')->findOrFail(request()->route('id'));
    $sendingService = app(\App\Services\EmailSendingService::class);
    $duplicateSent = $sendingService->hasDuplicateSent($email->vendor_id, $email->campaign_id);
    $isSuppressed = $sendingService->isVendorSuppressed($email->vendor);
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left: Vendor Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Vendor Information</h3>
            <div class="space-y-3 text-sm">
                <div><span class="text-gray-500">Brand:</span> <span class="text-gray-800 dark:text-gray-300 font-medium">{{ $email->vendor?->brand_name }}</span></div>
                <div><span class="text-gray-500">Company:</span> <span class="text-gray-800 dark:text-gray-300">{{ $email->vendor?->company_name ?? '-' }}</span></div>
                <div><span class="text-gray-500">Contact:</span> <span class="text-gray-800 dark:text-gray-300">{{ $email->vendor?->contact_name ?? '-' }}</span></div>
                <div><span class="text-gray-500">Email:</span> <span class="text-gray-800 dark:text-gray-300">{{ $email->vendor?->contact_email ?? '-' }}</span></div>
                <div><span class="text-gray-500">Category:</span> <span class="text-gray-800 dark:text-gray-300">{{ $email->vendor?->product_category ?? '-' }}</span></div>
                <div><span class="text-gray-500">Tone:</span> <span class="text-gray-800 dark:text-gray-300">{{ ucfirst($email->tone) }}</span></div>
                <div><span class="text-gray-500">Objective:</span> <span class="text-gray-800 dark:text-gray-300">{{ $email->objective ?? '-' }}</span></div>
                <div><span class="text-gray-500">AI Model:</span> <span class="text-gray-800 dark:text-gray-300">{{ $email->ai_model ?? '-' }}</span></div>
            </div>

            @if($email->personalization_notes)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500">Personalization Notes</div>
                <div class="text-sm text-gray-800 dark:text-gray-300 mt-1">{{ $email->personalization_notes }}</div>
            </div>
            @endif

            @if($email->quality_checks && !empty($email->quality_checks['warnings']))
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500">Quality Checks</div>
                <div class="mt-2 space-y-1">
                    @foreach($email->quality_checks['warnings'] as $warning)
                    <div class="text-xs text-yellow-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {{ $warning }}
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($duplicateSent)
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <p class="text-sm text-red-600">⚠ This vendor has already been contacted for this campaign.</p>
            </div>
            @endif

            @if($isSuppressed)
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <p class="text-sm text-red-600">⚠ This vendor is on the suppression list / opted out.</p>
            </div>
            @endif
        </div>

        <!-- Right: Email Editor -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Email Content</h3>
                <span class="px-2 py-1 text-xs rounded-full {{ $email->status === 'approved' ? 'bg-green-100 text-green-700' : ($email->status === 'sent' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst($email->status) }}</span>
            </div>

            <form method="POST" action="{{ route('emails.preview', $email->id) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                    <input type="text" name="subject" value="{{ $email->subject }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Body</label>
                    <textarea name="body" rows="15" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 font-mono text-sm">{{ $email->body }}</textarea>
                </div>

                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" name="action" value="save" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700">Save</button>
                    @if($email->status === 'draft')
                    <button type="submit" name="action" value="approve" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700" @disabled($isSuppressed)>Approve</button>
                    <button type="submit" name="action" value="reject" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">Reject</button>
                    @endif
                    @if($email->status === 'approved')
                    <button type="submit" name="action" value="send" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700" @disabled($isSuppressed || $duplicateSent)>Send Now</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
