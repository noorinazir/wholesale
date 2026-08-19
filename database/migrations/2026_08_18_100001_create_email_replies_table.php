<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('email_log_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('subject');
            $table->longText('body_text');
            $table->longText('body_html')->nullable();
            $table->string('message_id')->nullable();
            $table->string('in_reply_to')->nullable();
            $table->timestamp('received_at');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('from_email');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_replies');
    }
};
