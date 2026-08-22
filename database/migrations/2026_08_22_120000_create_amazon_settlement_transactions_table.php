<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_settlement_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('settlement_id')->nullable();
            $table->date('settlement_start_date')->nullable();
            $table->date('settlement_end_date')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->enum('status', ['pending', 'parsed', 'imported', 'failed'])->default('pending');
            $table->text('raw_content')->nullable();
            $table->json('parse_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('settlement_id');
            $table->index('status');
        });

        Schema::create('amazon_settlement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('amazon_settlement_imports')->cascadeOnDelete();
            $table->string('transaction_type')->nullable();
            $table->string('order_id')->nullable();
            $table->string('merchant_order_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('asin')->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('fee_type')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('transaction_description')->nullable();
            $table->date('posted_date')->nullable();
            $table->date('order_date')->nullable();
            $table->string('fulfillment_channel')->nullable();

            $table->enum('match_status', ['unmatched', 'matched_order', 'matched_product', 'matched_vendor', 'duplicate', 'ignored'])->default('unmatched');
            $table->foreignId('amazon_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->text('match_notes')->nullable();

            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('import_id');
            $table->index('order_id');
            $table->index('asin');
            $table->index('sku');
            $table->index('match_status');
            $table->index('posted_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_settlement_transactions');
        Schema::dropIfExists('amazon_settlement_imports');
    }
};
