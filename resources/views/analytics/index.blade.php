<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Analytics</h2>
        </div>
    </x-slot>

    @php
    $overview = $overview ?? ['total_vendors' => 0, 'total_sent' => 0, 'total_queue' => 1, 'ai_cost' => 0];
    $dailyVolume = $dailyVolume ?? ['labels' => [], 'values' => []];
    $successFail = $successFail ?? ['sent' => 0, 'failed' => 0];
    $categoryData = $categoryData ?? ['labels' => [], 'values' => []];
    $aiUsage = $aiUsage ?? ['labels' => [], 'values' => []];
    @endphp

    <div class="space-y-6">
        <!-- Overview Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-stat-card label="Total Vendors" value="{{ $overview['total_vendors'] }}" color="indigo" />
            <x-stat-card label="Total Emails Sent" value="{{ $overview['total_sent'] }}" color="green" />
            <x-stat-card label="Success Rate" value="{{ $overview['total_queue'] > 0 ? round($overview['total_sent'] / $overview['total_queue'] * 100, 1) : 0 }}%" color="blue" />
            <x-stat-card label="Total AI Cost" value="${{ number_format($overview['ai_cost'], 4) }}" color="purple" />
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Daily Sending Volume (Last 30 Days)</h3>
                <canvas id="dailyVolumeChart" height="200"></canvas>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Successful vs Failed</h3>
                <canvas id="successFailChart" height="200"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Outreach Activity by Category</h3>
                <canvas id="categoryChart" height="200"></canvas>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">AI Usage by Action</h3>
                <canvas id="aiUsageChart" height="200"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('dailyVolumeChart'), {
            type: 'bar',
            data: { labels: {{ json_encode($dailyVolume['labels']) }}, datasets: [{ label: 'Sent', data: {{ json_encode($dailyVolume['values']) }}, backgroundColor: 'rgba(99, 102, 241, 0.6)' }] },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('successFailChart'), {
            type: 'doughnut',
            data: { labels: ['Successful', 'Failed'], datasets: [{ data: [{{ $successFail['sent'] }}, {{ $successFail['failed'] }}], backgroundColor: ['#10b981', '#ef4444'] }] },
            options: { responsive: true }
        });

        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: { labels: {{ json_encode($categoryData['labels']) }}, datasets: [{ label: 'Vendors', data: {{ json_encode($categoryData['values']) }}, backgroundColor: 'rgba(59, 130, 246, 0.6)' }] },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('aiUsageChart'), {
            type: 'doughnut',
            data: { labels: {{ json_encode($aiUsage['labels']) }}, datasets: [{ data: {{ json_encode($aiUsage['values']) }}, backgroundColor: ['#6366f1','#8b5cf6','#ec4899','#14b8a6','#f97316'] }] },
            options: { responsive: true }
        });
    });
    </script>
    @endpush
</x-app-layout>
