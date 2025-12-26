<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_checkouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('hours');
            $table->decimal('price', 10, 2);
            $table->string('checkout_url');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'hours']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_checkouts');
    }
};
