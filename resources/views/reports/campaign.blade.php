<x-app-layout>
    @php
    $campaign = $campaign ?? null;
    $summary = $summary ?? [];
    $pivotStats = $pivotStats ?? collect();
    $followUpStats = $followUpStats ?? [];
    $dailySending = $dailySending ?? ['labels' => [], 'values' => []];
    @endphp

    <x-page-header title="Campaign Report" back="{{ route('reports.index') }}">
        <a href="{{ route('campaigns.show', $campaign->id) }}" class="text-xs text-indigo-600 hover:underline">View Campaign</a>
    </x-page-header>

    <div class="space-y-6">
        <!-- Campaign Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $campaign->name }}</h3>
                    @if ($campaign->description)
                    <p class="text-sm text-gray-500 mt-1">{{ $campaign->description }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-xs font-medium
                        {{ $campaign->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                        {{ $campaign->status === 'completed' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                        {{ $campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                        {{ $campaign->status === 'draft' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}
                    ">{{ ucfirst($campaign->status) }}</span>
                    @if ($campaign->started_at)
                    <span class="text-xs text-gray-400">Started {{ $campaign->started_at->format('M d, Y') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Summary KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <x-stat-card label="Total Emails" value="{{ $summary['total_emails'] ?? 0 }}" color="indigo" />
            <x-stat-card label="Sent" value="{{ $summary['sent'] ?? 0 }}" color="blue" />
            <x-stat-card label="Failed" value="{{ $summary['failed'] ?? 0 }}" color="red" />
            <x-stat-card label="Scheduled" value="{{ $summary['scheduled'] ?? 0 }}" color="yellow" />
            <x-stat-card label="Success Rate" value="{{ $summary['success_rate'] ?? 0 }}%" color="green" />
            <x-stat-card label="Replies" value="{{ $summary['replied'] ?? 0 }}" color="purple" />
            <x-stat-card label="Interested" value="{{ $summary['interested'] ?? 0 }}" color="orange" />
            <x-stat-card label="Approved" value="{{ $summary['approved'] ?? 0 }}" color="green" />
            <x-stat-card label="Reply Rate" value="{{ $summary['reply_rate'] ?? 0 }}%" color="purple" />
            <x-stat-card label="AI Cost" value="${{ number_format($summary['ai_cost'] ?? 0, 4) }}" color="orange" />
        </div>

        <!-- Conversion Rates -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-card title="Conversion Rates" class="lg:col-span-2">
                <div class="space-y-4">
                    @php
                    $rates = [
                        ['label' => 'Reply Rate', 'value' => $summary['reply_rate'] ?? 0, 'color' => 'bg-purple-500', 'desc' => 'Replies / Sent'],
                        ['label' => 'Interest Rate', 'value' => $summary['interest_rate'] ?? 0, 'color' => 'bg-yellow-500', 'desc' => 'Interested / Sent'],
                        ['label' => 'Approval Rate', 'value' => $summary['approval_rate'] ?? 0, 'color' => 'bg-green-500', 'desc' => 'Approved / Sent'],
                        ['label' => 'Success Rate', 'value' => $summary['success_rate'] ?? 0, 'color' => 'bg-blue-500', 'desc' => 'Sent / Total'],
                    ];
                    @endphp
                    @foreach ($rates as $rate)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $rate['label'] }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $rate['value'] }}% <span class="text-gray-400 font-normal ml-1">{{ $rate['desc'] }}</span></span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-4">
                            <div class="{{ $rate['color'] }} h-4 rounded-full transition-all" style="width: {{ min($rate['value'], 100) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="AI Usage">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">AI Calls</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $summary['ai_calls'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">AI Cost</span>
                        <span class="text-sm font-semibold text-orange-600">${{ number_format($summary['ai_cost'] ?? 0, 4) }}</span>
                    </div>
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="text-xs text-gray-500 mb-2">Cost per Email Sent</div>
                        <div class="text-xl font-bold text-gray-800 dark:text-gray-200">
                            ${{ ($summary['sent'] ?? 0) > 0 ? number_format(($summary['ai_cost'] ?? 0) / $summary['sent'], 4) : '0.0000' }}
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Daily Sending Chart -->
        <x-card title="Daily Sending Volume">
            @if (!empty($dailySending['values']))
            <canvas id="dailySendingChart" height="200"></canvas>
            @else
            <x-empty-state icon="chart" title="No sending data" description="No emails have been sent for this campaign yet." />
            @endif
        </x-card>

        <!-- Pivot Status & Follow-up Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="Vendor Pipeline Status">
                <div class="space-y-2">
                    @if ($pivotStats->isNotEmpty())
                        @foreach ($pivotStats as $status => $count)
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $count }}</span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-400 text-center py-4">No vendor data in this campaign.</p>
                    @endif
                </div>
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
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $campaign->max_followups ?? 0 }}</div>
                                <div class="text-[10px] text-gray-500">Max Follow-ups</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $campaign->followup_delay_days ?? 0 }}d</div>
                                <div class="text-[10px] text-gray-500">Delay Between</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Campaign Settings Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-card title="Automation Settings">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Auto Approve</span>
                        <span class="text-sm font-semibold {{ $campaign->auto_approve ? 'text-green-600' : 'text-gray-400' }}">{{ $campaign->auto_approve ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Auto Follow-up</span>
                        <span class="text-sm font-semibold {{ $campaign->auto_followup_enabled ? 'text-green-600' : 'text-gray-400' }}">{{ $campaign->auto_followup_enabled ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Max Follow-ups</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $campaign->max_followups ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Follow-up Delay</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $campaign->followup_delay_days ?? 0 }} days</span>
                    </div>
                </div>
            </x-card>

            <x-card title="Objective">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $campaign->objective ?? 'No objective set' }}</p>
            </x-card>

            <x-card title="Timeline">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Created</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $campaign->created_at?->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Started</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $campaign->started_at?->format('M d, Y') ?? 'Not started' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Completed</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $campaign->completed_at?->format('M d, Y') ?? 'Not completed' }}</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        @if (!empty($dailySending['values']))
        new Chart(document.getElementById('dailySendingChart'), {
            type: 'bar',
            data: {
                labels: {{ json_encode($dailySending['labels']) }},
                datasets: [{
                    label: 'Emails Sent',
                    data: {{ json_encode($dailySending['values']) }},
                    backgroundColor: 'rgba(99, 102, 241, 0.7)'
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
        @endif
    });
    </script>
    @endpush
</x-app-layout>
