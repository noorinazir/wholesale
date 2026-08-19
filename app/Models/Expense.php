<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'product_id', 'purchase_order_id', 'expense_number',
        'category', 'description', 'amount', 'currency', 'expense_date',
        'status', 'payment_method', 'vendor_name', 'receipt_url',
        'is_recurring', 'recurring_frequency', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'is_recurring' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public static function generateExpenseNumber(): string
    {
        $last = self::latest()->first();
        $next = $last ? $last->id + 1 : 1;
        return 'EXP-' . date('Y') . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public static function categoryLabels(): array
    {
        return [
            'shipping' => 'Shipping',
            'labeling' => 'Labeling',
            'inventory' => 'Inventory / PO',
            'amazon_fees' => 'Amazon Fees',
            'fba_fees' => 'FBA Fees (legacy)',
            'amazon_referral' => 'Amazon Referral (legacy)',
            'advertising' => 'Advertising',
            'storage' => 'Storage',
            'returns' => 'Returns',
            'supplies' => 'Supplies',
            'software' => 'Software',
            'fees' => 'Fees',
            'other' => 'Other',
        ];
    }

    public static function categoryColors(): array
    {
        return [
            'shipping' => 'blue',
            'labeling' => 'purple',
            'inventory' => 'teal',
            'amazon_fees' => 'orange',
            'fba_fees' => 'orange',
            'amazon_referral' => 'yellow',
            'advertising' => 'red',
            'storage' => 'gray',
            'returns' => 'red',
            'supplies' => 'green',
            'software' => 'indigo',
            'fees' => 'gray',
            'other' => 'gray',
        ];
    }
}
