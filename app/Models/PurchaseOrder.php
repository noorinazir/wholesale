<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number', 'vendor_id', 'order_date', 'expected_delivery_date', 'actual_delivery_date',
        'status', 'payment_status', 'payment_method', 'payment_terms',
        'subtotal', 'shipping_cost', 'tax_amount', 'discount_amount', 'total_amount',
        'total_expenses', 'total_landed_cost', 'amount_paid',
        'currency', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'actual_delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'total_landed_cost' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(AmazonOrder::class);
    }

    public function recalculate(): void
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->quantity_ordered * $item->unit_cost;
        });

        $this->update([
            'subtotal' => round($subtotal, 2),
            'total_amount' => round($subtotal + $this->shipping_cost + $this->tax_amount - $this->discount_amount, 2),
        ]);
    }

    public function recalculateWithExpenses(): void
    {
        $this->recalculate();

        $totalExpenses = $this->expenses()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $totalQty = $this->items->sum('quantity_ordered');
        $poLevelCosts = (float)$this->shipping_cost + (float)$this->tax_amount - (float)$this->discount_amount;

        foreach ($this->items as $item) {
            $landedPerUnit = $item->calculateLandedCost();
            $myQty = $item->quantity_ordered;

            $allocatedPoCosts = $totalQty > 0
                ? $poLevelCosts * ($myQty / $totalQty)
                : 0;

            $allocatedExpenses = ($landedPerUnit * $myQty)
                - ($item->unit_total_cost * $myQty)
                - $allocatedPoCosts;

            $item->update([
                'allocated_po_shipping' => round($totalQty > 0 ? (float)$this->shipping_cost * ($myQty / $totalQty) : 0, 2),
                'allocated_po_tax' => round($totalQty > 0 ? ((float)$this->tax_amount - (float)$this->discount_amount) * ($myQty / $totalQty) : 0, 2),
                'allocated_expense_cost' => round(max(0, $allocatedExpenses) / max(1, $myQty), 2),
                'landed_cost_per_unit' => $landedPerUnit,
                'landed_cost_total' => round($landedPerUnit * $myQty, 2),
            ]);
        }

        $totalLandedCost = $this->items->sum(fn($i) => (float)$i->landed_cost_total);

        $this->update([
            'total_expenses' => round($totalExpenses, 2),
            'total_landed_cost' => round($totalLandedCost, 2),
        ]);
    }

    public function getTotalLandedCostAttribute(): float
    {
        if ((float)$this->attributes['total_landed_cost'] ?? 0 > 0) {
            return (float)$this->attributes['total_landed_cost'];
        }
        return $this->items->sum(fn($i) => $i->landed_cost_per_unit * $i->quantity_ordered);
    }

    public function getBalanceDueAttribute(): string
    {
        return number_format((float)$this->total_amount - (float)$this->amount_paid, 2);
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->items->every(fn($item) => $item->quantity_received >= $item->quantity_ordered);
    }

    public function getReceivedPercentageAttribute(): int
    {
        $total = $this->items->sum('quantity_ordered');
        if ($total === 0) return 0;
        $received = $this->items->sum('quantity_received');
        return (int)(($received / $total) * 100);
    }

    public static function generatePoNumber(): string
    {
        $last = self::latest()->first();
        $next = $last ? $last->id + 1 : 1;
        return 'PO-' . date('Y') . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
