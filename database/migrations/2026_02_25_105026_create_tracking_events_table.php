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
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_session_id')->nullable()->constrained('tracking_sessions')->nullOnDelete();
            $table->string('event_uuid', 64)->unique();
            $table->string('session_uuid', 64)->nullable()->index();
            $table->string('visitor_uuid', 64)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_name', 120)->index();
            $table->string('event_category', 64)->nullable()->index();
            $table->string('event_source', 64)->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('received_at')->nullable()->index();
            $table->text('page_url')->nullable();
            $table->string('page_path')->nullable()->index();
            $table->string('page_type', 120)->nullable()->index();
            $table->text('referrer')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkout_id')->nullable()->constrained('course_checkouts')->nullOnDelete();
            $table->string('course_slug')->nullable()->index();
            $table->string('city_slug')->nullable()->index();
            $table->string('city_name')->nullable();
            $table->string('cta_source', 120)->nullable()->index();
            $table->decimal('value', 12, 2)->nullable();
            $table->string('currency', 12)->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['event_name', 'occurred_at'], 'tracking_events_name_time_idx');
            $table->index(['page_type', 'occurred_at'], 'tracking_events_page_type_time_idx');
            $table->index(['city_slug', 'occurred_at'], 'tracking_events_city_time_idx');
            $table->index(['course_id', 'occurred_at'], 'tracking_events_course_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
