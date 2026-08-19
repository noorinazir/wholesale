<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('asin')->nullable();
            $table->string('product_name');
            $table->string('product_category')->nullable();
            $table->string('image_url')->nullable();

            // Cost fields
            $table->decimal('buying_price', 10, 2)->default(0);
            $table->decimal('fba_fee', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('labeling_cost', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);

            // Amazon market data
            $table->decimal('amazon_sell_price', 10, 2)->default(0);
            $table->decimal('fba_buy_box_price', 10, 2)->nullable();
            $table->decimal('fbm_buy_box_price', 10, 2)->nullable();
            $table->integer('number_of_sellers')->default(0);
            $table->enum('buy_box_type', ['fba', 'fbm', 'none'])->default('none');
            $table->integer('bsr_rank')->nullable();
            $table->integer('review_count')->nullable();
            $table->decimal('review_rating', 3, 1)->nullable();

            // Referral fee (Amazon commission %)
            $table->decimal('referral_fee_percent', 5, 2)->default(15.00);

            // Calculated fields (stored for quick reference)
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('amazon_fee', 10, 2)->default(0);
            $table->decimal('net_profit', 10, 2)->default(0);
            $table->decimal('margin_percent', 5, 2)->default(0);
            $table->decimal('roi_percent', 5, 2)->default(0);

            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('asin');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
