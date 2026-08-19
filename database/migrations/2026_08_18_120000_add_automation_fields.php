<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add auto_approve to campaigns
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('auto_approve')->default(false)->after('status');
            $table->boolean('auto_followup_enabled')->default(false)->after('auto_approve');
            $table->integer('followup_delay_days')->default(5)->after('auto_followup_enabled');
            $table->integer('max_followups')->default(2)->after('followup_delay_days');
        });

        // Add scheduled_date to email_queue for per-vendor scheduling
        Schema::table('email_queue', function (Blueprint $table) {
            $table->timestamp('scheduled_date')->nullable()->after('scheduled_at');
        });

        // Notifications table for in-app notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('info');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::table('email_queue', function (Blueprint $table) {
            $table->dropColumn('scheduled_date');
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['auto_approve', 'auto_followup_enabled', 'followup_delay_days', 'max_followups']);
        });
    }
};
