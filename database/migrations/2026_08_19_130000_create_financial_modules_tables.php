<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === Purchase Orders ===
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->enum('status', [
                'draft', 'submitted', 'confirmed', 'in_production',
                'shipped', 'received', 'partial_received', 'cancelled'
            ])->default('draft');
            $table->enum('payment_status', ['unpaid', 'partial_paid', 'paid', 'refunded'])->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->string('payment_terms')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('order_date');
        });

        // === Purchase Order Items ===
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('asin')->nullable();
            $table->string('upc')->nullable();
            $table->integer('quantity_ordered')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->decimal('unit_shipping', 10, 2)->default(0);
            $table->decimal('unit_labeling', 10, 2)->default(0);
            $table->decimal('unit_other_costs', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('product_id');
        });

        // === Amazon Sales Orders ===
        Schema::create('amazon_orders', function (Blueprint $table) {
            $table->id();
            $table->string('amazon_order_id')->unique()->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('asin')->nullable();
            $table->string('upc')->nullable();
            $table->string('sku')->nullable();
            $table->string('fulfillment_channel')->nullable(); // FBA, FBM
            $table->string('amazon_marketplace')->default('US');
            $table->date('order_date');
            $table->date('ship_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->enum('order_status', [
                'pending', 'processing', 'shipped', 'delivered',
                'returned', 'refunded', 'cancelled'
            ])->default('pending');
            $table->integer('quantity')->default(1);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('amazon_referral_fee', 10, 2)->default(0);
            $table->decimal('fba_fee', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('labeling_cost', 10, 2)->default(0);
            $table->decimal('product_cost', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);
            $table->decimal('operation_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('margin_percent', 5, 2)->default(0);
            $table->decimal('tax_collected', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('tax_state')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_zip')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('vendor_id');
            $table->index('asin');
            $table->index('order_status');
            $table->index('order_date');
            $table->index('amazon_order_id');
        });

        // === Expenses (general operating expenses) ===
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('expense_number')->unique();
            $table->enum('category', [
                'shipping', 'labeling', 'fba_fees', 'amazon_referral',
                'advertising', 'storage', 'returns', 'supplies',
                'software', 'fees', 'other'
            ])->default('other');
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('expense_date');
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('receipt_url')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('product_id');
            $table->index('category');
            $table->index('expense_date');
            $table->index('status');
        });

        // === Tax Configuration (US state sales tax rates) ===
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('state_code', 2)->unique();
            $table->string('state_name');
            $table->decimal('sales_tax_rate', 5, 2)->default(0);
            $table->decimal('additional_rate', 5, 2)->default(0);
            $table->decimal('combined_rate', 5, 2)->default(0);
            $table->boolean('has_marketplace_facilitator')->default(false);
            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // === Profit & Loss Summaries (cached/aggregated) ===
        Schema::create('profit_loss_summaries', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['overall', 'vendor', 'product'])->default('overall');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_type')->default('monthly'); // daily, weekly, monthly, quarterly, yearly
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('total_product_cost', 14, 2)->default(0);
            $table->decimal('total_fba_fees', 14, 2)->default(0);
            $table->decimal('total_referral_fees', 14, 2)->default(0);
            $table->decimal('total_shipping', 14, 2)->default(0);
            $table->decimal('total_labeling', 14, 2)->default(0);
            $table->decimal('total_other_costs', 14, 2)->default(0);
            $table->decimal('total_operation_cost', 14, 2)->default(0);
            $table->decimal('total_advertising', 14, 2)->default(0);
            $table->decimal('total_returns_cost', 14, 2)->default(0);
            $table->decimal('total_expenses', 14, 2)->default(0);
            $table->decimal('gross_profit', 14, 2)->default(0);
            $table->decimal('net_profit', 14, 2)->default(0);
            $table->decimal('margin_percent', 5, 2)->default(0);
            $table->decimal('tax_collected', 14, 2)->default(0);
            $table->decimal('tax_owed', 14, 2)->default(0);
            $table->integer('units_sold')->default(0);
            $table->integer('units_returned')->default(0);
            $table->integer('orders_count')->default(0);
            $table->timestamps();

            $table->index(['scope', 'scope_id']);
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_loss_summaries');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('amazon_orders');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
