<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\EmailQueue;
use App\Models\Vendor;
use App\Models\AiGeneration;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    public function getAnalyticsData(): array
    {
        return [
            'overview' => Cache::remember('analytics.overview', 300, fn() => $this->getOverview()),
            'dailyVolume' => Cache::remember('analytics.daily', 300, fn() => $this->getDailyVolume()),
            'successFail' => Cache::remember('analytics.success_fail', 300, fn() => $this->getSuccessFail()),
            'categoryData' => Cache::remember('analytics.category', 300, fn() => $this->getCategoryData()),
            'aiUsage' => Cache::remember('analytics.ai_usage', 300, fn() => $this->getAiUsage()),
        ];
    }

    private function getOverview(): array
    {
        return [
            'total_vendors' => Vendor::count(),
            'total_sent' => EmailQueue::where('status', 'sent')->count(),
            'total_queue' => EmailQueue::count(),
            'ai_cost' => AiGeneration::sum('estimated_cost') ?? 0,
        ];
    }

    private function getDailyVolume(): array
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

    private function getSuccessFail(): array
    {
        return [
            'sent' => EmailQueue::where('status', 'sent')->count(),
            'failed' => EmailQueue::where('status', 'failed')->count(),
        ];
    }

    private function getCategoryData(): array
    {
        $data = Vendor::selectRaw('product_category, COUNT(*) as count')
            ->whereNotNull('product_category')
            ->groupBy('product_category')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'product_category');

        return [
            'labels' => $data->keys(),
            'values' => $data->values(),
        ];
    }

    private function getAiUsage(): array
    {
        $data = AiGeneration::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action');

        return [
            'labels' => $data->keys()->map(fn($a) => ucfirst(str_replace('_', ' ', $a))),
            'values' => $data->values(),
        ];
    }
}
