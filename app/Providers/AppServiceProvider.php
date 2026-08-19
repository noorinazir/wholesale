<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\SystemSetting;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\AI\KimiService::class, function ($app) {
            \App\Models\SystemSetting::flushCache();
            return new \App\Services\AI\KimiService();
        });
    }

    public function boot(): void
    {
        Gate::define('manage-settings', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-vendors', function ($user) {
            return $user->isStaff();
        });

        Gate::define('manage-campaigns', function ($user) {
            return $user->isManager();
        });

        Gate::define('send-emails', function ($user) {
            return $user->isManager();
        });
    }
}
