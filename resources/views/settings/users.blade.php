<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">User Management</h2>
        </div>
    </x-slot>

    @php
    $users = \App\Models\User::latest()->paginate(25);
    @endphp

    <div class="space-y-6">
        <x-settings-tabs active="users" />
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Add User</h3>
            <form method="POST" action="{{ route('settings.users') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <input type="text" name="name" placeholder="Name" value="{{ old('name') }}" required class="rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required class="rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                <input type="password" name="password" placeholder="Min 8 chars: uppercase, lowercase, number, special char" required class="rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                <select name="role" class="rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                    <option value="administrator" {{ old('role') === 'administrator' ? 'selected' : '' }}>Administrator</option>
                    <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>Viewer</option>
                </select>
                <button type="submit" class="md:col-span-4 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Add User</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Login</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-sm"><span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700">{{ ucfirst($user->role) }}</span></td>
                            <td class="px-4 py-3 text-sm">{{ $user->is_active ? '✓' : '✗' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $user->last_login_at?->format('M d, Y H:i') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8">
                            <x-empty-state icon="users" title="No users" description="Add team members to manage the system." />
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
