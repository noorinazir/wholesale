<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smtp_settings', function (Blueprint $table) {
            $table->string('imap_host')->nullable()->after('reply_to');
            $table->integer('imap_port')->nullable()->after('imap_host');
            $table->string('imap_encryption')->default('ssl')->after('imap_port');
            $table->string('imap_username')->nullable()->after('imap_encryption');
            $table->string('imap_password')->nullable()->after('imap_username');
            $table->boolean('inbox_checking_enabled')->default(false)->after('imap_password');
            $table->timestamp('last_inbox_check_at')->nullable()->after('inbox_checking_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('smtp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'imap_host', 'imap_port', 'imap_encryption',
                'imap_username', 'imap_password',
                'inbox_checking_enabled', 'last_inbox_check_at',
            ]);
        });
    }
};
