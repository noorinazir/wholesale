<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'service_vendor_id', 'product_id', 'purchase_order_id', 'expense_number',
        'category', 'description', 'amount', 'currency', 'expense_date',
        'status', 'allocation_method', 'payment_method', 'vendor_name', 'receipt_url',
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

    public function serviceVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'service_vendor_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ExpenseAllocation::class);
    }

    public function isAllocatedToPo(): bool
    {
        return $this->purchase_order_id !== null && $this->allocation_method !== 'none';
    }

    public static function generateExpenseNumber(): string
    {
        return DB::transaction(function () {
            $last = self::lockForUpdate()->latest()->first();
            $next = $last ? $last->id + 1 : 1;
            return 'EXP-' . date('Y') . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
        });
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
            'prep' => 'Prep Service',
            'inspection' => 'Inspection / QC',
            'customs' => 'Customs / Import Duties',
            'freight' => 'Freight Forwarding',
            'insurance' => 'Shipping Insurance',
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
            'prep' => 'purple',
            'inspection' => 'indigo',
            'customs' => 'red',
            'freight' => 'blue',
            'insurance' => 'green',
            'advertising' => 'red',
            'storage' => 'gray',
            'returns' => 'red',
            'supplies' => 'green',
            'software' => 'indigo',
            'fees' => 'gray',
            'other' => 'gray',
        ];
    }

    public static function allocationMethodLabels(): array
    {
        return [
            'none' => 'Not Allocated',
            'by_quantity' => 'By Quantity (split evenly across PO items)',
            'by_value' => 'By Value (proportional to line cost)',
            'specific' => 'Specific Items (manual allocation)',
        ];
    }
}
