<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_webhook_link_id')->constrained('payment_webhook_links')->cascadeOnDelete();
            $table->string('payload_hash', 64);
            $table->json('raw_payload');
            $table->json('raw_headers')->nullable();
            $table->string('external_event_code', 120)->nullable()->index();
            $table->string('internal_action', 20)->nullable()->index();
            $table->string('buyer_email')->nullable()->index();
            $table->string('external_tx_id', 191)->nullable()->index();
            $table->string('external_product_id', 191)->nullable()->index();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 12)->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('received_at')->nullable()->index();
            $table->string('processing_status', 20)->index();
            $table->string('processing_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('replay_of_payment_event_id')->nullable()->constrained('payment_events')->nullOnDelete();
            $table->timestamps();

            $table->unique(['payment_webhook_link_id', 'payload_hash'], 'payment_event_link_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
