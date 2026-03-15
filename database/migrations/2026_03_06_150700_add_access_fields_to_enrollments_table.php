<?php

use App\Enums\EnrollmentAccessStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->string('access_status', 20)
                ->default(EnrollmentAccessStatus::ACTIVE->value)
                ->after('completed_at')
                ->index();
            $table->string('access_block_reason')->nullable()->after('access_status');
            $table->timestamp('access_blocked_at')->nullable()->after('access_block_reason');
            $table->boolean('manual_override')->default(false)->after('access_blocked_at');
            $table->foreignId('manual_override_by')->nullable()->after('manual_override')->constrained('users')->nullOnDelete();
            $table->timestamp('manual_override_at')->nullable()->after('manual_override_by');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('manual_override_by');
            $table->dropColumn([
                'access_status',
                'access_block_reason',
                'access_blocked_at',
                'manual_override',
                'manual_override_at',
            ]);
        });
    }
};
