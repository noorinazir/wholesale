@props(['active'])

<div class="mb-6 border-b border-gray-200 dark:border-gray-700">
    <nav class="flex flex-wrap gap-1 -mb-px">
        @foreach([
            'company' => ['Company', route('settings.company')],
            'smtp' => ['SMTP', route('settings.smtp')],
            'ai' => ['AI Config', route('settings.ai')],
            'sending' => ['Sending', route('settings.sending')],
            'amazon' => ['Amazon API', route('settings.amazon')],
            'users' => ['Users', route('settings.users')],
            'system' => ['System', route('settings.system')],
            'audit' => ['Audit Logs', route('settings.audit')],
        ] as $key => [$label, $url])
            <a href="{{ $url }}" class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $active === $key ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
