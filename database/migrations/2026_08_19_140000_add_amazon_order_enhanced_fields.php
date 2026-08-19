<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amazon_orders', function (Blueprint $table) {
            $table->decimal('amazon_fee_total', 10, 2)->default(0)->after('amazon_referral_fee');
            $table->decimal('advertising_cost', 10, 2)->default(0)->after('operation_cost');
            $table->decimal('return_cost', 10, 2)->default(0)->after('advertising_cost');
            $table->decimal('breakaway_referral_rate', 5, 2)->default(15.00)->after('margin_percent');
            $table->string('selling_plan')->nullable()->after('fulfillment_channel');
            $table->string('batch_id')->nullable()->after('metadata');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('amazon_orders', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropColumn([
                'amazon_fee_total', 'advertising_cost', 'return_cost',
                'breakaway_referral_rate', 'selling_plan', 'batch_id',
            ]);
        });
    }
};
