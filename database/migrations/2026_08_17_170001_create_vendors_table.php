<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('brand_name');
            $table->string('company_name')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('secondary_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('product_category')->nullable();
            $table->string('amazon_brand_store')->nullable();
            $table->string('vendor_website')->nullable();
            $table->string('contact_source')->nullable();
            $table->text('notes')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'new', 'researching', 'ready_to_contact', 'contacted', 'replied',
                'interested', 'not_interested', 'approved', 'rejected',
                'follow_up_required', 'opted_out', 'invalid_email', 'archived'
            ])->default('new');
            $table->enum('email_status', [
                'not_sent', 'draft', 'ready', 'scheduled', 'sending',
                'sent', 'failed', 'cancelled', 'opted_out'
            ])->default('not_sent');
            $table->timestamp('last_contacted_at')->nullable();
            $table->date('next_follow_up')->nullable();
            $table->text('research_summary')->nullable();
            $table->json('research_data')->nullable();
            $table->timestamp('researched_at')->nullable();
            $table->timestamps();

            $table->index('contact_email');
            $table->index('brand_name');
            $table->index('status');
            $table->index('email_status');
            $table->index('priority');
            $table->index('country');
            $table->index('product_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
