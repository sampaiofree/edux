<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('support_whatsapp_mode', 20)
                ->default('all')
                ->after('kavoo_id');

            $table->foreignId('support_whatsapp_number_id')
                ->nullable()
                ->after('support_whatsapp_mode')
                ->constrained('support_whatsapp_numbers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('support_whatsapp_number_id');
            $table->dropColumn('support_whatsapp_mode');
        });
    }
};

