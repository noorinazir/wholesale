<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->longText('body');
            $table->text('personalization_notes')->nullable();
            $table->string('tone')->default('professional');
            $table->string('objective')->nullable();
            $table->text('custom_instructions')->nullable();
            $table->string('ai_model')->nullable();
            $table->enum('status', ['draft', 'approved', 'rejected', 'scheduled', 'sent', 'failed'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->integer('generation_attempt')->default(1);
            $table->json('quality_checks')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('campaign_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_emails');
    }
};
