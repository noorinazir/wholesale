@props(['label', 'value', 'color' => 'indigo'])

@php
$colors = [
    'indigo' => ['bar' => 'bg-indigo-600'],
    'green' => ['bar' => 'bg-green-600'],
    'blue' => ['bar' => 'bg-blue-600'],
    'gray' => ['bar' => 'bg-gray-500'],
    'yellow' => ['bar' => 'bg-yellow-500'],
    'red' => ['bar' => 'bg-red-600'],
    'orange' => ['bar' => 'bg-orange-500'],
    'purple' => ['bar' => 'bg-purple-600'],
    'pink' => ['bar' => 'bg-pink-600'],
];
$c = $colors[$color] ?? $colors['indigo'];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-100 dark:border-gray-700 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-1 h-full {{ $c['bar'] }}"></div>
    <div class="pl-2">
        <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $value }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
    </div>
</div>
