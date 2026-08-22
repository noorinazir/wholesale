<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmazonSettlementTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id', 'transaction_type', 'order_id', 'merchant_order_id', 'sku', 'asin',
        'product_name', 'amount', 'revenue', 'amazon_fees_amount', 'promotional_rebates', 'other_amount',
        'fee_type', 'currency', 'transaction_description',
        'posted_date', 'order_date', 'fulfillment_channel',
        'match_status', 'amazon_order_id', 'product_id', 'vendor_id', 'expense_id',
        'match_notes', 'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'revenue' => 'decimal:2',
            'amazon_fees_amount' => 'decimal:2',
            'promotional_rebates' => 'decimal:2',
            'other_amount' => 'decimal:2',
            'posted_date' => 'date',
            'order_date' => 'date',
            'raw_data' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(AmazonSettlementImport::class, 'import_id');
    }

    public function amazonOrder(): BelongsTo
    {
        return $this->belongsTo(AmazonOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public static function transactionTypeLabels(): array
    {
        return [
            'order' => 'Order Payment',
            'refund' => 'Refund',
            'fee' => 'Fee',
            'adjustment' => 'Adjustment',
            'transfer' => 'Transfer',
            'service_fee' => 'Service Fee',
            'advertising' => 'Advertising',
            'storage_fee' => 'Storage Fee',
            'other' => 'Other',
        ];
    }

    public static function matchStatusLabels(): array
    {
        return [
            'unmatched' => 'Unmatched',
            'matched_order' => 'Matched to Order',
            'matched_product' => 'Matched to Product',
            'matched_vendor' => 'Matched to Vendor',
            'duplicate' => 'Duplicate',
            'ignored' => 'Ignored',
        ];
    }

    public static function matchStatusColors(): array
    {
        return [
            'unmatched' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-400'],
            'matched_order' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-400'],
            'matched_product' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400'],
            'matched_vendor' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'text' => 'text-cyan-700 dark:text-cyan-400'],
            'duplicate' => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-400'],
            'ignored' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-500 dark:text-gray-500'],
        ];
    }
}
