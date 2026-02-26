<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->text('escola_nome')->nullable()->after('id');
            $table->text('escola_cnpj')->nullable()->after('escola_nome');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['escola_nome', 'escola_cnpj']);
        });
    }
};
