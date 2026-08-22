<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vendors DROP CONSTRAINT IF EXISTS vendors_email_status_check");
        DB::statement("ALTER TABLE vendors ADD CONSTRAINT vendors_email_status_check CHECK (email_status IN (
            'not_sent', 'draft', 'ready', 'scheduled', 'sending',
            'sent', 'failed', 'cancelled', 'opted_out', 'replied'
        ))");
    }

    public function down(): void
    {
        DB::statement("UPDATE vendors SET email_status = 'sent' WHERE email_status = 'replied'");

        DB::statement("ALTER TABLE vendors DROP CONSTRAINT IF EXISTS vendors_email_status_check");
        DB::statement("ALTER TABLE vendors ADD CONSTRAINT vendors_email_status_check CHECK (email_status IN (
            'not_sent', 'draft', 'ready', 'scheduled', 'sending',
            'sent', 'failed', 'cancelled', 'opted_out'
        ))");
    }
};
