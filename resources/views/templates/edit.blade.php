<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Edit Template</h2>
    </x-slot>

    @php
    $template = \App\Models\EmailTemplate::findOrFail(request()->route('id'));
    @endphp

    <div class="max-w-3xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('templates.edit', $template->id) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Name</label>
                    <input type="text" name="name" value="{{ $template->name }}" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select name="type" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @foreach(['wholesale_inquiry','amazon_reseller','distributor_inquiry','catalog_request','dealer_application','pricing_request'] as $type)
                        <option value="{{ $type }}" @selected($template->type === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject Template</label>
                    <input type="text" name="subject_template" value="{{ $template->subject_template }}" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Body Template</label>
                    <textarea name="body_template" rows="12" required class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 font-mono text-sm">{{ $template->body_template }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <input type="text" name="description" value="{{ $template->description }}" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" {{ $template->is_active ? 'checked' : '' }} class="rounded border-gray-300">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Update Template</button>
            </form>
        </div>
    </div>
</x-app-layout>
