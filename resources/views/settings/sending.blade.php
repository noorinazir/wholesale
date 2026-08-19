<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Sending Limits & Schedule</h2>
    </x-slot>

    @php
    $delayType = \App\Models\SystemSetting::get('delay_type', 'random');
    $minDelay = \App\Models\SystemSetting::get('min_delay_seconds', 60);
    $maxDelay = \App\Models\SystemSetting::get('max_delay_seconds', 180);
    $dailyLimit = \App\Models\SystemSetting::get('daily_email_limit', 50);
    $hourlyLimit = \App\Models\SystemSetting::get('hourly_email_limit', 10);
    $startTime = \App\Models\SystemSetting::get('sending_start_time', '09:00');
    $endTime = \App\Models\SystemSetting::get('sending_end_time', '17:00');
    $timezone = \App\Models\SystemSetting::get('sending_timezone', config('app.timezone'));
    $allowedDays = json_decode(\App\Models\SystemSetting::get('allowed_weekdays', '["1","2","3","4","5"]'), true);
    @endphp

    <div class="max-w-3xl mx-auto space-y-6">
        <x-settings-tabs active="sending" />
        <!-- Delay Configuration -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Sending Delay</h3>
            <form method="POST" action="{{ route('settings.sending') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Delay Type</label>
                    <select name="delay_type" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <option value="random" @selected($delayType === 'random')>Random (between min and max)</option>
                        <option value="fixed" @selected($delayType === 'fixed')>Fixed (use min value)</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Min Delay (seconds)</label>
                        <input type="number" name="min_delay_seconds" value="{{ $minDelay }}" min="0" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Delay (seconds)</label>
                        <input type="number" name="max_delay_seconds" value="{{ $maxDelay }}" min="0" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Daily Email Limit</label>
                        <input type="number" name="daily_email_limit" value="{{ $dailyLimit }}" min="1" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hourly Email Limit</label>
                        <input type="number" name="hourly_email_limit" value="{{ $hourlyLimit }}" min="1" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <button type="submit" name="action" value="limits" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save Limits & Delay</button>
            </form>
        </div>

        <!-- Schedule -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Sending Schedule</h3>
            <form method="POST" action="{{ route('settings.sending') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
                        <input type="time" name="sending_start_time" value="{{ $startTime }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
                        <input type="time" name="sending_end_time" value="{{ $endTime }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                    <input type="text" name="sending_timezone" value="{{ $timezone }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="America/Edmonton">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Allowed Weekdays</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach(['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'] as $day => $label)
                        <label class="flex items-center gap-1">
                            <input type="checkbox" name="allowed_weekdays[]" value="{{ $day }}" {{ in_array($day, $allowedDays) ? 'checked' : '' }} class="rounded border-gray-300">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" name="action" value="schedule" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save Schedule</button>
            </form>
        </div>

        <!-- Emergency Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Emergency Controls</h3>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('settings.sending') }}">
                    @csrf
                    <button type="submit" name="action" value="pause" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">Pause All Sending</button>
                </form>
                <form method="POST" action="{{ route('settings.sending') }}">
                    @csrf
                    <button type="submit" name="action" value="resume" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Resume Sending</button>
                </form>
                <form method="POST" action="{{ route('settings.sending') }}">
                    @csrf
                    <button type="submit" name="action" value="cancel_pending" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700" onclick="return confirm('Cancel all pending emails?')">Cancel All Pending</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
