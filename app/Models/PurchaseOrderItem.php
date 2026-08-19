<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'product_id', 'product_name', 'asin', 'upc',
        'quantity_ordered', 'quantity_received', 'unit_cost', 'line_total',
        'unit_shipping', 'unit_labeling', 'unit_other_costs', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
            'unit_shipping' => 'decimal:2',
            'unit_labeling' => 'decimal:2',
            'unit_other_costs' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUnitTotalCostAttribute(): float
    {
        return (float)$this->unit_cost + (float)$this->unit_shipping + (float)$this->unit_labeling + (float)$this->unit_other_costs;
    }

    public function getLineTotalCostAttribute(): float
    {
        return $this->unit_total_cost * $this->quantity_ordered;
    }

    public function getQuantityPendingAttribute(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }
}
