<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('payment_webhook_link_id')->constrained('payment_webhook_links')->cascadeOnDelete();
            $table->string('external_tx_id', 191)->default('');
            $table->string('external_product_id', 191)->default('');
            $table->string('state', 20)->index();
            $table->timestamp('last_event_at')->nullable()->index();
            $table->foreignId('last_payment_event_id')->nullable()->constrained('payment_events')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['user_id', 'course_id', 'payment_webhook_link_id', 'external_tx_id', 'external_product_id'],
                'payment_entitlement_unique_idx'
            );
            $table->index(['user_id', 'course_id', 'state'], 'payment_entitlement_user_course_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_entitlements');
    }
};
