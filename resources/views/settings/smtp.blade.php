<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">SMTP Configuration</h2>
    </x-slot>

    @php
    $smtp = \App\Models\SmtpSetting::where('is_active', true)->first() ?? new \App\Models\SmtpSetting();
    @endphp

    <div class="max-w-3xl mx-auto space-y-6">
        <x-settings-tabs active="smtp" />
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('settings.smtp') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SMTP Host</label>
                        <input type="text" name="host" value="{{ old('host', $smtp->host) }}" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="smtp.yourdomain.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Port</label>
                        <input type="number" name="port" value="{{ old('port', $smtp->port ?? 587) }}" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Encryption</label>
                        <select name="encryption" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                            <option value="tls" @selected($smtp->encryption === 'tls')>TLS</option>
                            <option value="ssl" @selected($smtp->encryption === 'ssl')>SSL</option>
                            <option value="none" @selected($smtp->encryption === 'none')>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                        <input type="text" name="username" value="{{ old('username', $smtp->username) }}" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                        <input type="password" name="password" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="{{ $smtp->exists ? '••••••••' : 'Enter password' }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Name</label>
                        <input type="text" name="from_name" value="{{ old('from_name', $smtp->from_name) }}" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Email</label>
                        <input type="email" name="from_email" value="{{ old('from_email', $smtp->from_email) }}" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reply-To</label>
                        <input type="email" name="reply_to" value="{{ old('reply_to', $smtp->reply_to) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <label class="flex items-center gap-2 mb-2">
                        <input type="checkbox" name="test_mode" {{ $smtp->test_mode ? 'checked' : '' }} class="rounded border-gray-300">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Test Mode (emails go to test address only)</span>
                    </label>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Mode Recipient</label>
                        <input type="email" name="test_mode_recipient" value="{{ old('test_mode_recipient', $smtp->test_mode_recipient) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="admin@yourcompany.com">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save SMTP Settings</button>
            </form>
        </div>

        <!-- IMAP / Inbox Checking -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Inbox Checking (IMAP)</h3>
            <form method="POST" action="{{ route('settings.smtp') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="action" value="imap">
                <label class="flex items-center gap-2 mb-3">
                    <input type="checkbox" name="inbox_checking_enabled" {{ $smtp->inbox_checking_enabled ? 'checked' : '' }} class="rounded border-gray-300">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable automatic inbox checking for replies</span>
                </label>
                <p class="text-xs text-gray-500 mb-3">When enabled, the app will poll your inbox every 5 minutes for replies from vendors and auto-update their status to "Replied".</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IMAP Host</label>
                        <input type="text" name="imap_host" value="{{ old('imap_host', $smtp->imap_host) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="imap.yourdomain.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IMAP Port</label>
                        <input type="number" name="imap_port" value="{{ old('imap_port', $smtp->imap_port ?? 993) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IMAP Encryption</label>
                        <select name="imap_encryption" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                            <option value="ssl" @selected($smtp->imap_encryption === 'ssl' || !$smtp->imap_encryption)>SSL</option>
                            <option value="tls" @selected($smtp->imap_encryption === 'tls')>TLS</option>
                            <option value="notls" @selected($smtp->imap_encryption === 'notls')>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IMAP Username</label>
                        <input type="text" name="imap_username" value="{{ old('imap_username', $smtp->imap_username) }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="{{ $smtp->from_email ?? 'your@email.com' }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IMAP Password</label>
                        <input type="password" name="imap_password" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="{{ $smtp->getRawOriginal('imap_password') ? '••••••••' : 'Enter IMAP password' }}">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save IMAP Settings</button>
            </form>

            @if($smtp->inbox_checking_enabled)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <form method="POST" action="{{ route('inbox.check') }}" class="flex items-center gap-3">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Check Inbox Now</button>
                    @if($smtp->last_inbox_check_at)
                    <span class="text-xs text-gray-500">Last checked: {{ $smtp->last_inbox_check_at->format('M d, Y H:i') }}</span>
                    @endif
                </form>
            </div>
            @endif
        </div>

        @if($smtp->exists)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Test SMTP Connection</h3>
            <form method="POST" action="{{ route('settings.smtp') }}" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Email Address</label>
                    <input type="email" name="test_email" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="your@email.com">
                </div>
                <button type="submit" name="action" value="test" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Send Test Email</button>
            </form>
            @if($smtp->last_tested_at)
            <div class="mt-4 text-sm">
                <span class="text-gray-500">Last tested:</span> {{ $smtp->last_tested_at->format('M d, Y H:i') }}
                <span class="ml-2 px-2 py-1 text-xs rounded-full {{ $smtp->last_test_success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $smtp->last_test_success ? 'Success' : 'Failed' }}</span>
            </div>
            @endif
        </div>
        @endif

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-2">Deliverability Checklist</h4>
            <ul class="text-sm text-blue-600 dark:text-blue-400 space-y-1">
                <li>• Configure SPF record for your domain</li>
                <li>• Configure DKIM signing</li>
                <li>• Set up DMARC policy</li>
                <li>• Use a dedicated sending domain</li>
                <li>• Monitor bounce rates</li>
                <li>• Respect opt-out requests</li>
            </ul>
        </div>
    </div>
</x-app-layout>
