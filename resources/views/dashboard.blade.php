<x-app-layout>
    @php
    $sendingService = $sendingService ?? app(\App\Services\EmailSendingService::class);
    $stats = $stats ?? [];
    $funnel = $funnel ?? [];
    $products = $products ?? ['total' => 0, 'profitable' => 0, 'avg_margin' => 0];
    $recentActivity = $recentActivity ?? collect();
    $suggestedVendors = $suggestedVendors ?? collect();
    $followupsScheduled = $followupsScheduled ?? 0;
    $aiStats = $aiStats ?? [];
    $vendorBreakdown = $vendorBreakdown ?? [];
    $emailChartData = $emailChartData ?? ['labels' => [], 'values' => []];
    $vendorStatusData = $vendorStatusData ?? ['labels' => [], 'values' => []];
    @endphp

    <x-page-header title="Dashboard">
        <div class="flex items-center gap-2">
            <a href="{{ route('finance.dashboard') }}" class="px-3.5 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                Finance Dashboard
            </a>
            <a href="{{ route('vendors.create') }}" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Vendor
            </a>
        </div>
    </x-page-header>

    <div class="space-y-4">
        <!-- KPI Cards Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Total Vendors -->
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-8 -mt-8"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-indigo-100">Total Vendors</div>
                        <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div class="text-2xl font-bold">{{ $stats['total_vendors'] ?? 0 }}</div>
                    <div class="text-xs text-indigo-200 mt-1">{{ $stats['active_vendors'] ?? 0 }} active · {{ $vendorBreakdown['not_contacted'] ?? 0 }} not contacted</div>
                </div>
            </div>
            <!-- Emails Sent -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-8 -mt-8"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-blue-100">Emails Sent</div>
                        <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="text-2xl font-bold">{{ $stats['emails_sent'] ?? 0 }}</div>
                    <div class="text-xs text-blue-200 mt-1">{{ $stats['pending'] ?? 0 }} pending · {{ $stats['failed'] ?? 0 }} failed</div>
                </div>
            </div>
            <!-- Reply Rate -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-8 -mt-8"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-green-100">Reply Rate</div>
                        <svg class="w-4 h-4 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div class="text-2xl font-bold">{{ $funnel['reply_rate'] ?? 0 }}%</div>
                    <div class="text-xs text-green-200 mt-1">{{ $funnel['replied'] ?? 0 }} replies / {{ $funnel['contacted'] ?? 0 }} contacted</div>
                </div>
            </div>
            <!-- Follow-up Due -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-8 -mt-8"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-orange-100">Follow-ups Due</div>
                        <svg class="w-4 h-4 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="text-2xl font-bold">{{ $stats['followup_due'] ?? 0 }}</div>
                    <div class="text-xs text-orange-200 mt-1">{{ $followupsScheduled }} scheduled total</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('vendors.import') }}" class="group flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">Import Vendors</div>
                    <div class="text-xs text-gray-500">Bulk CSV upload</div>
                </div>
            </a>
            <a href="{{ route('vendors.create') }}" class="group flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-700 transition-colors">
                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">Add Vendor</div>
                    <div class="text-xs text-gray-500">Single entry</div>
                </div>
            </a>
            <a href="{{ route('campaigns.create') }}" class="group flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700 transition-colors">
                <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">New Campaign</div>
                    <div class="text-xs text-gray-500">Start outreach</div>
                </div>
            </a>
            <a href="{{ route('ai-assistant') }}" class="group flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-yellow-300 dark:hover:border-yellow-700 transition-colors">
                <div class="w-10 h-10 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">AI Assistant</div>
                    <div class="text-xs text-gray-500">Generate emails</div>
                </div>
            </a>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Left: Charts + Funnel -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Charts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Emails Sent (30 days)</h3>
                        <canvas id="emailsChart" height="160"></canvas>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Vendors by Status</h3>
                        <canvas id="vendorStatusChart" height="160"></canvas>
                    </div>
                </div>

                <!-- Outreach Funnel -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Outreach Funnel</h3>
                    <div class="space-y-3">
                        @php $maxCount = max($funnel['total'] ?? 1, 1); @endphp
                        @php
                        $funnelSteps = [
                            ['label' => 'Total Vendors', 'key' => 'total', 'color' => 'bg-indigo-500', 'textColor' => 'text-gray-800 dark:text-gray-200'],
                            ['label' => 'Contacted', 'key' => 'contacted', 'color' => 'bg-blue-500', 'textColor' => 'text-gray-800 dark:text-gray-200'],
                            ['label' => 'Replied', 'key' => 'replied', 'color' => 'bg-purple-500', 'textColor' => 'text-gray-800 dark:text-gray-200'],
                            ['label' => 'Interested', 'key' => 'interested', 'color' => 'bg-yellow-500', 'textColor' => 'text-gray-800 dark:text-gray-200'],
                            ['label' => 'Approved', 'key' => 'approved', 'color' => 'bg-green-500', 'textColor' => 'text-green-600'],
                        ];
                        @endphp
                        @foreach($funnelSteps as $step)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $step['label'] }}</span>
                                <span class="font-semibold {{ $step['textColor'] }}">{{ $funnel[$step['key']] ?? 0 }}</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden">
                                <div class="{{ $step['color'] }} h-5 rounded-full transition-all" style="width: {{ ($funnel[$step['key']] ?? 0) > 0 ? max(($funnel[$step['key']] / $maxCount) * 100, 3) : 0 }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Suggested Vendors + Recent Activity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Suggested Vendors -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Suggested Vendors</h3>
                            <a href="{{ route('vendors.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all →</a>
                        </div>
                        @if($suggestedVendors->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($suggestedVendors as $vendor)
                            <a href="{{ route('vendors.show', $vendor->id) }}" class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ strtoupper(substr($vendor->brand_name, 0, 1)) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $vendor->brand_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $vendor->product_category ?? 'No category' }}</div>
                                </div>
                                <span class="px-1.5 py-0.5 text-[10px] font-medium rounded shrink-0
                                    {{ $vendor->priority === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    {{ $vendor->priority === 'high' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                                    {{ $vendor->priority === 'medium' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    {{ $vendor->priority === 'low' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}
                                ">{{ ucfirst($vendor->priority) }}</span>
                            </a>
                            @endforeach
                        </div>
                        @else
                        <div class="text-sm text-gray-400 text-center py-6">All caught up! No vendors pending contact.</div>
                        @endif
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Recent Activity</h3>
                        @if($recentActivity->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($recentActivity as $activity)
                            <div class="flex items-start gap-2.5 p-2 rounded-lg {{ $activity->read_at ? 'opacity-60' : '' }}">
                                <div class="w-2 h-2 rounded-full mt-1.5 shrink-0
                                    {{ $activity->type === 'error' ? 'bg-red-500' : '' }}
                                    {{ $activity->type === 'reply' ? 'bg-purple-500' : '' }}
                                    {{ $activity->type === 'info' ? 'bg-blue-500' : '' }}
                                    {{ $activity->type === 'success' ? 'bg-green-500' : '' }}
                                "></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $activity->title }}</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">{{ $activity->message }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $activity->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-sm text-gray-400 text-center py-6">No recent activity</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right: Sidebar Widgets -->
            <div class="space-y-4">
                <!-- Sending Status -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Sending Status</h3>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sendingService->isSendingPaused() ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                            {{ $sendingService->isSendingPaused() ? 'Paused' : 'Active' }}
                        </span>
                    </div>
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Daily Limit</span>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $sendingService->getDailySentCount() }} / {{ $sendingService->getDailyLimit() }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Hourly Limit</span>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $sendingService->getHourlySentCount() }} / {{ $sendingService->getHourlyLimit() }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Within Schedule</span>
                            <span class="text-xs font-semibold {{ $sendingService->isWithinSendingSchedule() ? 'text-green-600' : 'text-gray-400' }}">{{ $sendingService->isWithinSendingSchedule() ? 'Yes' : 'No' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Mode</span>
                            <span class="text-xs font-semibold {{ $sendingService->isTestMode() ? 'text-orange-600' : 'text-green-600' }}">{{ $sendingService->isTestMode() ? 'Test' : 'Live' }}</span>
                        </div>
                    </div>
                </div>

                <!-- AI Stats -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">AI Statistics</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-2 rounded-lg bg-gray-50 dark:bg-gray-700/30">
                            <div class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $aiStats['generated'] ?? 0 }}</div>
                            <div class="text-[10px] text-gray-500">Generated</div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-gray-50 dark:bg-gray-700/30">
                            <div class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $aiStats['regenerated'] ?? 0 }}</div>
                            <div class="text-[10px] text-gray-500">Regenerated</div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-gray-50 dark:bg-gray-700/30">
                            <div class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $aiStats['total_calls'] ?? 0 }}</div>
                            <div class="text-[10px] text-gray-500">API Calls</div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-gray-50 dark:bg-gray-700/30">
                            <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($aiStats['cost'] ?? 0, 4) }}</div>
                            <div class="text-[10px] text-gray-500">Est. Cost</div>
                        </div>
                    </div>
                </div>

                <!-- Vendor Breakdown -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Vendor Breakdown</h3>
                    <div class="space-y-2">
                        @php
                        $breakdownItems = [
                            ['label' => 'Contacted', 'key' => 'contacted', 'color' => 'bg-blue-500'],
                            ['label' => 'Not Contacted', 'key' => 'not_contacted', 'color' => 'bg-gray-400'],
                            ['label' => 'Opted Out', 'key' => 'opted_out', 'color' => 'bg-red-500'],
                            ['label' => 'Invalid Email', 'key' => 'invalid_email', 'color' => 'bg-orange-500'],
                        ];
                        $breakdownTotal = ($vendorBreakdown['contacted'] ?? 0) + ($vendorBreakdown['not_contacted'] ?? 0) + ($vendorBreakdown['opted_out'] ?? 0) + ($vendorBreakdown['invalid_email'] ?? 0) ?: 1;
                        @endphp
                        @foreach($breakdownItems as $item)
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $item['color'] }} shrink-0"></div>
                            <div class="flex-1 text-xs text-gray-600 dark:text-gray-400">{{ $item['label'] }}</div>
                            <div class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $vendorBreakdown[$item['key']] ?? 0 }}</div>
                            <div class="w-16 h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full {{ $item['color'] }} rounded-full" style="width: {{ (($vendorBreakdown[$item['key']] ?? 0) / $breakdownTotal) * 100 }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Product Stats -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Product Stats</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Products Tracked</span>
                            <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ $products['total'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Profitable</span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $products['profitable'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Avg Margin</span>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ number_format($products['avg_margin'] ?? 0, 1) }}%</span>
                        </div>
                        @if(($marginAlerts['unprofitable_count'] ?? 0) > 0)
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <a href="{{ route('products.index') }}" class="flex items-center justify-between text-xs">
                                <span class="text-red-600 dark:text-red-400">Margin Alerts</span>
                                <span class="px-1.5 py-0.5 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium">{{ ($marginAlerts['unprofitable_count'] ?? 0) + ($marginAlerts['low_margin_count'] ?? 0) }}</span>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Follow-ups Due -->
                @if($followUpsDue->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-orange-200 dark:border-orange-800 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Follow-ups Due</h3>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">{{ $followUpsDue->count() }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach($followUpsDue->limit(5) as $vendor)
                        <a href="{{ route('vendors.show', $vendor->id) }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ $vendor->brand_name }}</div>
                                <div class="text-xs text-gray-400">Due {{ $vendor->next_follow_up?->format('M d') }}</div>
                            </div>
                            <span class="shrink-0 ml-2 px-1.5 py-0.5 text-[10px] font-medium rounded
                                {{ $vendor->priority === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                {{ $vendor->priority === 'high' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                                {{ $vendor->priority === 'medium' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $vendor->priority === 'low' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}
                            ">{{ ucfirst($vendor->priority) }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('emailsChart'), {
            type: 'line',
            data: {
                labels: {{ json_encode($emailChartData['labels']) }},
                datasets: [{
                    label: 'Emails Sent',
                    data: {{ json_encode($emailChartData['values']) }},
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 9 } } } }
            }
        });

        new Chart(document.getElementById('vendorStatusChart'), {
            type: 'doughnut',
            data: {
                labels: {{ json_encode($vendorStatusData['labels']) }},
                datasets: [{
                    data: {{ json_encode($vendorStatusData['values']) }},
                    backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#6b7280','#84cc16','#06b6d4','#a855f7']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 10, padding: 8 } } }
            }
        });
    });
    </script>
    @endpush
</x-app-layout>
