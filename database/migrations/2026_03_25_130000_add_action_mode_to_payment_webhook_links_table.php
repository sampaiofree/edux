<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_webhook_links', function (Blueprint $table): void {
            $table->string('action_mode', 20)
                ->default('register')
                ->after('is_active')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('payment_webhook_links', function (Blueprint $table): void {
            $table->dropIndex(['action_mode']);
            $table->dropColumn('action_mode');
        });
    }
};
