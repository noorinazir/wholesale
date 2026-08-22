<?php

namespace App\Services\AI;

use App\Models\Expense;
use App\Models\AmazonOrder;
use App\Services\ProfitLossService;
use Carbon\Carbon;

class AiFinancialAnalysisService
{
    private const VALID_CATEGORIES = [
        'shipping', 'labeling', 'inventory', 'amazon_fees', 'fba_fees',
        'amazon_referral', 'prep', 'inspection', 'customs', 'freight',
        'insurance', 'advertising', 'storage', 'returns', 'supplies',
        'software', 'fees', 'other',
    ];

    public function __construct(
        private KimiService $kimi,
        private ProfitLossService $plService
    ) {}

    public function categorizeExpense(string $description, ?float $amount = null, ?string $vendorName = null): array
    {
        $context = "Description: {$description}";
        if ($amount) $context .= "\nAmount: \${$amount}";
        if ($vendorName) $context .= "\nVendor: {$vendorName}";

        $categories = implode(', ', self::VALID_CATEGORIES);

        $prompt = <<<PROMPT
You are an accounting assistant. Categorize this expense into one of these categories:
{$categories}

Respond with ONLY a JSON object:
{"category": "one_of_the_categories", "confidence": 0.0_to_1.0, "reason": "brief explanation"}

Expense:
{$context}
PROMPT;

        $result = $this->kimi->chat(
            [['role' => 'user', 'content' => $prompt]],
            ['max_tokens' => 200, 'temperature' => 0.1]
        );

        if (!$result['success'] || !$result['content']) {
            return ['category' => 'other', 'confidence' => 0, 'reason' => 'AI unavailable'];
        }

        $content = trim($result['content']);
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        $parsed = json_decode($content, true);

        if (!is_array($parsed) || !isset($parsed['category'])) {
            return ['category' => 'other', 'confidence' => 0, 'reason' => 'Parse failed'];
        }

        $category = in_array($parsed['category'], self::VALID_CATEGORIES) ? $parsed['category'] : 'other';

        return [
            'category' => $category,
            'confidence' => (float)($parsed['confidence'] ?? 0.5),
            'reason' => $parsed['reason'] ?? '',
        ];
    }

