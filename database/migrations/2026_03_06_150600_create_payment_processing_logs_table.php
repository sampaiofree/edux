<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_processing_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_event_id')->constrained('payment_events')->cascadeOnDelete();
            $table->string('step', 120);
            $table->string('level', 20)->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['payment_event_id', 'step'], 'payment_processing_logs_event_step_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_processing_logs');
    }
};
