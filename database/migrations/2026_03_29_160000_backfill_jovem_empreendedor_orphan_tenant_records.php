<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TARGET_DOMAIN = 'cursos.jovemempreendedor.org';

    public function up(): void
    {
        $orphanCourseIds = DB::table('courses')
            ->whereNull('system_setting_id')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        if ($orphanCourseIds->isEmpty()) {
            return;
        }

        $targetSystemSettingId = $this->resolveTargetSystemSettingId();

        if ($targetSystemSettingId === null) {
            throw new RuntimeException(sprintf(
                'Target tenant "%s" not found while orphan tenant records still exist.',
                self::TARGET_DOMAIN
            ));
        }

        DB::transaction(function () use ($orphanCourseIds, $targetSystemSettingId): void {
            DB::table('courses')
                ->whereIn('id', $orphanCourseIds)
                ->update([
                    'system_setting_id' => $targetSystemSettingId,
                ]);

            $this->backfillSupportWhatsappNumbers($orphanCourseIds, $targetSystemSettingId);

            DB::table('enrollments')
                ->whereIn('course_id', $orphanCourseIds)
                ->whereNull('system_setting_id')
                ->update([
                    'system_setting_id' => $targetSystemSettingId,
                ]);

            DB::table('certificate_brandings')
                ->whereIn('course_id', $orphanCourseIds)
                ->whereNull('system_setting_id')
                ->update([
                    'system_setting_id' => $targetSystemSettingId,
                ]);

            $this->backfillUsers($orphanCourseIds, $targetSystemSettingId);
        });
    }

    public function down(): void
    {
        // Data backfill only. Intentionally irreversible.
    }

    private function resolveTargetSystemSettingId(): ?int
    {
        $targetDomain = $this->normalizeDomain(self::TARGET_DOMAIN);

        return DB::table('system_settings')
            ->select(['id', 'domain'])
            ->orderBy('id')
            ->get()
            ->first(fn (object $setting): bool => $this->normalizeDomain($setting->domain) === $targetDomain)
            ?->id;
    }

    /**
     * @param  Collection<int, int>  $orphanCourseIds
     */
    private function backfillSupportWhatsappNumbers(Collection $orphanCourseIds, int $targetSystemSettingId): void
    {
        $supportWhatsappNumberIds = DB::table('courses')
            ->whereIn('id', $orphanCourseIds)
            ->whereNotNull('support_whatsapp_number_id')
            ->distinct()
            ->pluck('support_whatsapp_number_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        if ($supportWhatsappNumberIds->isEmpty()) {
            return;
        }

        $copyColumns = collect(Schema::getColumnListing('support_whatsapp_numbers'))
            ->reject(fn (string $column): bool => in_array($column, ['id', 'system_setting_id', 'created_at', 'updated_at'], true))
            ->values()
            ->all();

        $now = now();

        foreach ($supportWhatsappNumberIds as $numberId) {
            $number = DB::table('support_whatsapp_numbers')->where('id', $numberId)->first();

            if (! $number) {
                continue;
            }

            $currentSystemSettingId = $number->system_setting_id !== null ? (int) $number->system_setting_id : null;

            if ($currentSystemSettingId === $targetSystemSettingId) {
                continue;
            }

            $referencingTenantIds = DB::table('courses')
                ->where('support_whatsapp_number_id', $numberId)
                ->whereNotNull('system_setting_id')
                ->distinct()
                ->pluck('system_setting_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values();

            $canAssignInPlace = $currentSystemSettingId === null
                && $referencingTenantIds->every(fn (int $id): bool => $id === $targetSystemSettingId);

            if ($canAssignInPlace) {
                DB::table('support_whatsapp_numbers')
                    ->where('id', $numberId)
                    ->update([
                        'system_setting_id' => $targetSystemSettingId,
                    ]);

                continue;
            }

            $targetCopyId = $this->findMatchingSupportWhatsappNumberId($number, $copyColumns, $targetSystemSettingId);

            if (! $targetCopyId) {
                $payload = [];

                foreach ($copyColumns as $column) {
                    $payload[$column] = $number->{$column};
                }

                $payload['system_setting_id'] = $targetSystemSettingId;
                $payload['created_at'] = $now;
                $payload['updated_at'] = $now;

                $targetCopyId = DB::table('support_whatsapp_numbers')->insertGetId($payload);
            }

            DB::table('courses')
                ->whereIn('id', $orphanCourseIds)
                ->where('support_whatsapp_number_id', $numberId)
                ->update([
                    'support_whatsapp_number_id' => $targetCopyId,
                ]);
        }
    }

    /**
     * @param  Collection<int, int>  $orphanCourseIds
     */
    private function backfillUsers(Collection $orphanCourseIds, int $targetSystemSettingId): void
    {
        $candidateUserIds = collect()
            ->merge(
                DB::table('courses')
                    ->whereIn('id', $orphanCourseIds)
                    ->whereNotNull('owner_id')
                    ->pluck('owner_id')
            )
            ->merge(
                DB::table('enrollments')
                    ->whereIn('course_id', $orphanCourseIds)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
            )
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($candidateUserIds->isEmpty()) {
            return;
        }

        DB::table('users')
            ->whereIn('id', $candidateUserIds)
            ->whereNull('system_setting_id')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $user) use ($targetSystemSettingId): void {
                $tenantIds = collect()
                    ->merge(
                        DB::table('courses')
                            ->where('owner_id', $user->id)
                            ->whereNotNull('system_setting_id')
                            ->pluck('system_setting_id')
                    )
                    ->merge(
                        DB::table('enrollments')
                            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                            ->where('enrollments.user_id', $user->id)
                            ->whereNotNull('courses.system_setting_id')
                            ->pluck('courses.system_setting_id')
                    )
                    ->map(fn (mixed $id): int => (int) $id)
                    ->unique()
                    ->values();

                if ($tenantIds->count() === 1 && $tenantIds->first() === $targetSystemSettingId) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'system_setting_id' => $targetSystemSettingId,
                        ]);
                }
            });
    }

    /**
     * @param  array<int, string>  $copyColumns
     */
    private function findMatchingSupportWhatsappNumberId(object $number, array $copyColumns, int $targetSystemSettingId): ?int
    {
        $query = DB::table('support_whatsapp_numbers')
            ->where('system_setting_id', $targetSystemSettingId);

        foreach ($copyColumns as $column) {
            $value = $number->{$column};

            if ($value === null) {
                $query->whereNull($column);

                continue;
            }

            $query->where($column, $value);
        }

        $match = $query
            ->orderBy('id')
            ->value('id');

        return $match !== null ? (int) $match : null;
    }

    private function normalizeDomain(mixed $value): ?string
    {
        $candidate = trim(mb_strtolower((string) $value, 'UTF-8'));

        return $candidate !== '' ? $candidate : null;
    }
};
