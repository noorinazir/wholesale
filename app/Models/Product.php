<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'asin', 'upc', 'product_name', 'product_category', 'image_url',
        'buying_price', 'fba_fee', 'shipping_cost', 'labeling_cost', 'other_costs', 'operation_cost',
        'amazon_sell_price', 'fba_buy_box_price', 'fbm_buy_box_price',
        'number_of_sellers', 'buy_box_type', 'bsr_rank', 'review_count', 'review_rating',
        'referral_fee_percent',
        'total_cost', 'amazon_fee', 'net_profit', 'margin_percent', 'roi_percent',
        'status', 'stock_quantity', 'last_purchase_order_id', 'notes',
        'amazon_last_synced_at', 'amazon_sync_status', 'amazon_raw_data',
    ];

    protected function casts(): array
    {
        return [
            'buying_price' => 'decimal:2',
            'fba_fee' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'labeling_cost' => 'decimal:2',
            'other_costs' => 'decimal:2',
            'operation_cost' => 'decimal:2',
            'amazon_sell_price' => 'decimal:2',
            'fba_buy_box_price' => 'decimal:2',
            'fbm_buy_box_price' => 'decimal:2',
            'referral_fee_percent' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'amazon_fee' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'margin_percent' => 'decimal:2',
            'roi_percent' => 'decimal:2',
            'review_rating' => 'decimal:1',
            'amazon_last_synced_at' => 'datetime',
            'amazon_raw_data' => 'array',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function amazonOrders(): HasMany
    {
        return $this->hasMany(AmazonOrder::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function recalculate(): void
    {
        $sellPrice = (float)$this->amazon_sell_price;

        $referralFee = 0;
        if ($sellPrice > 0 && (float)$this->referral_fee_percent > 0) {
            $referralFee = $sellPrice * (float)$this->referral_fee_percent / 100;
        }

        $fbaFee = (float)$this->fba_fee;
        $amazonFee = $fbaFee + $referralFee;

        $totalCost = (float)$this->buying_price + $fbaFee + $referralFee
            + (float)$this->shipping_cost + (float)$this->labeling_cost
            + (float)$this->other_costs + (float)$this->operation_cost;

        $netProfit = $sellPrice - $totalCost;
        $marginPercent = $sellPrice > 0 ? ($netProfit / $sellPrice) * 100 : 0;
        $roiPercent = $totalCost > 0 ? ($netProfit / $totalCost) * 100 : 0;

        $this->update([
            'total_cost' => round($totalCost, 2),
            'amazon_fee' => round($amazonFee, 2),
            'net_profit' => round($netProfit, 2),
            'margin_percent' => round($marginPercent, 2),
            'roi_percent' => round($roiPercent, 2),
        ]);
    }

    public function lastPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'last_purchase_order_id');
    }

    public function updateCostsFromPO(PurchaseOrderItem $item): void
    {
        $landedPerUnit = $item->calculateLandedCost();

        $this->update([
            'buying_price' => (float)$item->unit_cost,
            'shipping_cost' => (float)$item->unit_shipping + (float)$item->allocated_po_shipping,
            'labeling_cost' => (float)$item->unit_labeling,
            'other_costs' => (float)$item->unit_other_costs + (float)$item->allocated_po_tax,
            'operation_cost' => (float)$item->allocated_expense_cost,
            'last_purchase_order_id' => $item->purchase_order_id,
        ]);
        $this->recalculate();
    }

    public function adjustStock(int $qty, string $action = 'add'): void
    {
        if ($action === 'add') {
            $this->increment('stock_quantity', $qty);
        } else {
            $this->decrement('stock_quantity', $qty);
        }
    }

    public function getMarginColorAttribute(): string
    {
        $margin = (float)$this->margin_percent;
        if ($margin >= 30) return 'green';
        if ($margin >= 15) return 'blue';
        if ($margin >= 0) return 'yellow';
        return 'red';
    }

    public function getIsProfitableAttribute(): bool
    {
        return (float)$this->net_profit > 0;
    }

    public function scopeProfitable($query)
    {
        return $query->where('net_profit', '>', 0);
    }
}
