<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->boolean('certificate_issuance_blocked')
                ->default(false)
                ->after('manual_override_at')
                ->index();
            $table->string('certificate_issuance_block_reason')
                ->nullable()
                ->after('certificate_issuance_blocked');
        });

        Schema::table('system_settings', function (Blueprint $table): void {
            $table->string('school_whatsapp')
                ->nullable()
                ->after('escola_cnpj');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn('school_whatsapp');
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropColumn([
                'certificate_issuance_blocked',
                'certificate_issuance_block_reason',
            ]);
        });
    }
};
