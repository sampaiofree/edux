<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('certificate_front_line1')->default('Certificamos que');
            $table->string('certificate_front_line3')->default('concluiu com 100% de aproveitamento o curso');
            $table->string('certificate_front_line6', 500)->default('Com carga horária de {duration}, no período de {start} a {end}, promovido pelo portal de cursos EDUX.');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_front_line1',
                'certificate_front_line3',
                'certificate_front_line6',
            ]);
        });
    }
};
