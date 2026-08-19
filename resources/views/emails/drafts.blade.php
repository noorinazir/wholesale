<x-app-layout>
    @php
    $emails = \App\Models\GeneratedEmail::with('vendor')->where('status', 'draft')->latest()->paginate(25);
    @endphp

    <x-page-header title="Email Drafts" :back="route('dashboard')" :count="$emails->total() . ' total'" />

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AI Model</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($emails as $email)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $email->vendor?->brand_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $email->subject }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($email->tone) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $email->ai_model ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $email->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end">
                                <a href="{{ route('emails.preview', $email->id) }}" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors" title="Preview">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8">
                        <x-empty-state icon="document" title="No drafts yet" description="Generated emails will appear here for review before sending." actionText="Go to AI Assistant" actionHref="{{ route('ai-assistant') }}" />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($emails->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $emails->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
