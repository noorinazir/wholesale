@props([
    'color' => 'gray',
    'text' => null,
])

@php
$colors = [
    'gray' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    'green' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'indigo' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    'red' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'pink' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
];
$c = $colors[$color] ?? $colors['gray'];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $c }}">
    {{ $text ?? $slot }}
</span>
