<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('certificate_title_size')->default(68);
            $table->unsignedSmallInteger('certificate_subtitle_size')->default(52);
            $table->unsignedSmallInteger('certificate_body_size')->default(40);
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_title_size',
                'certificate_subtitle_size',
                'certificate_body_size',
            ]);
        });
    }
};
