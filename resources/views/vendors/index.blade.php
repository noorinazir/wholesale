<x-app-layout>

    <x-page-header title="Vendors" :back="route('dashboard')" :count="$vendors->total() . ' total'" x-data="{ showBulk: false, selectedIds: [], bulkAction: '', showBulkOptions: false }">
        <x-button variant="secondary" href="{{ route('vendors.export') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </x-button>
        <x-button variant="success" @click="$dispatch('open-add-vendor')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Vendor
        </x-button>
        <x-button variant="primary" href="{{ route('vendors.import') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Import
        </x-button>
    </x-page-header>

    <div class="space-y-4">
        <!-- Summary Stats -->
        @php
        $sc = $statusCounts ?? [];
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Vendors</div>
                    <div class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $vendors->total() }}</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">New</div>
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $sc['new'] ?? 0 }}</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Contacted</div>
                    <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ $sc['contacted'] ?? 0 }}</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 3v-3"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Replied</div>
                    <div class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ $sc['replied'] ?? 0 }}</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Interested / Approved</div>
                    <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ ($sc['interested'] ?? 0) + ($sc['approved'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search brand, company, contact, email..." class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <select name="status" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="priority" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $val => $label)
                    <option value="{{ $val }}" @selected(request('priority') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="email_status" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Email Statuses</option>
                    @foreach($emailStatuses as $val => $label)
                    <option value="{{ $val }}" @selected(request('email_status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="country" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Countries</option>
                    @foreach($countries as $val => $label)
                    <option value="{{ $val }}" @selected(request('country') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="sort" class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="latest" @selected(request('sort') === 'latest')>Latest</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    <option value="name" @selected(request('sort') === 'name')>Name A-Z</option>
                    <option value="status" @selected(request('sort') === 'status')>By Status</option>
                    <option value="priority" @selected(request('sort') === 'priority')>By Priority</option>
                </select>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'email_status', 'priority', 'country', 'sort']))
                <a href="{{ route('vendors.index') }}" class="px-3 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors">Clear</a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Bulk Action Bar -->
            <div x-show="selectedIds.length > 0" x-cloak x-transition class="bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-200 dark:border-indigo-800 px-4 py-2.5 flex items-center gap-3">
                <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300" x-text="selectedIds.length + ' selected'"></span>
                <form method="POST" action="{{ route('vendors.bulk') }}" class="flex items-center gap-2">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="vendor_ids[]" :value="id">
                    </template>
                    <select name="bulk_action" x-model="bulkAction" @change="if(bulkAction === 'set_status' || bulkAction === 'set_priority' || bulkAction === 'assign_campaign') showBulkOptions = true; else showBulkOptions = false" class="text-xs rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <option value="">Choose action...</option>
                        <option value="set_status">Set Status</option>
                        <option value="set_priority">Set Priority</option>
                        <option value="assign_campaign">Assign to Campaign</option>
                        <option value="delete">Archive</option>
                    </select>
                    <select name="status" x-show="bulkAction === 'set_status'" x-cloak class="text-xs rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @foreach($statuses as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="priority" x-show="bulkAction === 'set_priority'" x-cloak class="text-xs rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @foreach($priorities as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="campaign_id" x-show="bulkAction === 'assign_campaign'" x-cloak class="text-xs rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-1 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg" x-show="bulkAction !== ''" x-cloak>Apply</button>
                </form>
                <button @click="selectedIds = []" class="text-xs text-gray-500 hover:text-gray-700 ml-auto">Clear</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3.5 w-10">
                                <input type="checkbox" @change="$refs.vendorCheckboxes.forEach(cb => { cb.checked = $event.target.checked; if(cb.checked) selectedIds.push(cb.value); else selectedIds = selectedIds.filter(id => id !== cb.value) })" class="rounded border-gray-300">
                            </th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Brand</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Contact</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Priority</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($vendors as $vendor)
                        @php
                        $sColor = $statusColors[$vendor->status] ?? 'gray';
                        $eColor = $emailStatusColors[$vendor->email_status] ?? 'gray';
                        $pColor = $priorityColors[$vendor->priority] ?? 'gray';
                        $colorMap = [
                            'blue' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400', 'dot' => 'bg-blue-500'],
                            'indigo' => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-400', 'dot' => 'bg-indigo-500'],
                            'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-400', 'dot' => 'bg-purple-500'],
                            'green' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400', 'dot' => 'bg-green-500'],
                            'red' => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400', 'dot' => 'bg-red-500'],
                            'yellow' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400', 'dot' => 'bg-yellow-500'],
                            'orange' => ['bg' => 'bg-orange-50 dark:bg-orange-900/30', 'text' => 'text-orange-700 dark:text-orange-400', 'dot' => 'bg-orange-500'],
                            'gray' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => 'bg-gray-400'],
                        ];
                        $sc = $colorMap[$sColor] ?? $colorMap['gray'];
                        $ec = $colorMap[$eColor] ?? $colorMap['gray'];
                        $pc = $colorMap[$pColor] ?? $colorMap['gray'];
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                            <td class="px-4 py-3">
                                <input type="checkbox" x-ref="vendorCheckboxes" value="{{ $vendor->id }}" @change="$event.target.checked ? selectedIds.push($event.target.value) : selectedIds = selectedIds.filter(id => id !== $event.target.value)" class="rounded border-gray-300">
                            </td>
                            <!-- Brand -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/40 dark:to-indigo-800/40 flex items-center justify-center shrink-0 ring-1 ring-indigo-100 dark:ring-indigo-900/30">
                                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ strtoupper(substr($vendor->brand_name ?? '?', 0, 2)) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('vendors.show', $vendor->id) }}" class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $vendor->brand_name }}</a>
                                        @if($vendor->company_name)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $vendor->company_name }}</div>
                                        @endif
                                        @if($vendor->country)
                                        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ $countries[$vendor->country] ?? $vendor->country }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <!-- Contact -->
                            <td class="px-4 py-3">
                                @if($vendor->contact_name)
                                <div class="text-sm text-gray-700 dark:text-gray-300">{{ $vendor->contact_name }}</div>
                                @endif
                                @if($vendor->contact_email)
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span class="truncate">{{ $vendor->contact_email }}</span>
                                </div>
                                @endif
                                @if($vendor->phone)
                                <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    {{ $vendor->phone }}
                                </div>
                                @endif
                                @if(!$vendor->contact_name && !$vendor->contact_email && !$vendor->phone)
                                <span class="text-sm text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <!-- Category -->
                            <td class="px-4 py-3">
                                @if($vendor->product_category)
                                <span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $categories[$vendor->product_category] ?? $vendor->product_category }}</span>
                                @else
                                <span class="text-sm text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <!-- Status -->
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('vendors.status', $vendor->id) }}" class="inline-block">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" class="text-xs rounded-full border-0 py-1 pl-2 pr-6 cursor-pointer focus:ring-2 focus:ring-indigo-500 {{ $sc['bg'] }} {{ $sc['text'] }}">
                                        @foreach($statuses as $val => $label)
                                        <option value="{{ $val }}" @selected($vendor->status === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <!-- Email Status -->
                            <td class="px-4 py-3 text-center">
                                @if($vendor->email_status && $vendor->email_status !== 'not_sent')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $ec['bg'] }} {{ $ec['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $ec['dot'] }}"></span>
                                    {{ $emailStatuses[$vendor->email_status] ?? ucfirst($vendor->email_status) }}
                                </span>
                                @else
                                <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <!-- Priority -->
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('vendors.priority', $vendor->id) }}" class="inline-block">
                                    @csrf
                                    <select name="priority" onchange="this.form.submit()" class="text-xs rounded-full border-0 py-1 pl-2 pr-6 cursor-pointer focus:ring-2 focus:ring-indigo-500 {{ $pc['bg'] }} {{ $pc['text'] }}">
                                        @foreach($priorities as $val => $label)
                                        <option value="{{ $val }}" @selected($vendor->priority === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <!-- Actions -->
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('vendors.show', $vendor->id) }}" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('vendors.edit', $vendor->id) }}" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16">
                                <div class="text-center">
                                    <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">No vendors found</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add vendors manually or import from a CSV file to get started.</p>
                                    <div class="flex items-center justify-center gap-2 mt-4">
                                        <button @click="$dispatch('open-add-vendor')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Add Vendor
                                        </button>
                                        <a href="{{ route('vendors.import') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                            Import CSV
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($vendors->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $vendors->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Add Vendor Modal -->
    <div x-data="{ show: false }" @open-add-vendor.window="show = true" @keydown.escape.window="show = false" x-cloak>
        <div x-show="show" x-transition.opacity class="fixed inset-0 z-50 bg-black/50" @click="show = false"></div>
        <div x-show="show" x-transition
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @click.self="show = false">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Add New Vendor</h3>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('vendors.create') }}" class="p-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-3 gap-y-2.5">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Brand Name <span class="text-red-500">*</span></label>
                            <input type="text" name="brand_name" required placeholder="Acme Pet Supplies" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Contact Email</label>
                            <input type="email" name="contact_email" placeholder="name@company.com" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Phone</label>
                            <input type="text" name="phone" placeholder="+1 555-0100" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Website</label>
                            <input type="text" name="website" placeholder="https://..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Company Name</label>
                            <input type="text" name="company_name" placeholder="Legal entity" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Contact Name</label>
                            <input type="text" name="contact_name" placeholder="John Doe" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Category</label>
                            <select name="product_category" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select...</option>
                                @foreach($categories as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Country</label>
                            <select name="country" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select...</option>
                                @foreach($countries as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Priority</label>
                            <select name="priority" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Contact Source</label>
                            <select name="contact_source" class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select...</option>
                                @foreach($sources as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-0.5">Notes</label>
                            <input type="text" name="notes" placeholder="Any additional context..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-3 mt-3 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="show = false" class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">Cancel</button>
                        <button type="submit" name="add_another" value="1" class="px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-lg transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Save & Add Another
                        </button>
                        <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Create Vendor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
