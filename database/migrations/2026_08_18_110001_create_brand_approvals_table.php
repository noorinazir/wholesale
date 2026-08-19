<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->enum('approval_status', ['pending', 'submitted', 'under_review', 'approved', 'rejected', 'expired'])->default('pending');
            $table->date('submitted_at')->nullable();
            $table->date('approved_at')->nullable();
            $table->date('expires_at')->nullable();

            // Approval details
            $table->json('approved_categories')->nullable();
            $table->decimal('minimum_order_qty', 10, 2)->nullable();
            $table->string('payment_terms')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->json('exclusive_territories')->nullable();
            $table->string('pricing_tier')->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('approval_document_url')->nullable();

            // Requirements
            $table->boolean('requires_exclusivity')->default(false);
            $table->boolean('requires_map_policy')->default(false);
            $table->boolean('requires_brand_registry')->default(false);
            $table->text('requirements_notes')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_approvals');
    }
};
