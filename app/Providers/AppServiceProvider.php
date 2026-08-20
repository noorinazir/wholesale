<?php

namespace App\Providers;

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
        // Authorization is handled entirely by Spatie Permission package.
        // Permissions are seeded in DatabaseSeeder and assigned to roles there.
    }
}
