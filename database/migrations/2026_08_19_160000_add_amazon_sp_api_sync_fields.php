<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('amazon_last_synced_at')->nullable()->after('notes');
            $table->string('amazon_sync_status')->nullable()->after('amazon_last_synced_at');
            $table->json('amazon_raw_data')->nullable()->after('amazon_sync_status');
        });

        Schema::table('amazon_orders', function (Blueprint $table) {
            $table->timestamp('amazon_last_synced_at')->nullable()->after('metadata');
            $table->string('amazon_sync_status')->nullable()->after('amazon_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['amazon_last_synced_at', 'amazon_sync_status', 'amazon_raw_data']);
        });

        Schema::table('amazon_orders', function (Blueprint $table) {
            $table->dropColumn(['amazon_last_synced_at', 'amazon_sync_status']);
        });
    }
};
