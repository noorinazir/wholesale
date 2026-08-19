<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">System Settings</h2>
    </x-slot>

    @php
    @endphp

    <div class="max-w-3xl mx-auto space-y-6">
        <x-settings-tabs active="system" />
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">General Settings</h3>
            <form method="POST" action="{{ route('settings.system') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Application Name</label>
                    <input type="text" name="app_name" value="{{ \App\Models\SystemSetting::get('app_name', config('app.name')) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Session Timeout (minutes)</label>
                    <input type="number" name="session_timeout" value="{{ \App\Models\SystemSetting::get('session_timeout', 120) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="enable_follow_ups" {{ \App\Models\SystemSetting::get('enable_follow_ups', false) ? 'checked' : '' }} class="rounded border-gray-300">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Enable automated follow-ups</span>
                    </label>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="include_opt_out" {{ \App\Models\SystemSetting::get('include_opt_out', true) ? 'checked' : '' }} class="rounded border-gray-300">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Include opt-out text in emails</span>
                    </label>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save Settings</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Data Export</h3>
            <div class="flex gap-3">
                <a href="{{ route('settings.system') }}?export=vendors" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700">Export Vendors CSV</a>
                <a href="{{ route('settings.system') }}?export=email_logs" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700">Export Email Logs CSV</a>
            </div>
        </div>
    </div>
</x-app-layout>
