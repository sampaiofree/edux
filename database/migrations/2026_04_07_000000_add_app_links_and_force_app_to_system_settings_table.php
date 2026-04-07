<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->string('play_store_link')->nullable()->after('meta_ads_pixel');
            $table->string('apple_store_link')->nullable()->after('play_store_link');
            $table->boolean('force_app')->default(false)->after('apple_store_link');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'play_store_link',
                'apple_store_link',
                'force_app',
            ]);
        });
    }
};
