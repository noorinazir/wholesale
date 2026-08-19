<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_email_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('email_queue_id')->nullable()->constrained('email_queue')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient');
            $table->string('subject');
            $table->longText('body');
            $table->string('campaign_name')->nullable();
            $table->string('generated_by')->nullable();
            $table->string('ai_model')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['draft', 'approved', 'scheduled', 'sent', 'failed', 'bounced', 'rejected', 'cancelled'])->default('draft');
            $table->text('smtp_response')->nullable();
            $table->text('error')->nullable();
            $table->string('message_id')->nullable();

            $table->index('vendor_id');
            $table->index('campaign_id');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
