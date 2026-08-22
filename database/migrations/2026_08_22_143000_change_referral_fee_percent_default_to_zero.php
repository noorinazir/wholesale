<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::table('products')->where('referral_fee_percent', 15.00)->update(['referral_fee_percent' => 0]);

        DB::table('amazon_orders')->where('breakaway_referral_rate', 15.00)->update(['breakaway_referral_rate' => 0]);
    }

    public function down(): void
    {
        DB::table('products')->where('referral_fee_percent', 0)->update(['referral_fee_percent' => 15.00]);

        DB::table('amazon_orders')->where('breakaway_referral_rate', 0)->update(['breakaway_referral_rate' => 15.00]);
    }
};
