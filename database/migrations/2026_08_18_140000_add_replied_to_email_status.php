<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            // PostgreSQL: drop the check constraint and re-add with 'replied' included
            DB::statement("ALTER TABLE vendors DROP CONSTRAINT IF EXISTS vendors_email_status_check");
            DB::statement("ALTER TABLE vendors ADD CONSTRAINT vendors_email_status_check CHECK (email_status IN (
                'not_sent', 'draft', 'ready', 'scheduled', 'sending',
                'sent', 'failed', 'cancelled', 'opted_out', 'replied'
            ))");
            return;
        }

        // MySQL
        DB::statement("ALTER TABLE vendors MODIFY COLUMN email_status ENUM(
            'not_sent', 'draft', 'ready', 'scheduled', 'sending',
            'sent', 'failed', 'cancelled', 'opted_out', 'replied'
        ) NOT NULL DEFAULT 'not_sent'");
    }

    public function down(): void
    {
        DB::statement("UPDATE vendors SET email_status = 'sent' WHERE email_status = 'replied'");

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE vendors DROP CONSTRAINT IF EXISTS vendors_email_status_check");
            DB::statement("ALTER TABLE vendors ADD CONSTRAINT vendors_email_status_check CHECK (email_status IN (
                'not_sent', 'draft', 'ready', 'scheduled', 'sending',
                'sent', 'failed', 'cancelled', 'opted_out'
            ))");
            return;
        }

        // MySQL
        DB::statement("ALTER TABLE vendors MODIFY COLUMN email_status ENUM(
            'not_sent', 'draft', 'ready', 'scheduled', 'sending',
            'sent', 'failed', 'cancelled', 'opted_out'
        ) NOT NULL DEFAULT 'not_sent'");
    }
};
