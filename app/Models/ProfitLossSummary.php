<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitLossSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope', 'scope_id', 'period_start', 'period_end', 'period_type',
        'total_revenue', 'total_product_cost', 'total_fba_fees', 'total_referral_fees',
        'total_shipping', 'total_labeling', 'total_other_costs', 'total_operation_cost',
        'total_advertising', 'total_returns_cost', 'total_expenses',
        'gross_profit', 'net_profit', 'margin_percent',
        'tax_collected', 'tax_owed', 'units_sold', 'units_returned', 'orders_count',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_revenue' => 'decimal:2',
            'total_product_cost' => 'decimal:2',
            'total_fba_fees' => 'decimal:2',
            'total_referral_fees' => 'decimal:2',
            'total_shipping' => 'decimal:2',
            'total_labeling' => 'decimal:2',
            'total_other_costs' => 'decimal:2',
            'total_operation_cost' => 'decimal:2',
            'total_advertising' => 'decimal:2',
            'total_returns_cost' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'gross_profit' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'margin_percent' => 'decimal:2',
            'tax_collected' => 'decimal:2',
            'tax_owed' => 'decimal:2',
        ];
    }
}
