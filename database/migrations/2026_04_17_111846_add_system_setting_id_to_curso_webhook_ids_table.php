<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curso_webhook_ids', function (Blueprint $table): void {
            $table->foreignId('system_setting_id')
                ->nullable()
                ->after('course_id');

            $table->index(['system_setting_id', 'webhook_id'], 'curso_webhook_ids_system_webhook_index');
            $table->foreign('system_setting_id', 'curso_webhook_ids_system_setting_id_foreign')
                ->references('id')
                ->on('system_settings')
                ->nullOnDelete();
        });

        DB::table('curso_webhook_ids')
            ->join('courses', 'courses.id', '=', 'curso_webhook_ids.course_id')
            ->select([
                'curso_webhook_ids.id',
                'courses.system_setting_id',
            ])
            ->orderBy('curso_webhook_ids.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('curso_webhook_ids')
                    ->where('id', $row->id)
                    ->update([
                        'system_setting_id' => $row->system_setting_id,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('curso_webhook_ids', function (Blueprint $table): void {
            $table->dropForeign('curso_webhook_ids_system_setting_id_foreign');
            $table->dropIndex('curso_webhook_ids_system_webhook_index');
            $table->dropColumn('system_setting_id');
        });
    }
};
