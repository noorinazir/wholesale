<x-app-layout>
    <x-page-header title="AI Financial Analysis" :back="route('finance.dashboard')">
    </x-page-header>

    <div class="space-y-4">
        <!-- Date Range Filter -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">From</label>
                    <input type="date" name="date_from" value="{{ $startDate->format('Y-m-d') }}" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">To</label>
                    <input type="date" name="date_to" value="{{ $endDate->format('Y-m-d') }}" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Analyze</button>
                <a href="{{ route('finance.ai-analysis') }}?date_from={{ $startDate->format('Y-m-d') }}&date_to={{ $endDate->format('Y-m-d') }}&generate_narrative=1" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    Generate AI Narrative
                </a>
            </form>
        </div>

        <!-- Anomaly Detection -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Anomaly Detection</h3>
                <span class="text-xs text-gray-400">{{ $anomalies['summary'] }}</span>
            </div>

            @if(!empty($anomalies['stats']))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="text-center">
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $anomalies['stats']['orders_analyzed'] }}</div>
                    <div class="text-xs text-gray-400">Orders Analyzed</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $anomalies['stats']['expenses_analyzed'] }}</div>
                    <div class="text-xs text-gray-400">Expenses Analyzed</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-200">${{ number_format($anomalies['stats']['avg_revenue'], 2) }}</div>
                    <div class="text-xs text-gray-400">Avg Revenue</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ number_format($anomalies['stats']['avg_margin'], 1) }}%</div>
                    <div class="text-xs text-gray-400">Avg Margin</div>
                </div>
            </div>
            @endif

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($anomalies['anomalies'] as $anomaly)
                @php
                    $severityColors = [
                        'warning' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400',
                        'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400',
                        'error' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400',
                    ];
                    $color = $severityColors[$anomaly['severity'] ?? 'info'] ?? $severityColors['info'];
                @endphp
                <div class="px-4 py-3 flex items-start gap-3">
                    <div class="flex-shrink-0 w-2 h-2 rounded-full mt-1.5 {{ $anomaly['severity'] === 'warning' ? 'bg-amber-500' : 'bg-blue-500' }}"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $anomaly['description'] }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            Type: {{ str_replace('_', ' ', $anomaly['type']) }}
                            @if(isset($anomaly['order_id'])) • Order: {{ $anomaly['order_id'] }} @endif
                            @if(isset($anomaly['expense_id'])) • Expense #{{ $anomaly['expense_id'] }} @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    No anomalies detected in this period.
                </div>
                @endforelse
            </div>
        </div>

        <!-- AI Narrative -->
        @if($narrative)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">AI Monthly Analysis</h3>
                <span class="text-xs text-gray-400">({{ $startDate->format('M d') }} – {{ $endDate->format('M d, Y') }})</span>
            </div>
            <div class="p-6 prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $narrative }}</div>
        </div>
        @elseif($narrativeError)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-700 dark:text-red-400">
            Failed to generate AI narrative: {{ $narrativeError }}
        </div>
        @endif
    </div>
</x-app-layout>
