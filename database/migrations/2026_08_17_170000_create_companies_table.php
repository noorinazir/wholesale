<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('legal_company_name')->nullable();
            $table->string('website')->nullable();
            $table->text('business_description')->nullable();
            $table->string('business_address')->nullable();
            $table->string('country')->nullable();
            $table->string('state_province')->nullable();
            $table->string('city')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('amazon_store_url')->nullable();
            $table->string('amazon_marketplace')->nullable();
            $table->integer('years_in_business')->nullable();
            $table->string('business_model')->nullable();
            $table->text('product_categories')->nullable();
            $table->text('brands_represented')->nullable();
            $table->text('sales_channels')->nullable();
            $table->decimal('estimated_annual_purchasing_volume', 15, 2)->nullable();
            $table->decimal('estimated_monthly_purchasing_volume', 15, 2)->nullable();
            $table->text('target_brands')->nullable();
            $table->text('additional_information')->nullable();
            $table->text('custom_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
