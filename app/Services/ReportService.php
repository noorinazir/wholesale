<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\Campaign;
use App\Models\EmailQueue;
use App\Models\EmailLog;
use App\Models\EmailReply;
use App\Models\AiGeneration;
use App\Models\FollowUp;
use App\Models\SuppressionList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReportService
{
    public function getOverviewReport(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->subDays(30)->startOfDay();
        $to = $to ?? now()->endOfDay();

        return [
            'kpis' => $this->getKpis($from, $to),
            'emailFunnel' => $this->getEmailFunnel($from, $to),
            'dailyVolume' => $this->getDailyVolume($from, $to),
            'successFailDaily' => $this->getSuccessFailDaily($from, $to),
            'vendorStatusDistribution' => $this->getVendorStatusDistribution(),
            'topCampaigns' => $this->getTopCampaigns($from, $to),
            'replyTrend' => $this->getReplyTrend($from, $to),
            'aiCostBreakdown' => $this->getAiCostBreakdown($from, $to),
            'geoDistribution' => $this->getGeoDistribution(),
            'followUpStats' => $this->getFollowUpStats($from, $to),
            'categoryPerformance' => $this->getCategoryPerformance($from, $to),
            'suppressionStats' => $this->getSuppressionStats(),
        ];
    }

    public function getCampaignReport(Campaign $campaign): array
    {
        $vendorIds = $campaign->vendors()->pluck('vendors.id');

        $queueItems = EmailQueue::where('campaign_id', $campaign->id)->get();
        $sent = $queueItems->where('status', 'sent')->count();
        $failed = $queueItems->where('status', 'failed')->count();
        $scheduled = $queueItems->whereIn('status', ['scheduled', 'pending'])->count();
        $total = $queueItems->count();

        $replied = EmailReply::whereIn('vendor_id', $vendorIds)->count();
        $interested = Vendor::whereIn('id', $vendorIds)->whereIn('status', ['interested', 'approved'])->count();
        $approved = Vendor::whereIn('id', $vendorIds)->where('status', 'approved')->count();

        $aiCost = AiGeneration::whereHas('generatedEmail', function ($q) use ($campaign) {
            $q->where('campaign_id', $campaign->id);
        })->sum('estimated_cost') ?? 0;

        $aiCalls = AiGeneration::whereHas('generatedEmail', function ($q) use ($campaign) {
            $q->where('campaign_id', $campaign->id);
        })->count();

        $followUps = FollowUp::where('campaign_id', $campaign->id)->get();
        $followUpSent = $followUps->where('status', 'sent')->count();
        $followUpScheduled = $followUps->where('status', 'scheduled')->count();
        $followUpCancelled = $followUps->where('status', 'cancelled')->count();

        $pivotStats = $campaign->vendors()
            ->selectRaw('campaign_vendors.status, COUNT(*) as count')
            ->groupBy('campaign_vendors.status')
            ->pluck('count', 'status');

        $dailySending = EmailLog::where('campaign_id', $campaign->id)
            ->where('status', 'sent')
            ->selectRaw('DATE(sent_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return [
            'campaign' => $campaign,
            'summary' => [
                'total_emails' => $total,
                'sent' => $sent,
                'failed' => $failed,
                'scheduled' => $scheduled,
                'replied' => $replied,
                'interested' => $interested,
                'approved' => $approved,
                'reply_rate' => $sent > 0 ? round(($replied / $sent) * 100, 1) : 0,
                'interest_rate' => $sent > 0 ? round(($interested / $sent) * 100, 1) : 0,
                'approval_rate' => $sent > 0 ? round(($approved / $sent) * 100, 1) : 0,
                'success_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 0,
                'ai_cost' => $aiCost,
                'ai_calls' => $aiCalls,
            ],
            'pivotStats' => $pivotStats,
            'followUpStats' => [
                'total' => $followUps->count(),
                'sent' => $followUpSent,
                'scheduled' => $followUpScheduled,
                'cancelled' => $followUpCancelled,
            ],
            'dailySending' => [
                'labels' => $dailySending->keys()->map(fn($d) => Carbon::parse($d)->format('M d'))->values(),
                'values' => $dailySending->values(),
            ],
        ];
    }

    private function getKpis(Carbon $from, Carbon $to): array
    {
        $sent = EmailQueue::where('status', 'sent')->whereBetween('sent_at', [$from, $to])->count();
        $failed = EmailQueue::where('status', 'failed')->whereBetween('updated_at', [$from, $to])->count();
        $replied = EmailReply::whereBetween('received_at', [$from, $to])->count();
        $newVendors = Vendor::whereBetween('created_at', [$from, $to])->count();
        $aiCost = AiGeneration::whereBetween('created_at', [$from, $to])->sum('estimated_cost') ?? 0;
        $aiCalls = AiGeneration::whereBetween('created_at', [$from, $to])->count();

        $contacted = Vendor::whereNotNull('last_contacted_at')
            ->whereBetween('last_contacted_at', [$from, $to])
            ->count();

        $approved = Vendor::where('status', 'approved')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        return [
            'emails_sent' => $sent,
            'emails_failed' => $failed,
            'replies_received' => $replied,
            'new_vendors' => $newVendors,
            'vendors_contacted' => $contacted,
            'approved_vendors' => $approved,
            'reply_rate' => $sent > 0 ? round(($replied / $sent) * 100, 1) : 0,
            'success_rate' => ($sent + $failed) > 0 ? round(($sent / ($sent + $failed)) * 100, 1) : 0,
            'ai_cost' => round((float) $aiCost, 4),
            'ai_calls' => $aiCalls,
        ];
    }

    private function getEmailFunnel(Carbon $from, Carbon $to): array
    {
        $totalVendors = Vendor::count();
        $contacted = Vendor::whereNotNull('last_contacted_at')->count();
        $replied = Vendor::whereIn('status', ['replied', 'interested', 'approved'])->count();
        $interested = Vendor::whereIn('status', ['interested', 'approved'])->count();
        $approved = Vendor::where('status', 'approved')->count();

        return [
            'total' => $totalVendors,
            'contacted' => $contacted,
            'replied' => $replied,
            'interested' => $interested,
            'approved' => $approved,
            'contact_rate' => $totalVendors > 0 ? round(($contacted / $totalVendors) * 100, 1) : 0,
            'reply_rate' => $contacted > 0 ? round(($replied / $contacted) * 100, 1) : 0,
            'interest_rate' => $replied > 0 ? round(($interested / $replied) * 100, 1) : 0,
            'approval_rate' => $interested > 0 ? round(($approved / $interested) * 100, 1) : 0,
        ];
    }

    private function getDailyVolume(Carbon $from, Carbon $to): array
    {
        $data = EmailLog::selectRaw('DATE(sent_at) as date, COUNT(*) as count')
            ->where('status', 'sent')
            ->whereBetween('sent_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $labels = [];
        $values = [];
        $period = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        while ($period <= $end) {
            $key = $period->toDateString();
            $labels[] = $period->format('M d');
            $values[] = $data->get($key, 0);
            $period->addDay();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function getSuccessFailDaily(Carbon $from, Carbon $to): array
    {
        $sent = EmailQueue::selectRaw('DATE(sent_at) as date, COUNT(*) as count')
            ->where('status', 'sent')
            ->whereBetween('sent_at', [$from, $to])
            ->groupBy('date')
            ->pluck('count', 'date');

        $failed = EmailQueue::selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->where('status', 'failed')
            ->whereBetween('updated_at', [$from, $to])
            ->groupBy('date')
            ->pluck('count', 'date');

        $labels = [];
        $sentValues = [];
        $failedValues = [];
        $period = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        while ($period <= $end) {
            $key = $period->toDateString();
            $labels[] = $period->format('M d');
            $sentValues[] = $sent->get($key, 0);
            $failedValues[] = $failed->get($key, 0);
            $period->addDay();
        }

        return [
            'labels' => $labels,
            'sent' => $sentValues,
            'failed' => $failedValues,
        ];
    }

    private function getVendorStatusDistribution(): array
    {
        $data = Vendor::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->pluck('count', 'status');

        return [
            'labels' => $data->keys()->map(fn($s) => ucfirst(str_replace('_', ' ', $s)))->values(),
            'values' => $data->values(),
            'raw' => $data,
        ];
    }

    private function getTopCampaigns(Carbon $from, Carbon $to): Collection
    {
        $campaigns = Campaign::withCount([
            'emailQueueItems as sent_count' => fn($q) => $q->where('status', 'sent'),
            'emailQueueItems as failed_count' => fn($q) => $q->where('status', 'failed'),
            'emailQueueItems as total_count',
        ])->get();

        if ($campaigns->isEmpty()) {
            return collect();
        }

        $campaignIds = $campaigns->pluck('id');

        $replyCounts = EmailReply::selectRaw('campaign_vendors.campaign_id, COUNT(*) as count')
            ->join('vendors', 'email_replies.vendor_id', '=', 'vendors.id')
            ->join('campaign_vendors', 'vendors.id', '=', 'campaign_vendors.vendor_id')
            ->whereIn('campaign_vendors.campaign_id', $campaignIds)
            ->whereBetween('email_replies.received_at', [$from, $to])
            ->groupBy('campaign_vendors.campaign_id')
            ->pluck('count', 'campaign_id');

        $approvedCounts = Vendor::selectRaw('campaign_vendors.campaign_id, COUNT(*) as count')
            ->join('campaign_vendors', 'vendors.id', '=', 'campaign_vendors.vendor_id')
            ->whereIn('campaign_vendors.campaign_id', $campaignIds)
            ->where('vendors.status', 'approved')
            ->groupBy('campaign_vendors.campaign_id')
            ->pluck('count', 'campaign_id');

        return $campaigns->map(function ($c) use ($replyCounts, $approvedCounts) {
            $replied = $replyCounts->get($c->id, 0);
            $approved = $approvedCounts->get($c->id, 0);

            return [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'sent' => $c->sent_count,
                'failed' => $c->failed_count,
                'total' => $c->total_count,
                'replied' => $replied,
                'approved' => $approved,
                'reply_rate' => $c->sent_count > 0 ? round(($replied / $c->sent_count) * 100, 1) : 0,
                'success_rate' => $c->total_count > 0 ? round(($c->sent_count / $c->total_count) * 100, 1) : 0,
            ];
        })->sortByDesc('sent')->values()->take(10);
    }

    private function getReplyTrend(Carbon $from, Carbon $to): array
    {
        $data = EmailReply::selectRaw('DATE(received_at) as date, COUNT(*) as count')
            ->whereBetween('received_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $labels = [];
        $values = [];
        $period = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        while ($period <= $end) {
            $key = $period->toDateString();
            $labels[] = $period->format('M d');
            $values[] = $data->get($key, 0);
            $period->addDay();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function getAiCostBreakdown(Carbon $from, Carbon $to): array
    {
        $data = AiGeneration::selectRaw('action, COUNT(*) as calls, SUM(estimated_cost) as cost, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('action')
            ->get();

        return [
            'labels' => $data->pluck('action')->map(fn($a) => ucfirst(str_replace('_', ' ', $a)))->values(),
            'calls' => $data->pluck('calls')->values(),
            'costs' => $data->pluck('cost')->map(fn($c) => round((float) $c, 4))->values(),
            'total_cost' => round((float) $data->sum('cost'), 4),
            'total_calls' => $data->sum('calls'),
            'total_input_tokens' => $data->sum('input_tokens'),
            'total_output_tokens' => $data->sum('output_tokens'),
        ];
    }

    private function getGeoDistribution(): array
    {
        $data = Vendor::selectRaw('country, COUNT(*) as count')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(15)
            ->pluck('count', 'country');

        return [
            'labels' => $data->keys()->values(),
            'values' => $data->values(),
        ];
    }

    private function getFollowUpStats(Carbon $from, Carbon $to): array
    {
        $total = FollowUp::whereBetween('created_at', [$from, $to])->count();
        $sent = FollowUp::where('status', 'sent')->whereBetween('sent_at', [$from, $to])->count();
        $scheduled = FollowUp::where('status', 'scheduled')->count();
        $cancelled = FollowUp::where('status', 'cancelled')->whereBetween('updated_at', [$from, $to])->count();

        $followUpReplies = Vendor::whereHas('followUps', fn($q) => $q->where('status', 'sent'))
            ->whereIn('status', ['replied', 'interested', 'approved'])
            ->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'scheduled' => $scheduled,
            'cancelled' => $cancelled,
            'response_rate' => $sent > 0 ? round(($followUpReplies / $sent) * 100, 1) : 0,
        ];
    }

    private function getCategoryPerformance(Carbon $from, Carbon $to): array
    {
        $data = Vendor::selectRaw('product_category, COUNT(*) as total,
                SUM(CASE WHEN last_contacted_at IS NOT NULL THEN 1 ELSE 0 END) as contacted,
                SUM(CASE WHEN status IN ("replied","interested","approved") THEN 1 ELSE 0 END) as replied,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved')
            ->whereNotNull('product_category')
            ->where('product_category', '!=', '')
            ->groupBy('product_category')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('product_category')->values(),
            'total' => $data->pluck('total')->values(),
            'contacted' => $data->pluck('contacted')->values(),
            'replied' => $data->pluck('replied')->values(),
            'approved' => $data->pluck('approved')->values(),
        ];
    }

    private function getSuppressionStats(): array
    {
        return [
            'total' => SuppressionList::count(),
            'by_type' => SuppressionList::selectRaw('reason, COUNT(*) as count')
                ->groupBy('reason')
                ->pluck('count', 'reason')
                ->toArray(),
        ];
    }

    public function exportCsv(array $data): string
    {
        $rows = [];
        $rows[] = ['Campaign', 'Status', 'Total', 'Sent', 'Failed', 'Replied', 'Approved', 'Reply Rate %', 'Success Rate %'];

        foreach ($data['topCampaigns'] as $c) {
            $rows[] = [
                $c['name'], $c['status'], $c['total'], $c['sent'],
                $c['failed'], $c['replied'], $c['approved'],
                $c['reply_rate'], $c['success_rate'],
            ];
        }

        $output = "\xEF\xBB\xBF";
        foreach ($rows as $row) {
            $output .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        return $output;
    }
}
