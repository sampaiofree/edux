<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->string('mail_mailer', 32)->nullable()->after('domain');
            $table->string('mail_scheme', 32)->nullable()->after('mail_mailer');
            $table->string('mail_host')->nullable()->after('mail_scheme');
            $table->unsignedInteger('mail_port')->nullable()->after('mail_host');
            $table->string('mail_username')->nullable()->after('mail_port');
            $table->text('mail_password')->nullable()->after('mail_username');
            $table->string('mail_from_address')->nullable()->after('mail_password');
            $table->string('mail_from_name')->nullable()->after('mail_from_address');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'mail_mailer',
                'mail_scheme',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_from_address',
                'mail_from_name',
            ]);
        });
    }
};
