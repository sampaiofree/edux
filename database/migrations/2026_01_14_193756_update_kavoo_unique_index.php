<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kavoo', function (Blueprint $table) {
            $table->dropUnique(['transaction_code']);
            $table->unique(['transaction_code', 'item_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kavoo', function (Blueprint $table) {
            $table->dropUnique(['transaction_code', 'item_product_id']);
            $table->unique('transaction_code');
        });
    }
};
