<x-app-layout>
    @php
    $statusOptions = \App\Enums\VendorStatus::options();
    $priorityOptions = \App\Enums\Priority::options();
    $statusColors = \App\Enums\VendorStatus::colors();
    $priorityColors = \App\Enums\Priority::colors();
    $emailStatusColors = \App\Enums\EmailStatus::colors();
    $categories = \App\Support\CategoryOptions::categories();
    $approvalStatuses = \App\Enums\ApprovalStatus::options();
    @endphp

    <div x-data="{ tab: 'overview' }">
    <x-page-header title="{{ $vendor->brand_name }}" :back="route('vendors.index')">
        <x-button variant="secondary" href="{{ route('vendors.edit', $vendor->id) }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </x-button>
    </x-page-header>

    <div class="space-y-6">
        <!-- Vendor Info -->
        <x-card>
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $vendor->brand_name }}</h3>
                    <p class="text-sm text-gray-500">{{ $vendor->company_name ?? '' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('vendors.priority', $vendor->id) }}">
                        @csrf
                        <select name="priority" onchange="this.form.submit()" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer py-1">
                            @foreach($priorityOptions as $val => $label)
                            <option value="{{ $val }}" @selected($vendor->priority === $val)>{{ $label }} Priority</option>
                            @endforeach
                        </select>
                    </form>
                    <form method="POST" action="{{ route('vendors.status', $vendor->id) }}">
                        @csrf
                        <select name="status" onchange="this.form.submit()" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer py-1">
                            @foreach($statusOptions as $val => $label)
                            <option value="{{ $val }}" @selected($vendor->status === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span class="text-gray-500">Contact:</span>
                        <span class="text-gray-800 dark:text-gray-300 font-medium">{{ $vendor->contact_name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-gray-500">Email:</span>
                        <span class="text-gray-800 dark:text-gray-300 font-medium">{{ $vendor->contact_email ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span class="text-gray-500">Phone:</span>
                        <span class="text-gray-800 dark:text-gray-300 font-medium">{{ $vendor->phone ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-3 9a9 9 0 019-9"/></svg>
                        <span class="text-gray-500">Website:</span>
                        @if($vendor->website)
                        <a href="{{ $vendor->website }}" target="_blank" class="text-indigo-600 hover:underline font-medium">{{ $vendor->website }}</a>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5"/></svg>
                        <span class="text-gray-500">Category:</span>
                        <span class="text-gray-800 dark:text-gray-300 font-medium">{{ $vendor->product_category ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-gray-500">Location:</span>
                        <span class="text-gray-800 dark:text-gray-300 font-medium">{{ collect([$vendor->city, $vendor->state, $vendor->country])->filter()->join(', ') ?: '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-gray-500">Last Contacted:</span>
                        <span class="text-gray-800 dark:text-gray-300 font-medium">{{ $vendor->last_contacted_at?->format('M d, Y') ?? 'Never' }}</span>
                    </div>
                    @if($vendor->next_follow_up)
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-orange-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-gray-500">Next Follow Up:</span>
                        <span class="text-orange-600 font-medium">{{ $vendor->next_follow_up->format('M d, Y') }}</span>
                    </div>
                    @endif
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span class="text-gray-500">Source:</span>
                        <span class="text-gray-800 dark:text-gray-300 font-medium">{{ $vendor->contact_source ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M9 17V9m4 8V5m4 12v-8"/></svg>
                        <span class="text-gray-500">Amazon Store:</span>
                        <span class="text-gray-800 dark:text-gray-300 font-medium">{{ $vendor->amazon_brand_store ?? '-' }}</span>
                    </div>
                </div>
            </div>

            @if($vendor->notes)
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Notes</div>
                <div class="text-sm text-gray-700 dark:text-gray-300">{{ $vendor->notes }}</div>
            </div>
            @endif

            @if($vendor->research_summary)
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Research Summary</div>
                <div class="text-sm text-gray-700 dark:text-gray-300">{{ $vendor->research_summary }}</div>
            </div>
            @endif

            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                <span class="text-xs text-gray-400">Email Status:</span>
                <x-badge :color="$emailStatusColors[$vendor->email_status] ?? 'gray'">{{ ucfirst(str_replace('_', ' ', $vendor->email_status)) }}</x-badge>
                <span class="text-xs text-gray-400">Last Contacted:</span>
                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $vendor->last_contacted_at?->format('M d, Y') ?? 'Never' }}</span>
            </div>
        </x-card>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-2">
            @if($kimiService->isConfigured())
            <form method="POST" action="{{ route('vendors.show', $vendor->id) }}">
                @csrf
                <x-button type="submit" variant="secondary" size="sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Research
                </x-button>
                <input type="hidden" name="action" value="research">
            </form>
            @endif
            <x-button variant="primary" size="sm" href="{{ route('ai-assistant') }}?vendor={{ $vendor->id }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                Generate Email
            </x-button>
            <div class="flex items-center gap-1 ml-auto">
                <span class="text-xs text-gray-400 mr-1">Quick:</span>
                <form method="POST" action="{{ route('vendors.status', $vendor->id) }}" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="contacted">
                    <button type="submit" class="px-2.5 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-md transition-colors">Mark Contacted</button>
                </form>
                <form method="POST" action="{{ route('vendors.status', $vendor->id) }}" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="interested">
                    <button type="submit" class="px-2.5 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 dark:text-green-400 dark:bg-green-900/30 dark:hover:bg-green-900/50 rounded-md transition-colors">Interested</button>
                </form>
                <form method="POST" action="{{ route('vendors.status', $vendor->id) }}" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="not_interested">
                    <button type="submit" class="px-2.5 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-400 dark:bg-red-900/30 dark:hover:bg-red-900/50 rounded-md transition-colors">Not Interested</button>
                </form>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex gap-1">
                <button @click="tab = 'overview'" :class="tab === 'overview' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors">
                    Overview
                    @if($emailReplies->isNotEmpty())<span class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">{{ $emailReplies->where('is_read', false)->count() }}</span>@endif
                </button>
                <button @click="tab = 'products'" :class="tab === 'products' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors">
                    Products
                    @if($products->isNotEmpty())<span class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $products->count() }}</span>@endif
                </button>
                <button @click="tab = 'emails'" :class="tab === 'emails' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors">
                    Emails
                </button>
                <button @click="tab = 'approval'" :class="tab === 'approval' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors">
                    Brand Approval
                </button>
            </nav>
        </div>

        <!-- Overview Tab -->
        <div x-show="tab === 'overview'" x-transition class="space-y-6">
        <!-- Email Replies -->
        @if($emailReplies->isNotEmpty())
        <x-card title="Replies from Vendor">
            <div class="space-y-3">
                @foreach($emailReplies as $reply)
                <div class="border rounded-lg p-4 {{ $reply->is_read ? 'border-gray-200 dark:border-gray-700' : 'border-purple-200 dark:border-purple-800 bg-purple-50/50 dark:bg-purple-900/10' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="text-sm font-medium text-gray-800 dark:text-gray-300">{{ $reply->subject }}</div>
                            @if(!$reply->is_read)
                            <span class="px-1.5 py-0.5 text-xs font-medium text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 rounded-full">New</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500">{{ $reply->received_at->format('M d, Y H:i') }}</div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">From: {{ $reply->from_name ?? $reply->from_email }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-2 max-h-32 overflow-y-auto">{{ $reply->body_text }}</div>
                    <div class="mt-3 flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @if(!$reply->is_read)
                        <form method="POST" action="{{ route('inbox.replies.read', $reply->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs text-indigo-600 hover:underline font-medium">Mark as Read</button>
                        </form>
                        @endif
                        <span class="text-xs text-gray-400">Set status:</span>
                        <form method="POST" action="{{ route('inbox.replies.status', $reply->id) }}" class="inline">
                            @csrf
                            <select name="status" onchange="this.form.submit()" class="text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 cursor-pointer py-0.5">
                                <option value="">Choose...</option>
                                <option value="interested">Interested</option>
                                <option value="not_interested">Not Interested</option>
                                <option value="follow_up_required">Follow Up Required</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="contacted">Contacted</option>
                            </select>
                        </form>
                        <form method="POST" action="{{ route('vendors.generate-document-response', $vendor->id) }}" class="inline">
                            @csrf
                            <input type="hidden" name="reply_id" value="{{ $reply->id }}">
                            <button type="submit" class="px-2.5 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-md transition-colors flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Generate Document Response
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>
        @endif

        <!-- Generated Emails -->
        <x-card title="Generated Emails">
            @if($generatedEmails->isNotEmpty())
            <div class="space-y-3">
                @foreach($generatedEmails as $email)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-indigo-200 dark:hover:border-indigo-700 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-800 dark:text-gray-300">{{ $email->subject }}</div>
                        <x-badge :color="$email->status === 'approved' ? 'green' : ($email->status === 'sent' ? 'blue' : 'gray')">{{ ucfirst($email->status) }}</x-badge>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ $email->created_at->format('M d, Y H:i') }} · {{ $email->tone }} · {{ $email->ai_model }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">{{ $email->body }}</div>
                    <div class="mt-3">
                        <a href="{{ route('emails.preview', $email->id) }}" class="text-xs text-indigo-600 hover:underline font-medium">Preview →</a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <x-empty-state icon="document" title="No emails generated" description="Use the AI Assistant to generate personalized emails for this vendor." actionText="Generate Email" actionHref="{{ route('ai-assistant') }}?vendor={{ $vendor->id }}" />
            @endif
        </x-card>
        </div><!-- /Overview Tab -->

        <!-- Product Calculator (defined once for all modals) -->
        <script>
            function productCalculator(initial) {
                initial = initial || {};
                return {
                    showAdvanced: false,
                    asin: initial.asin || '',
                    asinWarning: '',
                    buyingPrice: initial.buyingPrice || 0,
                    fbaFee: initial.fbaFee || 0,
                    shippingCost: initial.shippingCost || 0,
                    labelingCost: initial.labelingCost || 0,
                    otherCosts: initial.otherCosts || 0,
                    operationCost: initial.operationCost || 0,
                    sellPrice: initial.sellPrice || 0,
                    referralPercent: initial.referralPercent || 15.00,
                    existingAsins: initial.existingAsins || {},
                    get totalCost() {
                        const referral = (this.sellPrice || 0) * (this.referralPercent || 0) / 100;
                        return (this.buyingPrice || 0) + (this.fbaFee || 0) + referral + (this.shippingCost || 0) +
                               (this.labelingCost || 0) + (this.otherCosts || 0) + (this.operationCost || 0);
                    },
                    get referralFee() {
                        return (this.sellPrice || 0) * (this.referralPercent || 0) / 100;
                    },
                    get netProfit() {
                        return (this.sellPrice || 0) - this.totalCost;
                    },
                    get marginPercent() {
                        return (this.sellPrice || 0) > 0 ? (this.netProfit / (this.sellPrice || 0)) * 100 : 0;
                    },
                    get roiPercent() {
                        return (this.buyingPrice || 0) > 0 ? (this.netProfit / (this.buyingPrice || 0)) * 100 : 0;
                    },
                    checkAsin() {
                        if (this.asin && this.existingAsins[this.asin]) {
                            this.asinWarning = 'Warning: ASIN ' + this.asin + ' already exists as "' + this.existingAsins[this.asin] + '"';
                        } else {
                            this.asinWarning = '';
                        }
                    }
                }
            }
        </script>

        <!-- Products Tab -->
        <div x-show="tab === 'products'" x-transition class="space-y-6">
        <!-- Products & Margin Analysis -->
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Products & Margin Analysis</h3>
                <div class="flex items-center gap-2">
                    @if($products->isNotEmpty())
                    <a href="{{ route('products.export', $vendor->id) }}" class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export
                    </a>
                    @endif
                    <button @click="$dispatch('open-product-modal')" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Product
                    </button>
                </div>
            </div>

            @if($products->isNotEmpty())
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Buy Price</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Amazon Fee</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Shipping</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Label</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Other</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Op Cost</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Total Cost</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Sell Price</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Net Profit</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Margin</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">ROI</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 uppercase">Sellers</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 uppercase">Buy Box</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($products as $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2">
                                <div class="font-medium text-gray-800 dark:text-gray-300">{{ $product->product_name }}</div>
                                <div class="text-gray-400">{{ $product->asin ?? '' }} · {{ $product->product_category ?? '' }}</div>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">${{ number_format($product->buying_price, 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">${{ number_format($product->amazon_fee, 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">${{ number_format($product->shipping_cost, 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">${{ number_format($product->labeling_cost, 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">${{ number_format($product->other_costs, 2) }}</td>
                            <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">${{ number_format($product->operation_cost, 2) }}</td>
                            <td class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-300">${{ number_format($product->total_cost, 2) }}</td>
                            <td class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-300">${{ number_format($product->amazon_sell_price, 2) }}</td>
                            <td class="px-3 py-2 text-right font-bold {{ $product->is_profitable ? 'text-green-600' : 'text-red-600' }}">${{ number_format($product->net_profit, 2) }}</td>
                            <td class="px-3 py-2 text-right">
                                <span class="px-1.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $product->margin_color === 'green' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                    {{ $product->margin_color === 'blue' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    {{ $product->margin_color === 'yellow' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                    {{ $product->margin_color === 'red' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                ">{{ number_format($product->margin_percent, 1) }}%</span>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($product->roi_percent, 1) }}%</td>
                            <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-400">{{ $product->number_of_sellers }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($product->buy_box_type === 'fba')
                                <span class="px-1.5 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded-full">FBA</span>
                                @elseif($product->buy_box_type === 'fbm')
                                <span class="px-1.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">FBM</span>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="$dispatch('open-edit-product-{{ $product->id }}')" class="p-1 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('products.destroy', $product->id) }}" class="inline" onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30" title="Delete">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div><span class="text-gray-500">Total Products:</span> <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $products->count() }}</span></div>
                <div><span class="text-gray-500">Profitable:</span> <span class="font-semibold text-green-600">{{ $products->filter(fn($p) => $p->is_profitable)->count() }}</span></div>
                <div><span class="text-gray-500">Avg Margin:</span> <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($products->avg('margin_percent'), 1) }}%</span></div>
                <div><span class="text-gray-500">Avg Profit:</span> <span class="font-semibold text-gray-800 dark:text-gray-200">${{ number_format($products->avg('net_profit'), 2) }}</span></div>
            </div>

            <!-- Edit Product Modals (outside table for valid HTML) -->
            @foreach($products as $product)
            <div x-data="{ show: false }" @open-edit-product-{{ $product->id }}.window="show = true" @keydown.escape.window="show = false" x-cloak>
                <div x-show="show" x-transition.opacity class="fixed inset-0 z-50 bg-black/50" @click="show = false"></div>
                <div x-show="show" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="show = false">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center justify-end px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                            <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('products.update', $product->id) }}" class="p-4 space-y-3">
                            @csrf
                            @method('PUT')
                            @include('vendors._product_form', ['product' => $product, 'categories' => $categories])
                            <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-gray-700">
                                <button type="button" @click="show = false" class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                                <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Update Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach

            @else
            <x-empty-state icon="document" title="No products yet" description="Add products to track buying price, FBA fees, margins, and profitability for this vendor." />
            @endif
        </x-card>

        <!-- Add Product Modal -->
        <div x-data="{ show: false }" @open-product-modal.window="show = true" @keydown.escape.window="show = false" x-cloak>
            <div x-show="show" x-transition.opacity class="fixed inset-0 z-50 bg-black/50" @click="show = false"></div>
            <div x-show="show" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="show = false">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-end px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                        <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('products.create', $vendor->id) }}" class="p-4 space-y-3">
                        @csrf
                        @include('vendors._product_form', ['product' => null, 'categories' => $categories, 'hideScript' => true])
                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="show = false" class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                            <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Add Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div><!-- /Products Tab -->

        <!-- Approval Tab -->
        <div x-show="tab === 'approval'" x-transition class="space-y-6">
        <!-- Brand Approval -->
        <x-card>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Brand Approval</h3>
            <form method="POST" action="{{ route('brand-approval.save', $vendor->id) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Approval Status</label>
                        <select name="approval_status" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500">
                            @foreach($approvalStatuses as $val => $label)
                            <option value="{{ $val }}" @selected($brandApproval?->approval_status === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Submitted Date</label>
                        <input type="date" name="submitted_at" value="{{ $brandApproval?->submitted_at?->format('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Approved Date</label>
                        <input type="date" name="approved_at" value="{{ $brandApproval?->approved_at?->format('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Expires Date</label>
                        <input type="date" name="expires_at" value="{{ $brandApproval?->expires_at?->format('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Minimum Order Qty</label>
                        <input type="number" step="0.01" name="minimum_order_qty" value="{{ $brandApproval?->minimum_order_qty }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Payment Terms</label>
                        <input type="text" name="payment_terms" value="{{ $brandApproval?->payment_terms }}" placeholder="Net 30" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Lead Time (days)</label>
                        <input type="number" name="lead_time_days" value="{{ $brandApproval?->lead_time_days }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pricing Tier</label>
                        <input type="text" name="pricing_tier" value="{{ $brandApproval?->pricing_tier }}" placeholder="Tier 1 / Wholesale" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Discount %</label>
                        <input type="number" step="0.01" name="discount_percent" value="{{ $brandApproval?->discount_percent }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ $brandApproval?->contact_person }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Approval Document URL</label>
                        <input type="text" name="approval_document_url" value="{{ $brandApproval?->approval_document_url }}" placeholder="https://..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Approved Categories (comma-separated)</label>
                        <input type="text" name="approved_categories[]" value="{{ $brandApproval?->approved_categories ? implode(', ', $brandApproval->approved_categories) : '' }}" placeholder="Pet Supplies, Home & Kitchen" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Exclusive Territories (comma-separated)</label>
                        <input type="text" name="exclusive_territories[]" value="{{ $brandApproval?->exclusive_territories ? implode(', ', $brandApproval->exclusive_territories) : '' }}" placeholder="USA, Canada" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                    </div>
                </div>

                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="requires_exclusivity" {{ $brandApproval?->requires_exclusivity ? 'checked' : '' }} class="rounded border-gray-300">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Requires Exclusivity</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="requires_map_policy" {{ $brandApproval?->requires_map_policy ? 'checked' : '' }} class="rounded border-gray-300">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Requires MAP Policy</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="requires_brand_registry" {{ $brandApproval?->requires_brand_registry ? 'checked' : '' }} class="rounded border-gray-300">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Requires Brand Registry</span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Requirements Notes</label>
                    <textarea name="requirements_notes" rows="2" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">{{ $brandApproval?->requirements_notes }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">{{ $brandApproval?->notes }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save Brand Approval</button>
                </div>
            </form>
        </x-card>
        </div><!-- /Approval Tab -->

        <!-- Emails Tab -->
        <div x-show="tab === 'emails'" x-transition class="space-y-6">
        <!-- Email History -->
        <x-card title="Email History">
            @if($emailLogs->isNotEmpty())
            <div class="space-y-3">
                @foreach($emailLogs as $log)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-800 dark:text-gray-300">{{ $log->subject }}</div>
                        <x-badge :color="$log->status === 'sent' ? 'green' : ($log->status === 'failed' ? 'red' : 'gray')">{{ ucfirst($log->status) }}</x-badge>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">To: {{ $log->recipient }} · {{ $log->sent_at?->format('M d, Y H:i') ?? $log->created_at?->format('M d, Y H:i') }}</div>
                    @if($log->error)
                    <div class="text-xs text-red-500 mt-1">Error: {{ $log->error }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <x-empty-state icon="mail" title="No email history" description="Sent emails and their delivery status will appear here." />
            @endif
        </x-card>
        </div><!-- /Emails Tab -->
    </div>
    </div><!-- /x-data tab wrapper -->
</x-app-layout>
