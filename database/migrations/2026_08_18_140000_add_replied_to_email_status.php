<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE vendors MODIFY COLUMN email_status ENUM(
            'not_sent', 'draft', 'ready', 'scheduled', 'sending',
            'sent', 'failed', 'cancelled', 'opted_out', 'replied'
        ) NOT NULL DEFAULT 'not_sent'");
    }

    public function down(): void
    {
        DB::statement("UPDATE vendors SET email_status = 'sent' WHERE email_status = 'replied'");

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE vendors MODIFY COLUMN email_status ENUM(
            'not_sent', 'draft', 'ready', 'scheduled', 'sending',
            'sent', 'failed', 'cancelled', 'opted_out'
        ) NOT NULL DEFAULT 'not_sent'");
    }
};
