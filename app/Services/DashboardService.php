<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\EmailQueue;
use App\Models\EmailLog;
use App\Models\AiGeneration;
use App\Models\Product;
use App\Models\FollowUp;
use App\Models\Notification;
use App\Services\AI\KimiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function __construct(
        private EmailSendingService $sendingService,
        private KimiService $kimiService
    ) {}

    public function getDashboardData(): array
    {
        $safe = fn(callable $cb, $default = []) => $this->safeQuery($cb, $default);

        return [
            'sendingService' => $this->sendingService,
            'sendingPaused' => $safe(fn() => $this->sendingService->isSendingPaused(), false),
            'dailySent' => $safe(fn() => $this->sendingService->getDailySentCount(), 0),
            'dailyLimit' => $safe(fn() => $this->sendingService->getDailyLimit(), 50),
            'hourlySent' => $safe(fn() => $this->sendingService->getHourlySentCount(), 0),
            'hourlyLimit' => $safe(fn() => $this->sendingService->getHourlyLimit(), 10),
            'withinSchedule' => $safe(fn() => $this->sendingService->isWithinSendingSchedule(), false),
            'isTestMode' => $safe(fn() => $this->sendingService->isTestMode(), false),
            'stats' => $safe(fn() => Cache::remember('dashboard.stats', 300, fn() => $this->getStats()), $this->defaultStats()),
            'funnel' => $safe(fn() => Cache::remember('dashboard.funnel', 300, fn() => $this->getFunnel())),
            'products' => $safe(fn() => Cache::remember('dashboard.products', 300, fn() => $this->getProductStats()), ['total' => 0, 'profitable' => 0, 'avg_margin' => 0]),
            'recentActivity' => $safe(fn() => Notification::where('user_id', auth()->id())->latest()->limit(8)->get(), collect()),
            'suggestedVendors' => $safe(fn() => $this->getSuggestedVendors(), collect()),
            'followupsScheduled' => $safe(fn() => FollowUp::where('status', 'scheduled')->count(), 0),
            'aiStats' => $safe(fn() => Cache::remember('dashboard.ai', 300, fn() => $this->getAiStats()), ['generated' => 0, 'regenerated' => 0, 'total_calls' => 0, 'cost' => 0]),
            'vendorBreakdown' => $safe(fn() => Cache::remember('dashboard.breakdown', 300, fn() => $this->getVendorBreakdown()), ['contacted' => 0, 'not_contacted' => 0, 'opted_out' => 0, 'invalid_email' => 0]),
            'emailChartData' => $safe(fn() => Cache::remember('dashboard.email_chart', 300, fn() => $this->getEmailChartData()), ['labels' => [], 'values' => []]),
            'vendorStatusData' => $safe(fn() => Cache::remember('dashboard.status_chart', 300, fn() => $this->getVendorStatusData()), ['labels' => [], 'values' => []]),
            'marginAlerts' => $safe(fn() => Cache::remember('dashboard.margin_alerts', 300, fn() => $this->getMarginAlerts()), ['unprofitable_count' => 0, 'low_margin_count' => 0, 'unprofitable' => collect(), 'low_margin' => collect()]),
            'followUpsDue' => $safe(fn() => Vendor::followUpDue()->with('products')->limit(10)->get(), collect()),
        ];
    }

    private function safeQuery(callable $cb, mixed $default = []): mixed
    {
        try {
            return $cb();
        } catch (\Throwable $e) {
            Log::warning('Dashboard query failed: ' . $e->getMessage());
            return $default;
        }
    }

    private function defaultStats(): array
    {
        return [
            'total_vendors' => 0,
            'active_vendors' => 0,
            'emails_sent' => 0,
            'pending' => 0,
            'failed' => 0,
            'followup_due' => 0,
        ];
    }

    private function getStats(): array
    {
        return [
            'total_vendors' => Vendor::count(),
            'active_vendors' => Vendor::active()->count(),
            'emails_sent' => EmailQueue::where('status', 'sent')->count(),
            'pending' => EmailQueue::where('status', 'pending')->count(),
            'failed' => EmailQueue::where('status', 'failed')->count(),
            'followup_due' => Vendor::followUpDue()->count(),
        ];
    }

    private function getFunnel(): array
    {
        $totalContacted = Vendor::whereNotNull('last_contacted_at')->count();
        $totalReplied = Vendor::whereIn('status', ['replied', 'interested', 'approved'])->count();
        $totalInterested = Vendor::whereIn('status', ['interested', 'approved'])->count();
        $totalApproved = Vendor::where('status', 'approved')->count();

        return [
            'total' => Vendor::count(),
            'contacted' => $totalContacted,
            'replied' => $totalReplied,
            'interested' => $totalInterested,
            'approved' => $totalApproved,
            'reply_rate' => $totalContacted > 0 ? round(($totalReplied / $totalContacted) * 100, 1) : 0,
        ];
    }

    private function getProductStats(): array
    {
        return Cache::remember('dashboard.product_stats', 300, function () {
            $total = Product::count();
            $profitable = Product::profitable()->count();
            $avgMargin = Product::avg('margin_percent') ?? 0;

            return [
                'total' => $total,
                'profitable' => $profitable,
                'avg_margin' => round((float)$avgMargin, 1),
            ];
        });
    }

    private function getSuggestedVendors(): \Illuminate\Database\Eloquent\Collection
    {
        return Vendor::active()
            ->where(function ($q) {
                $q->whereNull('last_contacted_at')
                  ->orWhere('next_follow_up', '<=', now()->toDateString());
            })
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->limit(5)
            ->get();
    }

    private function getAiStats(): array
    {
        return [
            'generated' => AiGeneration::where('action', 'generate_email')->count(),
            'regenerated' => AiGeneration::where('action', 'modify_email')->count(),
            'total_calls' => AiGeneration::count(),
            'cost' => AiGeneration::sum('estimated_cost') ?? 0,
        ];
    }

    private function getVendorBreakdown(): array
    {
        return [
            'contacted' => Vendor::whereNotNull('last_contacted_at')->count(),
            'not_contacted' => Vendor::notContacted()->count(),
            'opted_out' => Vendor::where('status', 'opted_out')->count(),
            'invalid_email' => Vendor::where('status', 'invalid_email')->count(),
        ];
    }

    private function getEmailChartData(): array
    {
        $data = EmailLog::selectRaw('DATE(sent_at) as date, COUNT(*) as count')
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return [
            'labels' => $data->keys()->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->values(),
            'values' => $data->values(),
        ];
    }

    private function getVendorStatusData(): array
    {
        $data = Vendor::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'labels' => $data->keys(),
            'values' => $data->values(),
        ];
    }

    private function getMarginAlerts(): array
    {
        $unprofitable = Product::where('net_profit', '<=', 0)
            ->where('status', 'active')
            ->with('vendor:id,brand_name')
            ->orderBy('margin_percent')
            ->limit(10)
            ->get();

        $lowMargin = Product::where('margin_percent', '>', 0)
            ->where('margin_percent', '<', 15)
            ->where('status', 'active')
            ->with('vendor:id,brand_name')
            ->orderBy('margin_percent')
            ->limit(10)
            ->get();

        return [
            'unprofitable_count' => $unprofitable->count(),
            'low_margin_count' => $lowMargin->count(),
            'unprofitable' => $unprofitable,
            'low_margin' => $lowMargin,
        ];
    }
}
