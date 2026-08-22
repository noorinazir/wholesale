<div x-data="{ sidebarOpen: false, collapsed: { vendors: {{ request()->routeIs('vendors.*') || request()->routeIs('products.*') || request()->routeIs('suppression.*') ? 'false' : 'true' }}, finance: {{ request()->routeIs('finance.*') ? 'false' : 'true' }}, campaigns: {{ request()->routeIs('campaigns.*') ? 'false' : 'true' }}, emails: {{ request()->routeIs('emails.*') ? 'false' : 'true' }}, tools: {{ request()->routeIs('ai-assistant') || request()->routeIs('templates.*') || request()->routeIs('analytics') || request()->routeIs('reports.*') ? 'false' : 'true' }}, system: {{ request()->routeIs('settings.*') ? 'false' : 'true' }} }, showNotifications: false, searchQuery: '', searchResults: [], searching: false }" class="flex">
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:sticky top-0 z-40 w-56 h-screen bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-transform duration-200 ease-in-out flex flex-col">
        <div class="h-14 flex items-center px-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-md bg-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="font-bold text-gray-800 dark:text-gray-200 text-sm tracking-tight">Wholesale Outreach</span>
            </a>
            <div class="ml-auto relative">
                <button @click="showNotifications = !showNotifications" class="relative p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if($unreadCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </button>
                <div x-show="showNotifications" x-cloak @click.outside="showNotifications = false" x-transition class="absolute right-0 top-full mt-1 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-y-auto">
                    <div class="flex items-center justify-between px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Notifications</span>
                        @if($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-[10px] text-indigo-600 hover:underline">Mark all read</button>
                        </form>
                        @endif
                    </div>
                    @if($unreadNotifications->isNotEmpty())
                        @foreach($unreadNotifications as $notif)
                        <div class="px-3 py-2 border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <div class="flex items-start gap-2">
                                <div class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0 {{ $notif->type === 'error' ? 'bg-red-500' : ($notif->type === 'reply' ? 'bg-purple-500' : 'bg-blue-500') }}"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $notif->title }}</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">{{ $notif->message }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</div>
                                </div>
                                <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
                                    @csrf
                                    <button type="submit" class="text-gray-300 hover:text-gray-500 shrink-0">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="px-3 py-6 text-center">
                            <p class="text-xs text-gray-400">No new notifications</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Global Search -->
        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
            <div class="relative">
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="searchQuery" @input.debounce.300ms="searching = true; fetch('{{ route('search') }}?q=' + encodeURIComponent(searchQuery)).then(r => r.json()).then(d => { searchResults = d; searching = false; }).catch(() => searching = false)" placeholder="Search vendors, campaigns..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                <div x-show="searchResults.length > 0" x-cloak @click.outside="searchResults = []" x-transition class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-64 overflow-y-auto">
                    <template x-for="result in searchResults" :key="result.url">
                        <a :href="result.url" class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/30 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                            <span class="text-[9px] font-medium px-1.5 py-0.5 rounded-full shrink-0" :class="result.type === 'Vendor' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : result.type === 'Campaign' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'" x-text="result.type"></span>
                            <div class="min-w-0">
                                <div class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate" x-text="result.label"></div>
                                <div class="text-[10px] text-gray-400 truncate" x-text="result.sublabel"></div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-2 px-2.5 space-y-0.5">
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="chart">
                Dashboard
            </x-sidebar-link>

            <button @click="collapsed.vendors = !collapsed.vendors" class="w-full flex items-center justify-between pt-3 px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider hover:text-gray-600 dark:hover:text-gray-300">
                <span>Vendors</span>
                <svg class="w-3 h-3 transition-transform" :class="collapsed.vendors ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="!collapsed.vendors" x-transition class="space-y-0.5">
                <x-sidebar-link :href="route('vendors.index')" :active="request()->routeIs('vendors.index') || request()->routeIs('vendors.create') || request()->routeIs('vendors.show') || request()->routeIs('vendors.edit')" icon="users">
                    All Vendors
                </x-sidebar-link>
                <x-sidebar-link :href="route('products.index')" :active="request()->routeIs('products.*')" icon="folder">
                    Products
                </x-sidebar-link>
                @can('manage-vendors')
                <x-sidebar-link :href="route('vendors.import')" :active="request()->routeIs('vendors.import')" icon="upload">
                    Import
                </x-sidebar-link>
                @endcan
                <x-sidebar-link :href="route('suppression.index')" :active="request()->routeIs('suppression.*')" icon="ban">
                    Suppression List
                </x-sidebar-link>
            </div>

            @canany(['manage-finance', 'view-finance'])
            <button @click="collapsed.finance = !collapsed.finance" class="w-full flex items-center justify-between pt-3 px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider hover:text-gray-600 dark:hover:text-gray-300">
                <span>Finance</span>
                <svg class="w-3 h-3 transition-transform" :class="collapsed.finance ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="!collapsed.finance" x-transition class="space-y-0.5">
                <x-sidebar-link :href="route('finance.dashboard')" :active="request()->routeIs('finance.dashboard')" icon="chart">
                    Finance Dashboard
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.po.index')" :active="request()->routeIs('finance.po.*')" icon="clipboard">
                    Purchase Orders
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.sales.index')" :active="request()->routeIs('finance.sales.*')" icon="cart">
                    Amazon Sales
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.tracking')" :active="request()->routeIs('finance.tracking')" icon="truck">
                    Order Tracking
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.expenses.index')" :active="request()->routeIs('finance.expenses.*')" icon="receipt">
                    Expenses
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.settlements.index')" :active="request()->routeIs('finance.settlements.*')" icon="upload">
                    Amazon Settlements
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.pnl')" :active="request()->routeIs('finance.pnl')" icon="calculator">
                    Profit &amp; Loss
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.reconciliation')" :active="request()->routeIs('finance.reconciliation')" icon="check-circle">
                    Reconciliation
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.ai-analysis')" :active="request()->routeIs('finance.ai-analysis')" icon="sparkles">
                    AI Analysis
                </x-sidebar-link>
                <x-sidebar-link :href="route('finance.tax.index')" :active="request()->routeIs('finance.tax.*')" icon="currency">
                    Tax Rates
                </x-sidebar-link>
            </div>
            @endcanany

            @canany(['manage-campaigns', 'view-campaigns'])
            <button @click="collapsed.campaigns = !collapsed.campaigns" class="w-full flex items-center justify-between pt-3 px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider hover:text-gray-600 dark:hover:text-gray-300">
                <span>Campaigns</span>
                <svg class="w-3 h-3 transition-transform" :class="collapsed.campaigns ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="!collapsed.campaigns" x-transition class="space-y-0.5">
                <x-sidebar-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')" icon="folder">
                    Campaigns
                </x-sidebar-link>
            </div>
            @endcanany

            @canany(['manage-emails', 'view-emails'])
            <button @click="collapsed.emails = !collapsed.emails" class="w-full flex items-center justify-between pt-3 px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider hover:text-gray-600 dark:hover:text-gray-300">
                <span>Emails</span>
                <svg class="w-3 h-3 transition-transform" :class="collapsed.emails ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="!collapsed.emails" x-transition class="space-y-0.5">
                <x-sidebar-link :href="route('emails.drafts')" :active="request()->routeIs('emails.drafts')" icon="document">
                    Drafts
                </x-sidebar-link>
                <x-sidebar-link :href="route('emails.pending')" :active="request()->routeIs('emails.pending')" icon="clock">
                    Pending Approval
                </x-sidebar-link>
                <x-sidebar-link :href="route('emails.queue')" :active="request()->routeIs('emails.queue')" icon="list">
                    Sending Queue
                </x-sidebar-link>
                <x-sidebar-link :href="route('emails.sent')" :active="request()->routeIs('emails.sent')" icon="check">
                    Sent
                </x-sidebar-link>
                <x-sidebar-link :href="route('emails.failed')" :active="request()->routeIs('emails.failed')" icon="x">
                    Failed
                </x-sidebar-link>
                <x-sidebar-link :href="route('emails.history')" :active="request()->routeIs('emails.history')" icon="archive">
                    History
                </x-sidebar-link>
            </div>
            @endcanany

            @canany(['manage-emails', 'view-emails', 'view-reports'])
            <button @click="collapsed.tools = !collapsed.tools" class="w-full flex items-center justify-between pt-3 px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider hover:text-gray-600 dark:hover:text-gray-300">
                <span>Tools</span>
                <svg class="w-3 h-3 transition-transform" :class="collapsed.tools ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="!collapsed.tools" x-transition class="space-y-0.5">
                @canany(['manage-emails', 'view-emails'])
                <x-sidebar-link :href="route('ai-assistant')" :active="request()->routeIs('ai-assistant')" icon="sparkles">
                    AI Assistant
                </x-sidebar-link>
                <x-sidebar-link :href="route('templates.index')" :active="request()->routeIs('templates.*')" icon="template">
                    Templates
                </x-sidebar-link>
                @endcanany
                @can('view-reports')
                <x-sidebar-link :href="route('analytics')" :active="request()->routeIs('analytics')" icon="chart-bar">
                    Analytics
                </x-sidebar-link>
                <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="chart">
                    Reports
                </x-sidebar-link>
                @endcan
            </div>
            @endcanany

            @can('manage-settings')
            <button @click="collapsed.system = !collapsed.system" class="w-full flex items-center justify-between pt-3 px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider hover:text-gray-600 dark:hover:text-gray-300">
                <span>System</span>
                <svg class="w-3 h-3 transition-transform" :class="collapsed.system ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="!collapsed.system" x-transition class="space-y-0.5">
                <x-sidebar-link :href="route('settings.company')" :active="request()->routeIs('settings.*')" icon="cog">
                    Settings
                </x-sidebar-link>
            </div>
            @endcan
        </nav>

        <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 shrink-0 space-y-2">
            <form method="POST" action="{{ route('settings.sending') }}">
                @csrf
                @if($sendingPaused)
                    <input type="hidden" name="sending_paused" value="0">
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 border border-green-200 dark:border-green-800">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Resume Sending
                    </button>
                @else
                    <input type="hidden" name="sending_paused" value="1">
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 border border-red-200 dark:border-red-800">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pause Sending
                    </button>
                @endif
            </form>

            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white text-xs font-semibold shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1" title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:hidden fixed top-0 left-0 right-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-14 flex items-center px-4">
        <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="ml-3 font-bold text-gray-800 dark:text-gray-200 text-sm">Wholesale Outreach</span>
    </div>
    <div class="lg:hidden h-14"></div>
</div>
