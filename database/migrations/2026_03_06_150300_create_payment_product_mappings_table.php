<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_product_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_webhook_link_id')->constrained('payment_webhook_links')->cascadeOnDelete();
            $table->string('external_product_id', 191);
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['payment_webhook_link_id', 'external_product_id'], 'payment_product_map_link_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_product_mappings');
    }
};
