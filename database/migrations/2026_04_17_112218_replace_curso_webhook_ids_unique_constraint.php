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

        if (! $this->hasIndexForColumns('curso_webhook_ids', ['course_id'])) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->index('course_id', 'curso_webhook_ids_course_id_index');
            });
        }

        if (! $this->hasIndexForColumns('curso_webhook_ids', ['system_setting_id'])) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->index('system_setting_id', 'curso_webhook_ids_system_setting_id_index');
            });
        }

        if ($this->hasIndexNamed('curso_webhook_ids', 'curso_webhook_ids_course_webhook_unique')) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->dropUnique('curso_webhook_ids_course_webhook_unique');
            });
        }

        if ($this->hasIndexNamed('curso_webhook_ids', 'curso_webhook_ids_system_webhook_index')) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->dropIndex('curso_webhook_ids_system_webhook_index');
            });
        }

        if (! $this->hasIndexNamed('curso_webhook_ids', 'curso_webhook_ids_system_webhook_unique')) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->unique(['system_setting_id', 'webhook_id'], 'curso_webhook_ids_system_webhook_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndexNamed('curso_webhook_ids', 'curso_webhook_ids_system_webhook_unique')) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->dropUnique('curso_webhook_ids_system_webhook_unique');
            });
        }

        if (! $this->hasIndexNamed('curso_webhook_ids', 'curso_webhook_ids_system_webhook_index')) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->index(['system_setting_id', 'webhook_id'], 'curso_webhook_ids_system_webhook_index');
            });
        }

        if (! $this->hasIndexNamed('curso_webhook_ids', 'curso_webhook_ids_course_webhook_unique')) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->unique(['course_id', 'webhook_id'], 'curso_webhook_ids_course_webhook_unique');
            });
        }

        if ($this->hasIndexNamed('curso_webhook_ids', 'curso_webhook_ids_course_id_index')) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->dropIndex('curso_webhook_ids_course_id_index');
            });
        }

        if ($this->hasIndexNamed('curso_webhook_ids', 'curso_webhook_ids_system_setting_id_index')) {
            Schema::table('curso_webhook_ids', function (Blueprint $table): void {
                $table->dropIndex('curso_webhook_ids_system_setting_id_index');
            });
        }
    }

    private function hasIndexNamed(string $tableName, string $indexName): bool
    {
        return in_array($indexName, Schema::getIndexListing($tableName), true);
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasIndexForColumns(string $tableName, array $columns): bool
    {
        foreach (Schema::getIndexes($tableName) as $index) {
            if (array_values($index['columns'] ?? []) === $columns) {
                return true;
            }
        }

        return false;
    }
};