    public function categorizeExpensesBatch(array $expenses): array
    {
        $results = [];
        $batchSize = 20;

        foreach (array_chunk($expenses, $batchSize) as $batch) {
            $lines = [];
            foreach ($batch as $idx => $exp) {
                $amt = $exp['amount'] ?? 'N/A';
                $vendor = $exp['vendor'] ?? 'N/A';
                $lines[] = ($idx + 1) . ". {$exp['description']} (Amount: \${$amt}, Vendor: {$vendor})";
            }

            $categories = implode(', ', self::VALID_CATEGORIES);
            $linesText = implode("\n", $lines);

            $prompt = <<<PROMPT
You are an accounting assistant. Categorize each expense into one of these categories:
{$categories}

Respond with ONLY a JSON array of objects, one per expense:
[{"index": 1, "category": "one_of_the_categories", "confidence": 0.0_to_1.0}]

Expenses:
{$linesText}
PROMPT;

            $result = $this->kimi->chat(
                [['role' => 'user', 'content' => $prompt]],
                ['max_tokens' => 1000, 'temperature' => 0.1]
            );

            if (!$result['success'] || !$result['content']) {
                foreach ($batch as $exp) {
                    $results[] = array_merge($exp, ['category' => 'other', 'confidence' => 0]);
                }
                continue;
            }

            $content = trim($result['content']);
            $content = preg_replace('/^```json\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            $parsed = json_decode($content, true);

            if (!is_array($parsed)) {
                foreach ($batch as $exp) {
                    $results[] = array_merge($exp, ['category' => 'other', 'confidence' => 0]);
                }
                continue;
            }

            foreach ($parsed as $item) {
                $idx = ($item['index'] ?? 0) - 1;
                if (isset($batch[$idx])) {
                    $category = in_array($item['category'] ?? '', self::VALID_CATEGORIES) ? $item['category'] : 'other';
                    $results[] = array_merge($batch[$idx], [
                        'category' => $category,
                        'confidence' => (float)($item['confidence'] ?? 0.5),
                    ]);
                }
            }
        }

        return $results;
    }

    public function generateMonthlyNarrative(Carbon $startDate, Carbon $endDate): array
    {
        $summary = $this->plService->getOverallSummary($startDate, $endDate);
        $vendorBreakdown = $this->plService->getPerVendorBreakdown($startDate, $endDate);
        $productBreakdown = $this->plService->getPerProductBreakdown($startDate, $endDate);

        $topProducts = array_slice($productBreakdown, 0, 5);
        $topVendors = array_slice($vendorBreakdown, 0, 5);

        $data = json_encode([
            'period' => $startDate->format('M Y') . ' (' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') . ')',
            'summary' => $summary,
            'top_products' => array_map(fn($p) => [
                'name' => $p['product_name'] ?? 'Unknown',
                'revenue' => $p['total_revenue'] ?? 0,
                'profit' => $p['net_profit'] ?? 0,
                'margin' => $p['margin_percent'] ?? 0,
            ], $topProducts),
            'top_vendors' => array_map(fn($v) => [
                'name' => $v['vendor_name'] ?? 'Unknown',
                'revenue' => $v['total_revenue'] ?? 0,
                'profit' => $v['net_profit'] ?? 0,
            ], $topVendors),
        ], JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are a financial analyst. Given the following monthly P&L data, write a concise narrative analysis (3-5 paragraphs).

Focus on:
1. Overall performance (revenue, profit, margin trends)
2. Top performing products and vendors
3. Cost breakdown analysis (which costs are highest, any concerns)
4. Anomalies or areas needing attention
5. Recommendations for next month

Keep it professional, concise, and actionable. Use dollar amounts and percentages.

Financial Data:
{$data}
PROMPT;

        $result = $this->kimi->chat(
            [['role' => 'user', 'content' => $prompt]],
            ['max_tokens' => 1200, 'temperature' => 0.7]
        );

        if (!$result['success'] || !$result['content']) {
            return [
                'success' => false,
                'narrative' => null,
                'error' => $result['error'] ?? 'AI unavailable',
            ];
        }

        return [
            'success' => true,
            'narrative' => $result['content'],
            'usage' => $result['usage'] ?? null,
        ];
    }

    public function detectAnomalies(Carbon $startDate, Carbon $endDate): array
    {
        $orders = AmazonOrder::whereBetween('order_date', [$startDate, $endDate])
            ->whereNotIn('order_status', ['cancelled'])
            ->get();

        $anomalies = [];

        if ($orders->isEmpty()) {
            return ['anomalies' => [], 'summary' => 'No orders in period.'];
        }

        $avgRevenue = $orders->avg(fn($o) => (float)$o->total_revenue);
        $avgMargin = $orders->avg(fn($o) => (float)$o->margin_percent);

        foreach ($orders as $order) {
            $revenue = (float)$order->total_revenue;
            $margin = (float)$order->margin_percent;

            if ($avgRevenue > 0 && $revenue > $avgRevenue * 3) {
                $anomalies[] = [
                    'type' => 'high_revenue',
                    'order_id' => $order->amazon_order_id ?? $order->id,
                    'description' => "Revenue \${$revenue} is " . round($revenue / $avgRevenue, 1) . "x the average",
                    'severity' => 'info',
                ];
            }

            if ($margin < -20) {
                $anomalies[] = [
                    'type' => 'negative_margin',
                    'order_id' => $order->amazon_order_id ?? $order->id,
                    'description' => "Negative margin of {$margin}% — selling at a loss",
                    'severity' => 'warning',
                ];
            }

            if ($order->breakaway_referral_rate > 0 && $order->amazon_referral_fee <= 0 && $revenue > 0) {
                $anomalies[] = [
                    'type' => 'missing_referral_fee',
                    'order_id' => $order->amazon_order_id ?? $order->id,
                    'description' => "Referral rate of {$order->breakaway_referral_rate}% set but no referral fee calculated for order with \${$revenue} revenue",
                    'severity' => 'warning',
                ];
            }
        }

        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', '!=', 'rejected')
            ->get();

        $expenseAmounts = $expenses->pluck('amount')->map(fn($a) => (float)$a)->toArray();
        if (!empty($expenseAmounts)) {
            $avgExpense = array_sum($expenseAmounts) / count($expenseAmounts);
            foreach ($expenses as $expense) {
                if ((float)$expense->amount > $avgExpense * 5 && $avgExpense > 0) {
                    $anomalies[] = [
                        'type' => 'high_expense',
                        'expense_id' => $expense->id,
                        'description' => "Expense '{$expense->description}' at \${$expense->amount} is " . round((float)$expense->amount / $avgExpense, 1) . "x the average",
                        'severity' => 'info',
                    ];
                }
            }

            $duplicates = $expenses->groupBy(function ($e) {
                return strtolower($e->description) . '|' . (float)$e->amount . '|' . $e->expense_date;
            })->filter(fn($group) => $group->count() > 1);

            foreach ($duplicates as $key => $group) {
                $anomalies[] = [
                    'type' => 'duplicate_expense',
                    'description' => "Possible duplicate: '{$group->first()->description}' for \${$group->first()->amount} on {$group->first()->expense_date} (appears {$group->count()} times)",
                    'severity' => 'warning',
                    'expense_ids' => $group->pluck('id')->toArray(),
                ];
            }
        }

        $summary = count($anomalies) === 0
            ? 'No anomalies detected in the selected period.'
            : count($anomalies) . ' potential anomalies found.';

        return [
            'anomalies' => $anomalies,
            'summary' => $summary,
            'stats' => [
                'orders_analyzed' => $orders->count(),
                'expenses_analyzed' => $expenses->count(),
                'avg_revenue' => round($avgRevenue, 2),
                'avg_margin' => round($avgMargin, 2),
            ],
        ];
    }
}
