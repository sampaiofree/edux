<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('curso_webhook_ids')
            ->select('system_setting_id', 'webhook_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('system_setting_id')
            ->groupBy('system_setting_id', 'webhook_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Existem IDs de webhook duplicados por escola em curso_webhook_ids. Resolva os conflitos antes de migrar.');
        }

        Schema::table('curso_webhook_ids', function (Blueprint $table): void {
            $table->dropUnique('curso_webhook_ids_course_webhook_unique');
            $table->dropIndex('curso_webhook_ids_system_webhook_index');
            $table->unique(['system_setting_id', 'webhook_id'], 'curso_webhook_ids_system_webhook_unique');
        });
    }

    public function down(): void
    {
        Schema::table('curso_webhook_ids', function (Blueprint $table): void {
            $table->dropUnique('curso_webhook_ids_system_webhook_unique');
            $table->index(['system_setting_id', 'webhook_id'], 'curso_webhook_ids_system_webhook_index');
            $table->unique(['course_id', 'webhook_id'], 'curso_webhook_ids_course_webhook_unique');
        });
    }
};
