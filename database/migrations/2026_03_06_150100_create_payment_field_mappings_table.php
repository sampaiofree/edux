<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_field_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_webhook_link_id')->constrained('payment_webhook_links')->cascadeOnDelete();
            $table->string('field_key', 64);
            $table->string('json_path');
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(['payment_webhook_link_id', 'field_key'], 'payment_field_map_link_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_field_mappings');
    }
};
