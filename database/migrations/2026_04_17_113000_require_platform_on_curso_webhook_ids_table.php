<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('curso_webhook_ids')
            ->whereNull('platform')
            ->orWhereRaw("TRIM(platform) = ''")
            ->update([
                'platform' => 'legacy',
            ]);

        Schema::table('curso_webhook_ids', function (Blueprint $table): void {
            $table->string('platform', 191)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('curso_webhook_ids', function (Blueprint $table): void {
            $table->string('platform', 191)->nullable()->change();
        });
    }
};
