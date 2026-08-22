<?php

namespace App\Services;

use App\Models\AmazonOrder;
use App\Models\Expense;
use App\Models\ProfitLossSummary;
use App\Models\Vendor;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    public function getOverallSummary(Carbon $startDate, Carbon $endDate, string $periodType = 'monthly'): array
    {
        $orders = AmazonOrder::whereBetween('order_date', [$startDate, $endDate])
            ->whereNotIn('order_status', ['cancelled'])
            ->get();

        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', '!=', 'rejected')
            ->where('notes', 'not like', 'Auto-generated from Amazon sale%')
            ->get();

        return $this->calculateSummary($orders, $expenses, $startDate, $endDate, $periodType);
    }

    public function getVendorSummary(int $vendorId, Carbon $startDate, Carbon $endDate): array
    {
        $orders = AmazonOrder::where('vendor_id', $vendorId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereNotIn('order_status', ['cancelled'])
            ->get();

        $expenses = Expense::where('vendor_id', $vendorId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', '!=', 'rejected')
            ->where('notes', 'not like', 'Auto-generated from Amazon sale%')
            ->get();

        return $this->calculateSummary($orders, $expenses, $startDate, $endDate, 'vendor');
    }

    public function getProductSummary(int $productId, Carbon $startDate, Carbon $endDate): array
    {
        $orders = AmazonOrder::where('product_id', $productId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereNotIn('order_status', ['cancelled'])
            ->get();

        $expenses = Expense::where('product_id', $productId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', '!=', 'rejected')
            ->where('notes', 'not like', 'Auto-generated from Amazon sale%')
            ->get();

        return $this->calculateSummary($orders, $expenses, $startDate, $endDate, 'product');
    }

    public function getPerVendorBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $vendors = Vendor::whereHas('amazonOrders', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate])
              ->whereNotIn('order_status', ['cancelled']);
        })->get();

        return $vendors->map(function ($vendor) use ($startDate, $endDate) {
            $summary = $this->getVendorSummary($vendor->id, $startDate, $endDate);
            return array_merge($summary, [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->brand_name,
            ]);
        })->sortByDesc('net_profit')->values()->toArray();
    }

    public function getPerProductBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $products = Product::whereHas('amazonOrders', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate])
              ->whereNotIn('order_status', ['cancelled']);
        })->with('vendor:id,brand_name')->get();

        return $products->map(function ($product) use ($startDate, $endDate) {
            $summary = $this->getProductSummary($product->id, $startDate, $endDate);
            return array_merge($summary, [
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'asin' => $product->asin,
                'vendor_name' => $product->vendor?->brand_name,
            ]);
        })->sortByDesc('net_profit')->values()->toArray();
    }

    public function getMonthlyTrend(int $months = 12): array
    {
        $trend = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            $summary = $this->getOverallSummary($start, $end);
            $trend[] = [
                'label' => $date->format('M Y'),
                'revenue' => $summary['total_revenue'],
                'cost' => $summary['total_cost'],
                'profit' => $summary['net_profit'],
                'margin' => $summary['margin_percent'],
            ];
        }
        return $trend;
    }

    public function getExpenseBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', '!=', 'rejected')
            ->where('notes', 'not like', 'Auto-generated from Amazon sale%')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $orderCosts = AmazonOrder::whereBetween('order_date', [$startDate, $endDate])
            ->whereNotIn('order_status', ['cancelled'])
            ->selectRaw("
                SUM(CASE WHEN order_status NOT IN ('returned','refunded') THEN product_cost ELSE 0 END) as product_cost,
                SUM(CASE WHEN order_status NOT IN ('returned','refunded') THEN fba_fee ELSE 0 END) as fba_fee,
                SUM(CASE WHEN order_status NOT IN ('returned','refunded') THEN amazon_referral_fee ELSE 0 END) as referral_fee,
                SUM(CASE WHEN order_status NOT IN ('returned','refunded') THEN shipping_cost ELSE 0 END) as shipping,
                SUM(CASE WHEN order_status NOT IN ('returned','refunded') THEN labeling_cost ELSE 0 END) as labeling,
                SUM(CASE WHEN order_status NOT IN ('returned','refunded') THEN other_costs ELSE 0 END) as other,
                SUM(CASE WHEN order_status NOT IN ('returned','refunded') THEN operation_cost ELSE 0 END) as operation,
                SUM(CASE WHEN order_status NOT IN ('returned','refunded') THEN advertising_cost ELSE 0 END) as advertising,
                SUM(return_cost) as return_cost,
                SUM(CASE WHEN order_status IN ('returned','refunded') THEN product_cost ELSE 0 END) as returned_product_cost,
                SUM(CASE WHEN order_status IN ('returned','refunded') THEN fba_fee ELSE 0 END) as returned_fba_fee,
                SUM(CASE WHEN order_status IN ('returned','refunded') THEN shipping_cost ELSE 0 END) as returned_shipping
            ")
            ->first();

        $breakdown = [];
        if ($orderCosts) {
            if ($orderCosts->product_cost > 0) $breakdown['Product Cost'] = (float)$orderCosts->product_cost;
            if ($orderCosts->fba_fee > 0) $breakdown['Amazon Fees'] = (float)$orderCosts->fba_fee;
            if ($orderCosts->referral_fee > 0) $breakdown['Amazon Referral'] = (float)$orderCosts->referral_fee;
            if ($orderCosts->shipping > 0) $breakdown['Shipping'] = (float)$orderCosts->shipping;
            if ($orderCosts->labeling > 0) $breakdown['Labeling'] = (float)$orderCosts->labeling;
            if ($orderCosts->operation > 0) $breakdown['Operation'] = (float)$orderCosts->operation;
            if ($orderCosts->advertising > 0) $breakdown['Advertising'] = (float)$orderCosts->advertising;
            if ($orderCosts->other > 0) $breakdown['Other'] = (float)$orderCosts->other;
            $totalReturns = (float)$orderCosts->return_cost + (float)$orderCosts->returned_product_cost + (float)$orderCosts->returned_fba_fee + (float)$orderCosts->returned_shipping;
            if ($totalReturns > 0) $breakdown['Returns & Refunds'] = $totalReturns;
        }

        foreach ($expenses as $cat => $amount) {
            $label = Expense::categoryLabels()[$cat] ?? ucfirst($cat);
            $breakdown[$label] = ($breakdown[$label] ?? 0) + (float)$amount;
        }

        arsort($breakdown);
        return $breakdown;
    }

    private function calculateSummary($orders, $expenses, Carbon $startDate, Carbon $endDate, string $periodType = 'monthly'): array
    {
        // Split orders: active (revenue-generating) vs returned/refunded
        $activeOrders = $orders->whereNotIn('order_status', ['cancelled', 'returned', 'refunded']);
        $returnedOrders = $orders->whereIn('order_status', ['returned', 'refunded']);

        $totalRevenue = $activeOrders->sum(fn($o) => (float)$o->total_revenue);
        $totalProductCost = $activeOrders->sum(fn($o) => (float)$o->product_cost);
        $totalFbaFees = $activeOrders->sum(fn($o) => (float)$o->fba_fee);
        $totalReferralFees = $activeOrders->sum(fn($o) => (float)$o->amazon_referral_fee);
        $totalShipping = $activeOrders->sum(fn($o) => (float)$o->shipping_cost);
        $totalLabeling = $activeOrders->sum(fn($o) => (float)$o->labeling_cost);
        $totalOtherCosts = $activeOrders->sum(fn($o) => (float)$o->other_costs);
        $totalOperationCost = $activeOrders->sum(fn($o) => (float)$o->operation_cost);
        $totalAdvertising = $activeOrders->sum(fn($o) => (float)$o->advertising_cost);
        $totalTaxCollected = $activeOrders->sum(fn($o) => (float)$o->tax_collected);

        // Return costs: return_cost field + lost product cost (inventory returned but may be unsellable)
        $totalReturnCost = $returnedOrders->sum(fn($o) => (float)$o->return_cost);
        $returnedProductCost = $returnedOrders->sum(fn($o) => (float)$o->product_cost);
        $returnedFbaFees = $returnedOrders->sum(fn($o) => (float)$o->fba_fee);
        $returnedShipping = $returnedOrders->sum(fn($o) => (float)$o->shipping_cost);

        $totalOrderCosts = $totalProductCost + $totalFbaFees + $totalReferralFees + $totalShipping + $totalLabeling + $totalOtherCosts + $totalOperationCost + $totalAdvertising
            + $totalReturnCost + $returnedProductCost + $returnedFbaFees + $returnedShipping;

        $totalExpenses = $expenses->sum(fn($e) => (float)$e->amount);
        $advertisingExpenses = $expenses->where('category', 'advertising')->sum(fn($e) => (float)$e->amount);

        $grossProfit = $totalRevenue - $totalProductCost;
        $netProfit = $totalRevenue - $totalOrderCosts - $totalExpenses;
        $margin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        $unitsSold = $activeOrders->sum('quantity');
        $unitsReturned = $returnedOrders->sum('quantity');

        return [
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'period_type' => $periodType,
            'total_revenue' => round($totalRevenue, 2),
            'total_product_cost' => round($totalProductCost, 2),
            'total_fba_fees' => round($totalFbaFees, 2),
            'total_referral_fees' => round($totalReferralFees, 2),
            'total_shipping' => round($totalShipping, 2),
            'total_labeling' => round($totalLabeling, 2),
            'total_other_costs' => round($totalOtherCosts, 2),
            'total_operation_cost' => round($totalOperationCost, 2),
            'total_order_costs' => round($totalOrderCosts, 2),
            'total_advertising' => round($totalAdvertising + $advertisingExpenses, 2),
            'total_returns_cost' => round($totalReturnCost + $returnedProductCost + $returnedFbaFees + $returnedShipping, 2),
            'total_expenses' => round($totalExpenses, 2),
            'total_cost' => round($totalOrderCosts + $totalExpenses, 2),
            'gross_profit' => round($grossProfit, 2),
            'net_profit' => round($netProfit, 2),
            'margin_percent' => round($margin, 2),
            'tax_collected' => round($totalTaxCollected, 2),
            'tax_owed' => round($totalTaxCollected, 2),
            'units_sold' => $unitsSold,
            'units_returned' => $unitsReturned,
            'orders_count' => $activeOrders->count(),
            'returns_count' => $returnedOrders->count(),
        ];
    }

    public function cacheSummaries(Carbon $startDate, Carbon $endDate, string $periodType = 'monthly'): void
    {
        $overall = $this->getOverallSummary($startDate, $endDate, $periodType);
        $this->storeSummary('overall', null, $startDate, $endDate, $periodType, $overall);

        $vendorBreakdown = $this->getPerVendorBreakdown($startDate, $endDate);
        foreach ($vendorBreakdown as $row) {
            $this->storeSummary('vendor', $row['vendor_id'], $startDate, $endDate, $periodType, $row);
        }

        $productBreakdown = $this->getPerProductBreakdown($startDate, $endDate);
        foreach ($productBreakdown as $row) {
            $this->storeSummary('product', $row['product_id'], $startDate, $endDate, $periodType, $row);
        }
    }

    public function cacheMonthlySummaries(int $months = 12): void
    {
        $now = Carbon::now();
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = $now->copy()->subMonths($i)->startOfMonth();
            $end = $now->copy()->subMonths($i)->endOfMonth();
            $this->cacheSummaries($start, $end, 'monthly');
        }
    }

    private function storeSummary(string $scope, ?int $scopeId, Carbon $startDate, Carbon $endDate, string $periodType, array $data): void
    {
        ProfitLossSummary::updateOrCreate(
            [
                'scope' => $scope,
                'scope_id' => $scopeId,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
                'period_type' => $periodType,
            ],
            [
                'total_revenue' => $data['total_revenue'] ?? 0,
                'total_product_cost' => $data['total_product_cost'] ?? 0,
                'total_fba_fees' => $data['total_fba_fees'] ?? 0,
                'total_referral_fees' => $data['total_referral_fees'] ?? 0,
                'total_shipping' => $data['total_shipping'] ?? 0,
                'total_labeling' => $data['total_labeling'] ?? 0,
                'total_other_costs' => $data['total_other_costs'] ?? 0,
                'total_operation_cost' => $data['total_operation_cost'] ?? 0,
                'total_advertising' => $data['total_advertising'] ?? 0,
                'total_returns_cost' => $data['total_returns_cost'] ?? 0,
                'total_expenses' => $data['total_expenses'] ?? 0,
                'gross_profit' => $data['gross_profit'] ?? 0,
                'net_profit' => $data['net_profit'] ?? 0,
                'margin_percent' => $data['margin_percent'] ?? 0,
                'tax_collected' => $data['tax_collected'] ?? 0,
                'tax_owed' => $data['tax_owed'] ?? 0,
                'units_sold' => $data['units_sold'] ?? 0,
                'units_returned' => $data['units_returned'] ?? 0,
                'orders_count' => $data['orders_count'] ?? 0,
            ]
        );
    }
}
