<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_email_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model');
            $table->string('action')->default('generate_email');
            $table->text('prompt')->nullable();
            $table->longText('response')->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->decimal('estimated_cost', 10, 6)->nullable();
            $table->boolean('success')->default(true);
            $table->text('error')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('model');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
