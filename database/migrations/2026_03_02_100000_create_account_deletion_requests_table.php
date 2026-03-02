<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requested_name');
            $table->string('requested_email');
            $table->string('requested_whatsapp', 32)->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending')->index();
            $table->dateTime('requested_at');
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'requested_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
