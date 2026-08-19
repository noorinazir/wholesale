<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('original_email_id')->nullable()->constrained('generated_emails')->nullOnDelete();
            $table->integer('sequence')->default(1);
            $table->integer('delay_days')->default(7);
            $table->date('scheduled_date')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->enum('status', ['pending', 'approved', 'scheduled', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->boolean('auto_send')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('campaign_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
