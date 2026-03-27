<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_webhook_ids', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('webhook_id', 191);
            $table->string('platform', 191)->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'webhook_id'], 'curso_webhook_ids_course_webhook_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_webhook_ids');
    }
};
