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
        'subtotal', 'shipping_cost', 'tax_amount', 'discount_amount', 'total_amount', 'amount_paid',
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
