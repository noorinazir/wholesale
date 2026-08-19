<?php

use App\Livewire\Actions\Logout;
use App\Services\EmailSendingService;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $sendingPaused = false;

    public function mount(): void
    {
        $this->sendingPaused = app(EmailSendingService::class)->isSendingPaused();
    }

    public function togglePause(): void
    {
        $service = app(EmailSendingService::class);
        if ($this->sendingPaused) {
            $service->resumeSending();
            $this->sendingPaused = false;
        } else {
            $service->pauseSending();
            $this->sendingPaused = true;
        }
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: false);
    }
}; ?>

<div x-data="{ sidebarOpen: false }" class="flex">
    <!-- Mobile overlay -->
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed lg:static z-40 w-64 h-screen bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-transform duration-200 ease-in-out flex flex-col">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="font-bold text-gray-800 dark:text-gray-200 text-lg">Wholesale Outreach</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="chart">
                Dashboard
            </x-sidebar-link>

            <div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Vendors</div>
            <x-sidebar-link :href="route('vendors.index')" :active="request()->routeIs('vendors.*')" icon="users">
                All Vendors
            </x-sidebar-link>
            <x-sidebar-link :href="route('vendors.import')" :active="request()->routeIs('vendors.import')" icon="upload">
                Import Vendors
            </x-sidebar-link>
            <x-sidebar-link :href="route('suppression.index')" :active="request()->routeIs('suppression.*')" icon="ban">
                Suppression List
            </x-sidebar-link>

            <div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Campaigns</div>
            <x-sidebar-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')" icon="folder">
                All Campaigns
            </x-sidebar-link>
            <x-sidebar-link :href="route('campaigns.create')" :active="request()->routeIs('campaigns.create')" icon="plus">
                Create Campaign
            </x-sidebar-link>

            <div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Emails</div>
            <x-sidebar-link :href="route('emails.drafts')" :active="request()->routeIs('emails.drafts')" icon="document">
                Drafts
            </x-sidebar-link>
            <x-sidebar-link :href="route('emails.pending')" :active="request()->routeIs('emails.pending')" icon="clock">
                Pending Approval
            </x-sidebar-link>
            <x-sidebar-link :href="route('emails.scheduled')" :active="request()->routeIs('emails.scheduled')" icon="calendar">
                Scheduled
            </x-sidebar-link>
            <x-sidebar-link :href="route('emails.sent')" :active="request()->routeIs('emails.sent')" icon="check">
                Sent
            </x-sidebar-link>
            <x-sidebar-link :href="route('emails.failed')" :active="request()->routeIs('emails.failed')" icon="x">
                Failed
            </x-sidebar-link>
            <x-sidebar-link :href="route('emails.queue')" :active="request()->routeIs('emails.queue')" icon="list">
                Sending Queue
            </x-sidebar-link>
            <x-sidebar-link :href="route('emails.history')" :active="request()->routeIs('emails.history')" icon="archive">
                Email History
            </x-sidebar-link>

            <div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Tools</div>
            <x-sidebar-link :href="route('ai-assistant')" :active="request()->routeIs('ai-assistant')" icon="sparkles">
                AI Assistant
            </x-sidebar-link>
            <x-sidebar-link :href="route('templates.index')" :active="request()->routeIs('templates.*')" icon="template">
                Templates
            </x-sidebar-link>
            <x-sidebar-link :href="route('analytics')" :active="request()->routeIs('analytics')" icon="chart-bar">
                Analytics
            </x-sidebar-link>

            @can('manage-settings')
            <div class="pt-3 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Settings</div>
            <x-sidebar-link :href="route('settings.company')" :active="request()->routeIs('settings.company')" icon="building">
                Company
            </x-sidebar-link>
            <x-sidebar-link :href="route('settings.smtp')" :active="request()->routeIs('settings.smtp')" icon="mail">
                SMTP
            </x-sidebar-link>
            <x-sidebar-link :href="route('settings.ai')" :active="request()->routeIs('settings.ai')" icon="cpu">
                AI Configuration
            </x-sidebar-link>
            <x-sidebar-link :href="route('settings.sending')" :active="request()->routeIs('settings.sending')" icon="adjustments">
                Sending Limits
            </x-sidebar-link>
            <x-sidebar-link :href="route('settings.users')" :active="request()->routeIs('settings.users')" icon="user-group">
                Users
            </x-sidebar-link>
            <x-sidebar-link :href="route('settings.system')" :active="request()->routeIs('settings.system')" icon="cog">
                System
            </x-sidebar-link>
            <x-sidebar-link :href="route('settings.audit')" :active="request()->routeIs('settings.audit')" icon="clipboard">
                Audit Logs
            </x-sidebar-link>
            @endcan
        </nav>

        <!-- Emergency Controls -->
        <div class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0">
            <button wire:click="togglePause" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $sendingPaused ? 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' }}">
                @if($sendingPaused)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Sending Paused
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Sending Active
                @endif
            </button>
        </div>

        <!-- User Menu -->
        <div class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-semibold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</div>
                </div>
                <button wire:click="logout" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </div>
        </div>
    </aside>

    <!-- Mobile top bar -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center px-4">
        <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="ml-4 font-bold text-gray-800 dark:text-gray-200">Wholesale Outreach</span>
    </div>
    <div class="lg:hidden h-16"></div>
</div>
