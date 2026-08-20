<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmazonOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'amazon_order_id', 'product_id', 'vendor_id', 'purchase_order_id', 'product_name', 'asin', 'upc', 'sku',
        'fulfillment_channel', 'selling_plan', 'amazon_marketplace', 'order_date', 'ship_date', 'delivery_date',
        'order_status', 'quantity', 'sale_price', 'total_revenue',
        'amazon_referral_fee', 'amazon_fee_total', 'fba_fee', 'shipping_cost', 'labeling_cost',
        'product_cost', 'other_costs', 'operation_cost', 'advertising_cost', 'return_cost',
        'total_cost', 'net_profit', 'margin_percent', 'breakaway_referral_rate',
        'tax_collected', 'tax_rate', 'tax_state',
        'customer_name', 'customer_state', 'customer_city', 'customer_zip',
        'notes', 'metadata', 'batch_id',
        'amazon_last_synced_at', 'amazon_sync_status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'ship_date' => 'date',
            'delivery_date' => 'date',
            'sale_price' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'amazon_referral_fee' => 'decimal:2',
            'amazon_fee_total' => 'decimal:2',
            'fba_fee' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'labeling_cost' => 'decimal:2',
            'product_cost' => 'decimal:2',
            'other_costs' => 'decimal:2',
            'operation_cost' => 'decimal:2',
            'advertising_cost' => 'decimal:2',
            'return_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'margin_percent' => 'decimal:2',
            'breakaway_referral_rate' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'metadata' => 'array',
            'amazon_last_synced_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function recalculate(): void
    {
        $fbaFee = (float)$this->fba_fee;
        $isReturned = in_array($this->order_status, ['returned', 'refunded']);

        $referralFee = (float)$this->amazon_referral_fee;
        if ($referralFee <= 0 && (float)$this->breakaway_referral_rate > 0 && !$isReturned) {
            $referralFee = ((float)$this->total_revenue * (float)$this->breakaway_referral_rate) / 100;
        }

        $baseCost = (float)$this->product_cost + $fbaFee + $referralFee
            + (float)$this->shipping_cost + (float)$this->labeling_cost
            + (float)$this->other_costs + (float)$this->operation_cost
            + (float)$this->advertising_cost;

        $returnCost = (float)$this->return_cost;
        $totalCost = $baseCost + $returnCost;

        $effectiveRevenue = $isReturned ? 0 : (float)$this->total_revenue;

        $netProfit = $effectiveRevenue - $totalCost;
        $margin = $effectiveRevenue > 0 ? ($netProfit / $effectiveRevenue) * 100 : 0;

        $this->update([
            'amazon_referral_fee' => round($referralFee, 2),
            'amazon_fee_total' => round($fbaFee + $referralFee, 2),
            'total_cost' => round($totalCost, 2),
            'net_profit' => round($netProfit, 2),
            'margin_percent' => round($margin, 2),
        ]);
    }

    public static function autofillFromProduct(Product $product, int $quantity = 1): array
    {
        $salePrice = (float)$product->amazon_sell_price;
        $totalRevenue = $salePrice * $quantity;

        return [
            'product_id' => $product->id,
            'vendor_id' => $product->vendor_id,
            'product_name' => $product->product_name,
            'asin' => $product->asin,
            'upc' => $product->upc ?? null,
            'sale_price' => $salePrice,
            'product_cost' => (float)$product->buying_price * $quantity,
            'fba_fee' => (float)$product->fba_fee * $quantity,
            'amazon_referral_fee' => 0,
            'breakaway_referral_rate' => (float)$product->referral_fee_percent,
            'shipping_cost' => (float)$product->shipping_cost * $quantity,
            'labeling_cost' => (float)$product->labeling_cost * $quantity,
            'other_costs' => (float)$product->other_costs * $quantity,
            'operation_cost' => (float)($product->operation_cost ?? 0) * $quantity,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'returned' => 'Returned',
            'refunded' => 'Refunded',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function statusColors(): array
    {
        return [
            'pending' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => 'bg-gray-400'],
            'processing' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400', 'dot' => 'bg-blue-500'],
            'shipped' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'text' => 'text-cyan-700 dark:text-cyan-400', 'dot' => 'bg-cyan-500'],
            'delivered' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400', 'dot' => 'bg-green-500'],
            'returned' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400', 'dot' => 'bg-yellow-500'],
            'refunded' => ['bg' => 'bg-orange-50 dark:bg-orange-900/30', 'text' => 'text-orange-700 dark:text-orange-400', 'dot' => 'bg-orange-500'],
            'cancelled' => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400', 'dot' => 'bg-red-500'],
        ];
    }

    public static function fulfillmentLabels(): array
    {
        return [
            'FBA' => 'Amazon FBA',
            'FBM' => 'Seller Fulfilled',
        ];
    }

    public function getIsFbaAttribute(): bool
    {
        return $this->fulfillment_channel === 'FBA';
    }

    public function getProfitColorAttribute(): string
    {
        return (float)$this->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    }

    public static function calculateTax(float $amount, string $stateCode): array
    {
        $taxRate = TaxRate::where('state_code', $stateCode)->first();
        if (!$taxRate) {
            return ['rate' => 0, 'amount' => 0];
        }

        $rate = (float)$taxRate->combined_rate;
        return [
            'rate' => $rate,
            'amount' => round($amount * $rate / 100, 2),
        ];
    }
}
