<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add service_vendor_id and allocation_method to expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('service_vendor_id')->nullable()->constrained('vendors')->nullOnDelete()->after('vendor_id');
            $table->string('allocation_method', 20)->default('by_quantity')->after('status');
            $table->index('service_vendor_id');
        });

        // Expand category check constraint with new FBA-relevant categories
        DB::statement("ALTER TABLE expenses DROP CONSTRAINT IF EXISTS expenses_category_check");
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_category_check CHECK (category IN (
            'shipping','labeling','inventory','amazon_fees','fba_fees','amazon_referral',
            'prep','inspection','customs','freight','insurance',
            'advertising','storage','returns','supplies','software','fees','other'
        ))");

        // 2. Add landed cost fields to purchase_order_items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('allocated_po_shipping', 10, 2)->default(0)->after('unit_other_costs');
            $table->decimal('allocated_po_tax', 10, 2)->default(0)->after('allocated_po_shipping');
            $table->decimal('allocated_expense_cost', 10, 2)->default(0)->after('allocated_po_tax');
            $table->decimal('landed_cost_per_unit', 10, 2)->default(0)->after('allocated_expense_cost');
            $table->decimal('landed_cost_total', 12, 2)->default(0)->after('landed_cost_per_unit');
        });

        // 3. Add total_landed_cost and total_expenses to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('total_expenses', 12, 2)->default(0)->after('total_amount');
            $table->decimal('total_landed_cost', 12, 2)->default(0)->after('total_expenses');
        });

        // 4. Create expense_allocations table (for 'specific' allocation method)
        Schema::create('expense_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('expense_id');
            $table->index('purchase_order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_allocations');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['total_landed_cost', 'total_expenses']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'allocated_po_shipping', 'allocated_po_tax',
                'allocated_expense_cost', 'landed_cost_per_unit', 'landed_cost_total',
            ]);
        });

        DB::statement("ALTER TABLE expenses DROP CONSTRAINT IF EXISTS expenses_category_check");
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_category_check CHECK (category IN (
            'shipping','labeling','inventory','amazon_fees','fba_fees','amazon_referral',
            'advertising','storage','returns','supplies','software','fees','other'
        ))");

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['service_vendor_id']);
            $table->dropColumn(['service_vendor_id', 'allocation_method']);
        });
    }
};
