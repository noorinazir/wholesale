<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\Notification;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\SmtpSetting;
use App\Models\Vendor;
use App\Policies\CampaignPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\VendorPolicy;
use App\Services\EmailSendingService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\AI\KimiService::class, function ($app) {
            return new \App\Services\AI\KimiService();
        });

        $this->app->bind(\App\Services\AmazonFinancialImportService::class, function ($app) {
            return new \App\Services\AmazonFinancialImportService(
                $app->make(\App\Services\AI\KimiService::class)
            );
        });

        $this->app->bind(\App\Services\AI\AiFinancialAnalysisService::class, function ($app) {
            return new \App\Services\AI\AiFinancialAnalysisService(
                $app->make(\App\Services\AI\KimiService::class),
                $app->make(\App\Services\ProfitLossService::class)
            );
        });
    }

    public function boot(): void
    {
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(EmailTemplate::class, EmailTemplatePolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);

        View::composer('partials.sidebar', function ($view) {
            $user = auth()->user();
            $sendingService = app(EmailSendingService::class);

            $unreadNotifications = collect();
            $unreadCount = 0;

            if ($user) {
                try {
                    $unreadNotifications = Notification::where('user_id', $user->id)
                        ->whereNull('read_at')
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();

                    $unreadCount = $unreadNotifications->count();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Notifications table not available: ' . $e->getMessage());
                }
            }

            try {
                $sendingPaused = $sendingService->isSendingPaused();
            } catch (\Throwable $e) {
                $sendingPaused = false;
            }

            $view->with([
                'sendingService' => $sendingService,
                'sendingPaused' => $sendingPaused,
                'unreadNotifications' => $unreadNotifications,
                'unreadCount' => $unreadCount,
            ]);
        });

        View::composer('settings.company', function ($view) {
            $company = Company::where('is_active', true)->first() ?? new Company();
            $documents = $company->exists
                ? $company->documents()->orderBy('type')->get()
                : collect();

            $view->with([
                'companyProfile' => $company,
                'companyDocuments' => $documents,
            ]);
        });

        View::composer('settings.smtp', function ($view) {
            $view->with('activeSmtpSetting', SmtpSetting::where('is_active', true)->first() ?? new SmtpSetting());
        });
    }
}
