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
        Schema::create('tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_uuid', 64)->unique();
            $table->string('visitor_uuid', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at')->index();
            $table->timestamp('last_seen_at')->index();
            $table->text('landing_url')->nullable();
            $table->string('landing_path')->nullable();
            $table->string('first_page_type', 120)->nullable()->index();
            $table->text('referrer')->nullable();
            $table->string('referrer_host')->nullable()->index();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('fbclid')->nullable()->index();
            $table->string('gclid')->nullable()->index();
            $table->string('ttclid')->nullable()->index();
            $table->string('city_slug')->nullable()->index();
            $table->string('city_name')->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('device_type', 32)->nullable()->index();
            $table->string('os', 64)->nullable();
            $table->string('browser', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['visitor_uuid', 'last_seen_at']);
            $table->index(['utm_source', 'utm_medium', 'utm_campaign'], 'tracking_sessions_utm_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_sessions');
    }
};
