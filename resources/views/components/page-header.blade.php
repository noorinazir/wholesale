@props([
    'title',
    'back' => null,
    'count' => null,
])

<div class="flex items-center justify-between mb-4" {{ $attributes->except(['title', 'back', 'count'])->filter(fn ($value, $key) => !in_array($key, ['title', 'back', 'count'])) }}>
    <div class="flex items-center gap-2.5">
        @if($back)
        <a href="{{ $back }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        @endif
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $title }}</h2>
        @if($count !== null)
        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">{{ $count }}</span>
        @endif
    </div>
    <div class="flex items-center gap-2">
        {{ $slot }}
    </div>
</div>
