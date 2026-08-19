<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Audit Logs</h2>
    </x-slot>

    @php
    use Spatie\Activitylog\Models\Activity;
    $logs = Activity::latest()->paginate(25);
    @endphp

    <div class="space-y-6">
        <x-settings-tabs active="audit" />
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Object</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-300">{{ $log->causer?->name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->description }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->properties['object'] ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->properties['ip'] ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8">
                        <x-empty-state icon="archive" title="No audit logs" description="User activity logs will appear here." />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
