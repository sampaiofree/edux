<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->uuid('onesignal_app_id')->nullable()->after('mail_from_name');
            $table->text('onesignal_rest_api_key')->nullable()->after('onesignal_app_id');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'onesignal_app_id',
                'onesignal_rest_api_key',
            ]);
        });
    }
};
