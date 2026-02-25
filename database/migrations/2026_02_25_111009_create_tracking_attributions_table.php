<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tracking_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kavoo_id')->nullable()->constrained('kavoo')->nullOnDelete();
            $table->foreignId('tracking_session_id')->nullable()->constrained('tracking_sessions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_code')->nullable()->index();
            $table->unsignedBigInteger('item_product_id')->nullable()->index();
            $table->string('attribution_model', 64)->index();
            $table->string('session_uuid', 64)->nullable()->index();
            $table->string('visitor_uuid', 64)->nullable()->index();
            $table->string('source')->nullable()->index();
            $table->string('medium')->nullable()->index();
            $table->string('campaign')->nullable()->index();
            $table->string('content')->nullable();
            $table->string('term')->nullable();
            $table->string('fbclid')->nullable()->index();
            $table->string('gclid')->nullable()->index();
            $table->string('ttclid')->nullable()->index();
            $table->text('referrer')->nullable();
            $table->string('referrer_host')->nullable()->index();
            $table->string('city_slug')->nullable()->index();
            $table->string('city_name')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 12)->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->unique(['kavoo_id', 'attribution_model'], 'tracking_attr_kavoo_model_unique');
            $table->index(['source', 'medium', 'campaign'], 'tracking_attr_utm_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_attributions');
    }
};
