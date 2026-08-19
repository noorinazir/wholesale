<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_quantity')->default(0)->after('status');
            $table->foreignId('last_purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete()->after('stock_quantity');
            $table->index('stock_quantity');
        });

        Schema::table('amazon_orders', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete()->after('vendor_id');
            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('amazon_orders', function (Blueprint $table) {
            $table->dropIndex(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['stock_quantity']);
            $table->dropColumn(['stock_quantity', 'last_purchase_order_id']);
        });
    }
};
