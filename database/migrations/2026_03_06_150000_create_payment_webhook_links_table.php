<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_links', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->uuid('endpoint_uuid')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->string('security_mode', 32)->nullable();
            $table->string('secret')->nullable();
            $table->string('signature_header', 120)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_links');
    }
};
