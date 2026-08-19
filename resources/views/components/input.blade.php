@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
])

@php
$isRequired = $required ? true : false;
$labelText = $label ?? ucfirst(str_replace('_', ' ', $name));
@endphp

<div>
    @if($labelText)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
        {{ $labelText }}
        @if($isRequired)<span class="text-red-500">*</span>@endif
    </label>
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if($isRequired) required @endif
        class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow"
    >
    @if($hint)
    <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
    @error($name)
    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
    @enderror
</div>
