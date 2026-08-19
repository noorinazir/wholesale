@props([
    'title' => null,
    'action' => null,
    'padding' => 'p-5',
])

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
    @if($title || $action)
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700">
        @if($title)
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $title }}</h3>
        @endif
        @if($action)
        <div>{{ $action }}</div>
        @endif
    </div>
    @endif
    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
