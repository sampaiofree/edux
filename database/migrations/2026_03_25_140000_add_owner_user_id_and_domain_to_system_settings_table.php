<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete()
                ->unique();
            $table->string('domain', 191)
                ->nullable()
                ->after('owner_user_id')
                ->unique();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('owner_user_id');
            $table->dropUnique('system_settings_domain_unique');
            $table->dropColumn('domain');
        });
    }
};
