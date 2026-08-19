<x-app-layout>
    @php
    $kpis = $kpis ?? [];
    $emailFunnel = $emailFunnel ?? [];
    $dailyVolume = $dailyVolume ?? ['labels' => [], 'values' => []];
    $successFailDaily = $successFailDaily ?? ['labels' => [], 'sent' => [], 'failed' => []];
    $vendorStatusDistribution = $vendorStatusDistribution ?? ['labels' => [], 'values' => []];
    $topCampaigns = $topCampaigns ?? collect();
    $replyTrend = $replyTrend ?? ['labels' => [], 'values' => []];
    $aiCostBreakdown = $aiCostBreakdown ?? ['labels' => [], 'calls' => [], 'costs' => [], 'total_cost' => 0, 'total_calls' => 0, 'total_input_tokens' => 0, 'total_output_tokens' => 0];
    $geoDistribution = $geoDistribution ?? ['labels' => [], 'values' => []];
    $followUpStats = $followUpStats ?? [];
    $categoryPerformance = $categoryPerformance ?? ['labels' => [], 'total' => [], 'contacted' => [], 'replied' => [], 'approved' => []];
    $suppressionStats = $suppressionStats ?? ['total' => 0, 'by_type' => []];
    @endphp

    <x-page-header title="Reports">
        <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-2">
            <select name="range" onchange="this.form.submit()" class="text-xs rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-indigo-500">
                <option value="7" @selected($range === '7')>Last 7 days</option>
                <option value="30" @selected($range === '30')>Last 30 days</option>
                <option value="90" @selected($range === '90')>Last 90 days</option>
                <option value="365" @selected($range === '365')>Last 12 months</option>
            </select>
            <a href="{{ route('reports.export', ['range' => $range]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Export CSV
            </a>
        </form>
    </x-page-header>

    <div class="space-y-6">
        <p class="text-xs text-gray-500">Reporting period: {{ $from }} to {{ $to }}</p>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <x-stat-card label="Emails Sent" value="{{ $kpis['emails_sent'] ?? 0 }}" color="blue" />
            <x-stat-card label="Reply Rate" value="{{ $kpis['reply_rate'] ?? 0 }}%" color="green" />
            <x-stat-card label="Success Rate" value="{{ $kpis['success_rate'] ?? 0 }}%" color="indigo" />
            <x-stat-card label="Replies Received" value="{{ $kpis['replies_received'] ?? 0 }}" color="purple" />
            <x-stat-card label="Approved Vendors" value="{{ $kpis['approved_vendors'] ?? 0 }}" color="green" />
            <x-stat-card label="Vendors Contacted" value="{{ $kpis['vendors_contacted'] ?? 0 }}" color="blue" />
            <x-stat-card label="New Vendors" value="{{ $kpis['new_vendors'] ?? 0 }}" color="indigo" />
            <x-stat-card label="Emails Failed" value="{{ $kpis['emails_failed'] ?? 0 }}" color="red" />
            <x-stat-card label="AI Cost" value="${{ number_format($kpis['ai_cost'] ?? 0, 4) }}" color="orange" />
            <x-stat-card label="AI Calls" value="{{ $kpis['ai_calls'] ?? 0 }}" color="yellow" />
        </div>

        <!-- Email Funnel -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Outreach Funnel">
                <div class="space-y-3">
                    @php $maxFunnel = max($emailFunnel['total'] ?? 1, 1); @endphp
                    @php
                    $funnelSteps = [
                        ['label' => 'Total Vendors', 'key' => 'total', 'color' => 'bg-indigo-500', 'rate_key' => null],
                        ['label' => 'Contacted', 'key' => 'contacted', 'color' => 'bg-blue-500', 'rate_key' => 'contact_rate'],
                        ['label' => 'Replied', 'key' => 'replied', 'color' => 'bg-purple-500', 'rate_key' => 'reply_rate'],
                        ['label' => 'Interested', 'key' => 'interested', 'color' => 'bg-yellow-500', 'rate_key' => 'interest_rate'],
                        ['label' => 'Approved', 'key' => 'approved', 'color' => 'bg-green-500', 'rate_key' => 'approval_rate'],
                    ];
                    @endphp
                    @foreach ($funnelSteps as $step)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $step['label'] }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">
                                {{ $emailFunnel[$step['key']] ?? 0 }}
                                @if ($step['rate_key'] && ($emailFunnel[$step['rate_key']] ?? 0) > 0)
                                <span class="text-gray-400 ml-1">({{ $emailFunnel[$step['rate_key']] }}%)</span>
                                @endif
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-6">
                            <div class="{{ $step['color'] }} h-6 rounded-full transition-all" style="width: {{ ($emailFunnel[$step['key']] ?? 0) > 0 ? max(($emailFunnel[$step['key']] / $maxFunnel) * 100, 5) : 0 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="Vendor Status Distribution">
                <canvas id="vendorStatusChart" height="200"></canvas>
            </x-card>
        </div>

        <!-- Daily Volume & Success/Fail -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Daily Sending Volume">
                <canvas id="dailyVolumeChart" height="200"></canvas>
            </x-card>
            <x-card title="Sent vs Failed (Daily)">
                <canvas id="successFailChart" height="200"></canvas>
            </x-card>
        </div>

        <!-- Reply Trend & Follow-up Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-card title="Reply Trend" class="lg:col-span-2">
                <canvas id="replyTrendChart" height="200"></canvas>
            </x-card>
            <x-card title="Follow-up Performance">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Total Follow-ups</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $followUpStats['total'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Sent</span>
                        <span class="text-sm font-semibold text-green-600">{{ $followUpStats['sent'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Scheduled</span>
                        <span class="text-sm font-semibold text-blue-600">{{ $followUpStats['scheduled'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Cancelled</span>
                        <span class="text-sm font-semibold text-red-600">{{ $followUpStats['cancelled'] ?? 0 }}</span>
                    </div>
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 text-center">
                        <div class="text-2xl font-bold {{ ($followUpStats['response_rate'] ?? 0) >= 15 ? 'text-green-600' : 'text-gray-500' }}">{{ $followUpStats['response_rate'] ?? 0 }}%</div>
                        <div class="text-xs text-gray-500 mt-1">Follow-up Response Rate</div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Campaign Performance Table -->
        <x-card title="Campaign Performance" padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-700 text-gray-500 dark:text-gray-400">
                            <th class="text-left font-medium px-5 py-3">Campaign</th>
                            <th class="text-left font-medium px-3 py-3">Status</th>
                            <th class="text-right font-medium px-3 py-3">Total</th>
                            <th class="text-right font-medium px-3 py-3">Sent</th>
                            <th class="text-right font-medium px-3 py-3">Failed</th>
                            <th class="text-right font-medium px-3 py-3">Replied</th>
                            <th class="text-right font-medium px-3 py-3">Approved</th>
                            <th class="text-right font-medium px-3 py-3">Reply %</th>
                            <th class="text-right font-medium px-3 py-3">Success %</th>
                            <th class="text-right font-medium px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($topCampaigns->isNotEmpty())
                            @foreach ($topCampaigns as $c)
                            <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $c['name'] }}</td>
                                <td class="px-3 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium
                                        {{ $c['status'] === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ $c['status'] === 'completed' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ $c['status'] === 'paused' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                        {{ $c['status'] === 'draft' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}
                                    ">{{ ucfirst($c['status']) }}</span>
                                </td>
                                <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $c['total'] }}</td>
                                <td class="px-3 py-3 text-right font-medium text-gray-800 dark:text-gray-200">{{ $c['sent'] }}</td>
                                <td class="px-3 py-3 text-right text-red-600">{{ $c['failed'] }}</td>
                                <td class="px-3 py-3 text-right text-purple-600">{{ $c['replied'] }}</td>
                                <td class="px-3 py-3 text-right text-green-600">{{ $c['approved'] }}</td>
                                <td class="px-3 py-3 text-right font-medium {{ $c['reply_rate'] >= 15 ? 'text-green-600' : 'text-gray-500' }}">{{ $c['reply_rate'] }}%</td>
                                <td class="px-3 py-3 text-right font-medium {{ $c['success_rate'] >= 90 ? 'text-green-600' : ($c['success_rate'] >= 70 ? 'text-yellow-600' : 'text-red-600') }}">{{ $c['success_rate'] }}%</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('reports.campaign', $c['id']) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Details</a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="10" class="px-5 py-8 text-center text-gray-400">No campaign data available for this period.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- AI Cost Breakdown & Geo Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="AI Usage & Cost Breakdown">
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="text-center">
                        <div class="text-xl font-bold text-indigo-600">{{ $aiCostBreakdown['total_calls'] }}</div>
                        <div class="text-[10px] text-gray-500">Total Calls</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-orange-600">${{ number_format($aiCostBreakdown['total_cost'], 4) }}</div>
                        <div class="text-[10px] text-gray-500">Total Cost</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-purple-600">{{ number_format($aiCostBreakdown['total_input_tokens'] + $aiCostBreakdown['total_output_tokens']) }}</div>
                        <div class="text-[10px] text-gray-500">Total Tokens</div>
                    </div>
                </div>
                <canvas id="aiCostChart" height="180"></canvas>
            </x-card>

            <x-card title="Geographic Distribution (Top 15)">
                <canvas id="geoChart" height="180"></canvas>
            </x-card>
        </div>

        <!-- Category Performance -->
        <x-card title="Category Performance" padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-700 text-gray-500 dark:text-gray-400">
                            <th class="text-left font-medium px-5 py-3">Category</th>
                            <th class="text-right font-medium px-3 py-3">Total Vendors</th>
                            <th class="text-right font-medium px-3 py-3">Contacted</th>
                            <th class="text-right font-medium px-3 py-3">Replied</th>
                            <th class="text-right font-medium px-3 py-3">Approved</th>
                            <th class="text-right font-medium px-5 py-3">Approval %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($categoryPerformance['labels']))
                            @foreach ($categoryPerformance['labels'] as $i => $cat)
                            <tr class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $cat }}</td>
                                <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400">{{ $categoryPerformance['total'][$i] ?? 0 }}</td>
                                <td class="px-3 py-3 text-right text-blue-600">{{ $categoryPerformance['contacted'][$i] ?? 0 }}</td>
                                <td class="px-3 py-3 text-right text-purple-600">{{ $categoryPerformance['replied'][$i] ?? 0 }}</td>
                                <td class="px-3 py-3 text-right text-green-600">{{ $categoryPerformance['approved'][$i] ?? 0 }}</td>
                                <td class="px-5 py-3 text-right font-medium">
                                    @php $approvalPct = ($categoryPerformance['total'][$i] ?? 0) > 0 ? round(($categoryPerformance['approved'][$i] / $categoryPerformance['total'][$i]) * 100, 1) : 0; @endphp
                                    {{ $approvalPct }}%
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-gray-400">No category data available.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Suppression Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Suppression List">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Total Suppressed</span>
                        <span class="text-lg font-bold text-red-600">{{ $suppressionStats['total'] }}</span>
                    </div>
                    @if (!empty($suppressionStats['by_type']))
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="text-xs font-medium text-gray-500 mb-2">By Reason</div>
                        <div class="space-y-2">
                            @foreach ($suppressionStats['by_type'] as $reason => $count)
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $reason)) }}</span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $count }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </x-card>

            <x-card title="AI Usage by Action">
                <canvas id="aiUsageChart" height="180"></canvas>
            </x-card>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartColors = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#6b7280','#84cc16','#06b6d4','#a855f7','#e11d48','#0ea5e9'];

        // Daily Volume
        new Chart(document.getElementById('dailyVolumeChart'), {
            type: 'line',
            data: {
                labels: {{ json_encode($dailyVolume['labels']) }},
                datasets: [{
                    label: 'Emails Sent',
                    data: {{ json_encode($dailyVolume['values']) }},
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        // Success vs Fail
        new Chart(document.getElementById('successFailChart'), {
            type: 'bar',
            data: {
                labels: {{ json_encode($successFailDaily['labels']) }},
                datasets: [
                    { label: 'Sent', data: {{ json_encode($successFailDaily['sent']) }}, backgroundColor: 'rgba(16, 185, 129, 0.7)' },
                    { label: 'Failed', data: {{ json_encode($successFailDaily['failed']) }}, backgroundColor: 'rgba(239, 68, 68, 0.7)' }
                ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        // Vendor Status
        new Chart(document.getElementById('vendorStatusChart'), {
            type: 'doughnut',
            data: {
                labels: {{ json_encode($vendorStatusDistribution['labels']) }},
                datasets: [{ data: {{ json_encode($vendorStatusDistribution['values']) }}, backgroundColor: chartColors }]
            },
            options: { responsive: true }
        });

        // Reply Trend
        new Chart(document.getElementById('replyTrendChart'), {
            type: 'line',
            data: {
                labels: {{ json_encode($replyTrend['labels']) }},
                datasets: [{
                    label: 'Replies',
                    data: {{ json_encode($replyTrend['values']) }},
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        // AI Cost
        new Chart(document.getElementById('aiCostChart'), {
            type: 'bar',
            data: {
                labels: {{ json_encode($aiCostBreakdown['labels']) }},
                datasets: [{
                    label: 'API Calls',
                    data: {{ json_encode($aiCostBreakdown['calls']) }},
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    yAxisID: 'y'
                }, {
                    label: 'Cost ($)',
                    data: {{ json_encode($aiCostBreakdown['costs']) }},
                    backgroundColor: 'rgba(249, 115, 22, 0.7)',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Calls' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Cost ($)' } }
                }
            }
        });

        // Geo Distribution
        new Chart(document.getElementById('geoChart'), {
            type: 'bar',
            data: {
                labels: {{ json_encode($geoDistribution['labels']) }},
                datasets: [{ label: 'Vendors', data: {{ json_encode($geoDistribution['values']) }}, backgroundColor: 'rgba(59, 130, 246, 0.7)' }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        // AI Usage
        new Chart(document.getElementById('aiUsageChart'), {
            type: 'doughnut',
            data: {
                labels: {{ json_encode($aiCostBreakdown['labels']) }},
                datasets: [{ data: {{ json_encode($aiCostBreakdown['calls']) }}, backgroundColor: chartColors }]
            },
            options: { responsive: true }
        });
    });
    </script>
    @endpush
</x-app-layout>
