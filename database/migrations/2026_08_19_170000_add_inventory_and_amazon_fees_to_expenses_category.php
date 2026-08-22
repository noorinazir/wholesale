<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE expenses DROP CONSTRAINT IF EXISTS expenses_category_check");
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_category_check CHECK (category IN (
            'shipping', 'labeling', 'inventory', 'amazon_fees', 'fba_fees',
            'amazon_referral', 'advertising', 'storage', 'returns', 'supplies',
            'software', 'fees', 'other'
        ))");
    }

    public function down(): void
    {
        // No safe way to restore enum constraint; leave as VARCHAR
    }
};
