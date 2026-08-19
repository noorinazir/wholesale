<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppression_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('reason')->default('opt_out');
            $table->text('notes')->nullable();
            $table->timestamp('suppressed_at');
            $table->timestamps();

            $table->index('email');
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppression_list');
    }
};
