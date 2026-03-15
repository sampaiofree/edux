<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_event_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_webhook_link_id')->constrained('payment_webhook_links')->cascadeOnDelete();
            $table->string('external_event_code', 120);
            $table->string('internal_action', 20)->index();
            $table->timestamps();

            $table->unique(['payment_webhook_link_id', 'external_event_code'], 'payment_event_map_link_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_event_mappings');
    }
};
