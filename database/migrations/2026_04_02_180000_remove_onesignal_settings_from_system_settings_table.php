<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('system_settings', 'onesignal_app_id')) {
                $table->dropColumn('onesignal_app_id');
            }

            if (Schema::hasColumn('system_settings', 'onesignal_rest_api_key')) {
                $table->dropColumn('onesignal_rest_api_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('system_settings', 'onesignal_app_id')) {
                $table->uuid('onesignal_app_id')->nullable()->after('mail_from_name');
            }

            if (! Schema::hasColumn('system_settings', 'onesignal_rest_api_key')) {
                $table->text('onesignal_rest_api_key')->nullable()->after('onesignal_app_id');
            }
        });
    }
};
