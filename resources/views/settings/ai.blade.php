<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">AI Configuration</h2>
    </x-slot>

    @php
    $kimiService = app(\App\Services\AI\KimiService::class);
    $isConfigured = $kimiService->isConfigured();
    $totalCalls = \App\Models\AiGeneration::count();
    $totalCost = \App\Models\AiGeneration::sum('estimated_cost') ?? 0;
    $totalInputTokens = \App\Models\AiGeneration::sum('input_tokens') ?? 0;
    $totalOutputTokens = \App\Models\AiGeneration::sum('output_tokens') ?? 0;
    $currentModel = \App\Models\SystemSetting::get('kimi_model', config('services.kimi.model', 'moonshot-v1-8k'));
    $currentTemperature = \App\Models\SystemSetting::get('kimi_temperature', config('services.kimi.temperature', 0.7));
    $currentMaxTokens = \App\Models\SystemSetting::get('kimi_max_tokens', config('services.kimi.max_tokens', 2048));
    @endphp

    <div class="max-w-3xl mx-auto space-y-6">
        <x-settings-tabs active="ai" />
        <!-- AI Usage Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-stat-card label="API Calls" value="{{ $totalCalls }}" color="indigo" />
            <x-stat-card label="Est. Cost" value="${{ number_format($totalCost, 4) }}" color="green" />
            <x-stat-card label="Input Tokens" value="{{ number_format($totalInputTokens) }}" color="blue" />
            <x-stat-card label="Output Tokens" value="{{ number_format($totalOutputTokens) }}" color="purple" />
        </div>

        <!-- Configuration -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('settings.ai') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kimi API Key</label>
                    <input type="password" name="kimi_api_key" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="{{ $isConfigured ? '•••••••• (configured)' : 'Enter API key' }}">
                    <p class="text-xs text-gray-500 mt-1">Get your API key from <a href="https://platform.kimi.ai/console/api-keys" target="_blank" class="text-indigo-600 hover:underline">Kimi API Platform</a></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                        <select name="kimi_model" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                            <option value="kimi-k3" @selected($currentModel === 'kimi-k3')>kimi-k3</option>
                            <option value="kimi-k2.6" @selected($currentModel === 'kimi-k2.6')>kimi-k2.6</option>
                            <option value="kimi-k2.7-code-highspeed" @selected($currentModel === 'kimi-k2.7-code-highspeed')>kimi-k2.7-code-highspeed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Temperature</label>
                        <input type="number" step="0.1" min="0" max="2" name="kimi_temperature" value="{{ $currentTemperature }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Tokens</label>
                        <input type="number" name="kimi_max_tokens" value="{{ $currentMaxTokens }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Email Tone</label>
                    <select name="default_tone" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @foreach(['professional','friendly','concise','formal','relationship_focused','direct'] as $tone)
                        <option value="{{ $tone }}" @selected(\App\Models\SystemSetting::get('default_tone', 'professional') === $tone)>{{ ucfirst(str_replace('_', ' ', $tone)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Email Objective</label>
                    <input type="text" name="default_objective" value="{{ \App\Models\SystemSetting::get('default_objective', 'Wholesale Authorization') }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save AI Settings</button>
            </form>
        </div>

        <!-- Test Connection -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Test AI Connection</h3>
            <form method="POST" action="{{ route('settings.ai') }}" class="flex gap-3 items-end">
                @csrf
                <button type="submit" name="action" value="test" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700" @disabled(!$isConfigured)>Test Connection</button>
            </form>
        </div>
    </div>
</x-app-layout>
