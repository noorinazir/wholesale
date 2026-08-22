<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amazon_settlement_transactions', function (Blueprint $table) {
            $table->decimal('revenue', 14, 2)->default(0)->after('amount');
            $table->decimal('amazon_fees_amount', 14, 2)->default(0)->after('revenue');
            $table->decimal('promotional_rebates', 14, 2)->default(0)->after('amazon_fees_amount');
            $table->decimal('other_amount', 14, 2)->default(0)->after('promotional_rebates');
        });
    }

    public function down(): void
    {
        Schema::table('amazon_settlement_transactions', function (Blueprint $table) {
            $table->dropColumn(['revenue', 'amazon_fees_amount', 'promotional_rebates', 'other_amount']);
        });
    }
};
