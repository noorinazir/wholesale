<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'product_id', 'product_name', 'asin', 'upc',
        'quantity_ordered', 'quantity_received', 'unit_cost', 'line_total',
        'unit_shipping', 'unit_labeling', 'unit_other_costs',
        'allocated_po_shipping', 'allocated_po_tax', 'allocated_expense_cost',
        'landed_cost_per_unit', 'landed_cost_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
            'unit_shipping' => 'decimal:2',
            'unit_labeling' => 'decimal:2',
            'unit_other_costs' => 'decimal:2',
            'allocated_po_shipping' => 'decimal:2',
            'allocated_po_tax' => 'decimal:2',
            'allocated_expense_cost' => 'decimal:2',
            'landed_cost_per_unit' => 'decimal:2',
            'landed_cost_total' => 'decimal:2',
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

    public function expenseAllocations(): HasMany
    {
        return $this->hasMany(ExpenseAllocation::class);
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

    public function getLandedCostPerUnitAttribute(): float
    {
        if (array_key_exists('landed_cost_per_unit', $this->attributes) && (float)$this->attributes['landed_cost_per_unit'] > 0) {
            return (float)$this->attributes['landed_cost_per_unit'];
        }
        return $this->calculateLandedCost();
    }

    public function calculateLandedCost(): float
    {
        $baseCost = (float)$this->unit_cost
            + (float)$this->unit_shipping
            + (float)$this->unit_labeling
            + (float)$this->unit_other_costs;

        $po = $this->purchaseOrder;
        if (!$po) {
            return round($baseCost, 2);
        }

        $totalQty = $po->items->sum('quantity_ordered');
        $myQty = $this->quantity_ordered;

        // Proportionate PO-level costs (shipping, tax, discount)
        $poLevelCosts = (float)$po->shipping_cost + (float)$po->tax_amount - (float)$po->discount_amount;
        $allocatedPoCosts = $totalQty > 0
            ? $poLevelCosts * ($myQty / $totalQty)
            : 0;

        // Allocated expenses (approved + paid only)
        // Wrapped in try-catch for backward compatibility before migration is run
        $totalExpenseShare = 0;

        try {
            $myExpenseShare = 0;

            // PO-wide expenses (no product_id, by_quantity or by_value)
            $poWideExpenses = $po->expenses()
                ->whereIn('status', ['approved', 'paid'])
                ->whereNull('product_id')
                ->where('allocation_method', '!=', 'none')
                ->get();

            foreach ($poWideExpenses as $expense) {
                if ($expense->allocation_method === 'by_quantity') {
                    $myExpenseShare += $totalQty > 0
                        ? (float)$expense->amount * ($myQty / $totalQty)
                        : 0;
                } elseif ($expense->allocation_method === 'by_value') {
                    $totalValue = $po->items->sum(fn($i) => (float)$i->line_total);
                    $myValue = (float)$this->line_total;
                    $myExpenseShare += $totalValue > 0
                        ? (float)$expense->amount * ($myValue / $totalValue)
                        : 0;
                }
            }

            // Product-specific expenses (full amount, split by qty)
            $productExpenses = $po->expenses()
                ->whereIn('status', ['approved', 'paid'])
                ->where('product_id', $this->product_id)
                ->where('allocation_method', '!=', 'none')
                ->where('allocation_method', '!=', 'specific')
                ->sum('amount');

            // Specific allocations via expense_allocations table
            $specificAllocations = $this->expenseAllocations()
                ->whereHas('expense', fn($q) => $q->whereIn('status', ['approved', 'paid']))
                ->sum('amount');

            $totalExpenseShare = $myExpenseShare + $productExpenses + $specificAllocations;
        } catch (\Exception $e) {
            // Columns or tables don't exist yet (migration not run)
            $totalExpenseShare = 0;
        }
        $perUnitExpenses = $myQty > 0 ? $totalExpenseShare / $myQty : 0;
        $perUnitPoCosts = $myQty > 0 ? $allocatedPoCosts / $myQty : 0;

        $landedCostPerUnit = $baseCost + $perUnitPoCosts + $perUnitExpenses;

        return round($landedCostPerUnit, 2);
    }
}
