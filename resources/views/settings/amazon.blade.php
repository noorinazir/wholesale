<x-app-layout>
    <x-page-header title="Amazon SP-API Settings" :back="route('settings.system')">
    </x-page-header>

    <div class="max-w-3xl mx-auto space-y-4">
        <x-settings-tabs active="amazon" />

        @if(session('status'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-sm text-red-700 dark:text-red-400">
            {{ session('error') }}
        </div>
        @endif

        <!-- Connection Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg {{ $configured ? 'bg-green-50 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }} flex items-center justify-center">
                        @if($configured)
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">SP-API Connection</div>
                        <div class="text-xs {{ $configured ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">{{ $configured ? 'Configured & Ready' : 'Not configured' }}</div>
                    </div>
                </div>
                @if($configured)
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('settings.amazon.sync') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Sync Now
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <!-- Credentials Form -->
        <form method="POST" action="{{ route('settings.amazon.save') }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            @csrf
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">API Credentials</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Enter your Amazon Selling Partner API credentials. All fields are encrypted in the database.
                See the <a href="https://developer-docs.amazon.com/sp-api/docs/welcome" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">SP-API docs</a> for setup instructions.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">LWA Client ID</label>
                    <input type="text" name="lwa_client_id" value="{{ old('lwa_client_id', $settings['lwa_client_id'] ?? '') }}" placeholder="amzn1.application-oa2-client.xxxxx" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm font-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">LWA Client Secret</label>
                    <input type="password" name="lwa_client_secret" value="{{ old('lwa_client_secret', $settings['lwa_client_secret'] ?? '') }}" placeholder="amzn1.oa2-cs.v1.xxxxx" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm font-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">LWA Refresh Token</label>
                    <input type="password" name="refresh_token" value="{{ old('refresh_token', $settings['refresh_token'] ?? '') }}" placeholder="amzn1.auth.oa2-refresh.xxxxx" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">AWS Access Key ID</label>
                    <input type="text" name="sp_api_access_key" value="{{ old('sp_api_access_key', $settings['sp_api_access_key'] ?? '') }}" placeholder="AKIAxxxxx" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">AWS Secret Access Key</label>
                    <input type="password" name="sp_api_secret_key" value="{{ old('sp_api_secret_key', $settings['sp_api_secret_key'] ?? '') }}" placeholder="Secret key" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Marketplace ID</label>
                    <input type="text" name="marketplace_id" value="{{ old('marketplace_id', $settings['marketplace_id'] ?? 'ATVPDKIKX0DER') }}" placeholder="ATVPDKIKX0DER (US)" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm font-mono">
                    <p class="text-[10px] text-gray-400 mt-0.5">US: ATVPDKIKX0DER, UK: A1F83G8C2ARO7P, DE: A1PA6795UKMFR9</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">AWS Region</label>
                    <select name="aws_region" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm">
                        <option value="us-east-1" @selected(($settings['aws_region'] ?? 'us-east-1') === 'us-east-1')>us-east-1 (North America)</option>
                        <option value="eu-west-1" @selected(($settings['aws_region'] ?? '') === 'eu-west-1')>eu-west-1 (Europe)</option>
                        <option value="us-west-2" @selected(($settings['aws_region'] ?? '') === 'us-west-2')>us-west-2 (Far East)</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">SP-API Endpoint</label>
                    <input type="text" name="sp_api_endpoint" value="{{ old('sp_api_endpoint', $settings['sp_api_endpoint'] ?? 'https://sellingpartnerapi-na.amazon.com') }}" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm font-mono">
                    <p class="text-[10px] text-gray-400 mt-0.5">NA: sellingpartnerapi-na.amazon.com, EU: sellingpartnerapi-eu.amazon.com, FE: sellingpartnerapi-fe.amazon.com</p>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                @if($configured)
                <form method="POST" action="{{ route('settings.amazon.disconnect') }}" class="inline" onsubmit="return confirm('Disconnect from Amazon SP-API? Sync will stop but existing data remains.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">Disconnect</button>
                </form>
                @endif
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg ml-auto">
                    {{ $configured ? 'Update Credentials' : 'Connect to Amazon' }}
                </button>
            </div>
        </form>

        <!-- Sync Options -->
        @if($configured)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Sync Options</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/30">
                    <div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Sync Products</div>
                        <div class="text-xs text-gray-400">Pull catalog details, pricing, and fees for products with ASINs</div>
                    </div>
                    <form method="POST" action="{{ route('settings.amazon.sync') }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="products">
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20">Sync Products</button>
                    </form>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/30">
                    <div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Sync Orders</div>
                        <div class="text-xs text-gray-400">Import orders from the last 7 days</div>
                    </div>
                    <form method="POST" action="{{ route('settings.amazon.sync') }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="orders">
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20">Sync Orders</button>
                    </form>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/30">
                    <div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Sync Inventory</div>
                        <div class="text-xs text-gray-400">Update stock quantities from FBA inventory</div>
                    </div>
                    <form method="POST" action="{{ route('settings.amazon.sync') }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="inventory">
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20">Sync Inventory</button>
                    </form>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700/30 dark:to-gray-700/30">
                    <div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Full Sync</div>
                        <div class="text-xs text-gray-400">Sync everything: products, orders, and inventory</div>
                    </div>
                    <form method="POST" action="{{ route('settings.amazon.sync') }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="full">
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Full Sync</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Auto-Sync Schedule -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Auto-Sync Schedule</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Add this to your <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">routes/console.php</code> or scheduler for automatic sync:</p>
            <pre class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 text-xs text-gray-700 dark:text-gray-300 overflow-x-auto">$schedule->command('amazon:sync')->everySixHours();</pre>
        </div>
        @endif

        <!-- Setup Guide -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Setup Guide</h3>
            <ol class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                <li class="flex gap-2"><span class="font-bold text-indigo-600">1.</span> Register as a developer at <a href="https://developer.amazon.com/spapi" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">developer.amazon.com/spapi</a></li>
                <li class="flex gap-2"><span class="font-bold text-indigo-600">2.</span> Create a Seller Central app to get LWA Client ID and Secret</li>
                <li class="flex gap-2"><span class="font-bold text-indigo-600">3.</span> Create an IAM user with SP-API access for AWS keys</li>
                <li class="flex gap-2"><span class="font-bold text-indigo-600">4.</span> Authorize your app to get the LWA Refresh Token</li>
                <li class="flex gap-2"><span class="font-bold text-indigo-600">5.</span> Enter all credentials above and click "Connect to Amazon"</li>
                <li class="flex gap-2"><span class="font-bold text-indigo-600">6.</span> Use "Sync Now" or set up auto-sync via scheduler</li>
            </ol>
        </div>
    </div>
</x-app-layout>
