<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_bonuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_checkout_id')->constrained('course_checkouts')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_bonuses');
    }
};
