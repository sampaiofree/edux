<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_checkouts', function (Blueprint $table): void {
            $table->string('nome')->nullable()->after('course_id');
            $table->text('descricao')->nullable()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('course_checkouts', function (Blueprint $table): void {
            $table->dropColumn(['nome', 'descricao']);
        });
    }
};
