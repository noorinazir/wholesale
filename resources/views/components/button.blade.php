@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'size' => 'md',
])

@php
$variants = [
    'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500',
    'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600',
    'ghost' => 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700',
];
$sizes = [
    'sm' => 'px-2.5 py-1 text-xs',
    'md' => 'px-3.5 py-1.5 text-sm',
    'lg' => 'px-5 py-2 text-base',
];
$v = $variants[$variant] ?? $variants['primary'];
$s = $sizes[$size] ?? $sizes['md'];
$classes = "inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 {$v} {$s}";
@endphp

@if($href)
<a href="{{ $href }}" class="{{ $classes }}" {{ $attributes->except(['variant', 'type', 'href', 'size'])->filter(fn ($value, $key) => !in_array($key, ['variant', 'type', 'href', 'size'])) }}>{{ $slot }}</a>
@else
<button type="{{ $type }}" class="{{ $classes }}" {{ $attributes->except(['variant', 'type', 'href', 'size'])->filter(fn ($value, $key) => !in_array($key, ['variant', 'type', 'href', 'size'])) }}>{{ $slot }}</button>
@endif
