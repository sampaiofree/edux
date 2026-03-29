<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureSystemSettingColumn('users', 'role');
        $this->ensureSystemSettingColumn('courses', 'id');
        $this->ensureSystemSettingColumn('payment_webhook_links', 'id');
        $this->ensureSystemSettingColumn('enrollments', 'id');
        $this->ensureSystemSettingColumn('support_whatsapp_numbers', 'id');
        $this->ensureSystemSettingColumn('notifications', 'id');
        $this->ensureSystemSettingColumn('certificate_brandings', 'id');

        $this->backfillSystemSettings();
        $this->backfillTenantOwnership();

        $this->ensureUsersEmailUniquePerTenant();
    }

    public function down(): void
    {
        if ($this->hasIndexNamed('users', 'users_system_setting_id_email_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_system_setting_id_email_unique');
            });
        }

        if (! $this->hasIndexNamed('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('email');
            });
        }

        $this->dropSystemSettingColumn('users');
        $this->dropSystemSettingColumn('courses');
        $this->dropSystemSettingColumn('payment_webhook_links');
        $this->dropSystemSettingColumn('enrollments');
        $this->dropSystemSettingColumn('support_whatsapp_numbers');
        $this->dropSystemSettingColumn('notifications');
        $this->dropSystemSettingColumn('certificate_brandings');
    }

    private function backfillSystemSettings(): void
    {
        $now = now();
        $primaryHost = $this->defaultHost();
        $template = DB::table('system_settings')->orderBy('id')->first();
        $templateData = $template ? $this->systemSettingTemplateData($template) : [];
        $admins = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->get();
        $settings = DB::table('system_settings')
            ->orderBy('id')
            ->get();
        $usedDomains = $settings
            ->pluck('domain')
            ->filter()
            ->map(fn ($value) => mb_strtolower((string) $value, 'UTF-8'))
            ->values()
            ->all();

        if ($admins->isEmpty() && $settings->isEmpty()) {
            DB::table('system_settings')->insert(array_merge($templateData, [
                'domain' => $primaryHost,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            return;
        }

        foreach ($admins as $index => $admin) {
            $existing = $settings->firstWhere('owner_user_id', $admin->id);

            if ($existing) {
                $domain = $this->firstAvailableDomain(
                    $this->normalizeDomain((string) ($existing->domain ?: '')) ?: ($index === 0 ? $primaryHost : "{$primaryHost}-{$admin->id}"),
                    $usedDomains,
                    $primaryHost,
                    (int) $admin->id
                );

                DB::table('system_settings')
                    ->where('id', $existing->id)
                    ->update([
                        'domain' => $domain,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            $unowned = $settings->first(fn ($setting) => $setting->owner_user_id === null);
            $domain = $this->firstAvailableDomain(
                $index === 0 ? $primaryHost : "{$primaryHost}-{$admin->id}",
                $usedDomains,
                $primaryHost,
                (int) $admin->id
            );

            if ($unowned) {
                DB::table('system_settings')
                    ->where('id', $unowned->id)
                    ->update([
                        'owner_user_id' => $admin->id,
                        'domain' => $domain,
                        'updated_at' => $now,
                    ]);

                $settings = DB::table('system_settings')->orderBy('id')->get();

                continue;
            }

            $systemSettingId = DB::table('system_settings')->insertGetId(array_merge($templateData, [
                'owner_user_id' => $admin->id,
                'domain' => $domain,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            $settings = $settings->push((object) array_merge($templateData, [
                'id' => $systemSettingId,
                'owner_user_id' => $admin->id,
                'domain' => $domain,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        if (DB::table('system_settings')->count() === 0) {
            DB::table('system_settings')->insert(array_merge($templateData, [
                'domain' => $primaryHost,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function backfillTenantOwnership(): void
    {
        $settingsByOwner = DB::table('system_settings')
            ->whereNotNull('owner_user_id')
            ->pluck('id', 'owner_user_id');
        $firstSystemSettingId = DB::table('system_settings')->orderBy('id')->value('id');

        DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->get()
            ->each(function ($admin) use ($settingsByOwner, $firstSystemSettingId): void {
                $systemSettingId = $settingsByOwner[$admin->id] ?? $firstSystemSettingId;

                DB::table('users')
                    ->where('id', $admin->id)
                    ->update([
                        'system_setting_id' => $systemSettingId,
                    ]);
            });

        DB::table('courses')
            ->leftJoin('users as owners', 'owners.id', '=', 'courses.owner_id')
            ->select('courses.id as course_id', 'owners.system_setting_id')
            ->orderBy('courses.id')
            ->get()
            ->each(function ($row) use ($firstSystemSettingId): void {
                DB::table('courses')
                    ->where('id', $row->course_id)
                    ->update([
                        'system_setting_id' => $row->system_setting_id ?: $firstSystemSettingId,
                    ]);
            });

        DB::table('payment_webhook_links')
            ->leftJoin('users as creators', 'creators.id', '=', 'payment_webhook_links.created_by')
            ->select('payment_webhook_links.id as link_id', 'creators.system_setting_id')
            ->orderBy('payment_webhook_links.id')
            ->get()
            ->each(function ($row) use ($firstSystemSettingId): void {
                DB::table('payment_webhook_links')
                    ->where('id', $row->link_id)
                    ->update([
                        'system_setting_id' => $row->system_setting_id ?: $firstSystemSettingId,
                    ]);
            });

        $this->backfillSupportWhatsappNumbers($firstSystemSettingId);
        $this->backfillCertificateBrandings($firstSystemSettingId);

        DB::table('users')
            ->whereNull('system_setting_id')
            ->orderBy('id')
            ->get()
            ->each(function ($user) use ($firstSystemSettingId): void {
                $systemSettingId = DB::table('enrollments')
                    ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                    ->where('enrollments.user_id', $user->id)
                    ->value('courses.system_setting_id');

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'system_setting_id' => $systemSettingId ?: $firstSystemSettingId,
                    ]);
            });

        DB::table('enrollments')
            ->leftJoin('courses', 'courses.id', '=', 'enrollments.course_id')
            ->leftJoin('users', 'users.id', '=', 'enrollments.user_id')
            ->select('enrollments.id as enrollment_id', 'courses.system_setting_id as course_system_setting_id', 'users.system_setting_id as user_system_setting_id')
            ->orderBy('enrollments.id')
            ->get()
            ->each(function ($row) use ($firstSystemSettingId): void {
                DB::table('enrollments')
                    ->where('id', $row->enrollment_id)
                    ->update([
                        'system_setting_id' => $row->course_system_setting_id ?: $row->user_system_setting_id ?: $firstSystemSettingId,
                    ]);
            });

        DB::table('notifications')
            ->whereNull('system_setting_id')
            ->update([
                'system_setting_id' => $firstSystemSettingId,
            ]);
    }

    private function backfillSupportWhatsappNumbers(?int $firstSystemSettingId): void
    {
        if (! $firstSystemSettingId) {
            return;
        }

        $now = now();
        $copyColumns = collect(Schema::getColumnListing('support_whatsapp_numbers'))
            ->reject(fn ($column) => in_array($column, ['id', 'system_setting_id', 'created_at', 'updated_at'], true))
            ->values()
            ->all();

        DB::table('support_whatsapp_numbers')
            ->orderBy('id')
            ->get()
            ->each(function ($number) use ($copyColumns, $firstSystemSettingId, $now): void {
                $systemSettingIds = DB::table('courses')
                    ->where('support_whatsapp_number_id', $number->id)
                    ->whereNotNull('system_setting_id')
                    ->distinct()
                    ->pluck('system_setting_id')
                    ->filter()
                    ->values();

                if ($systemSettingIds->isEmpty()) {
                    DB::table('support_whatsapp_numbers')
                        ->where('id', $number->id)
                        ->update([
                            'system_setting_id' => $firstSystemSettingId,
                        ]);

                    return;
                }

                $primarySystemSettingId = $systemSettingIds->shift();

                DB::table('support_whatsapp_numbers')
                    ->where('id', $number->id)
                    ->update([
                        'system_setting_id' => $primarySystemSettingId,
                    ]);

                foreach ($systemSettingIds as $systemSettingId) {
                    $payload = [];

                    foreach ($copyColumns as $column) {
                        $payload[$column] = $number->{$column};
                    }

                    $payload['system_setting_id'] = $systemSettingId;
                    $payload['created_at'] = $now;
                    $payload['updated_at'] = $now;

                    $duplicateId = DB::table('support_whatsapp_numbers')->insertGetId($payload);

                    DB::table('courses')
                        ->where('support_whatsapp_number_id', $number->id)
                        ->where('system_setting_id', $systemSettingId)
                        ->update([
                            'support_whatsapp_number_id' => $duplicateId,
                        ]);
                }
            });
    }

    private function backfillCertificateBrandings(?int $firstSystemSettingId): void
    {
        if (! $firstSystemSettingId) {
            return;
        }

        $now = now();
        $copyColumns = collect(Schema::getColumnListing('certificate_brandings'))
            ->reject(fn ($column) => in_array($column, ['id', 'system_setting_id', 'created_at', 'updated_at'], true))
            ->values()
            ->all();

        DB::table('certificate_brandings')
            ->whereNotNull('course_id')
            ->leftJoin('courses', 'courses.id', '=', 'certificate_brandings.course_id')
            ->select('certificate_brandings.id as branding_id', 'courses.system_setting_id')
            ->orderBy('certificate_brandings.id')
            ->get()
            ->each(function ($row) use ($firstSystemSettingId): void {
                DB::table('certificate_brandings')
                    ->where('id', $row->branding_id)
                    ->update([
                        'system_setting_id' => $row->system_setting_id ?: $firstSystemSettingId,
                    ]);
            });

        $globalBranding = DB::table('certificate_brandings')
            ->whereNull('course_id')
            ->orderBy('id')
            ->first();
        $systemSettingIds = DB::table('system_settings')
            ->orderBy('id')
            ->pluck('id');

        if (! $globalBranding && $systemSettingIds->isEmpty()) {
            return;
        }

        if ($globalBranding) {
            $firstTenantId = $systemSettingIds->shift() ?: $firstSystemSettingId;

            DB::table('certificate_brandings')
                ->where('id', $globalBranding->id)
                ->update([
                    'system_setting_id' => $firstTenantId,
                ]);

            foreach ($systemSettingIds as $systemSettingId) {
                $exists = DB::table('certificate_brandings')
                    ->whereNull('course_id')
                    ->where('system_setting_id', $systemSettingId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $payload = [];

                foreach ($copyColumns as $column) {
                    $payload[$column] = $globalBranding->{$column};
                }

                $payload['system_setting_id'] = $systemSettingId;
                $payload['created_at'] = $now;
                $payload['updated_at'] = $now;

                DB::table('certificate_brandings')->insert($payload);
            }

            return;
        }

        foreach ($systemSettingIds as $systemSettingId) {
            DB::table('certificate_brandings')->insert([
                'course_id' => null,
                'system_setting_id' => $systemSettingId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function systemSettingTemplateData(object $template): array
    {
        $copyColumns = collect(Schema::getColumnListing('system_settings'))
            ->reject(fn ($column) => in_array($column, ['id', 'owner_user_id', 'domain', 'created_at', 'updated_at'], true))
            ->values()
            ->all();

        $payload = [];

        foreach ($copyColumns as $column) {
            $payload[$column] = $template->{$column};
        }

        return $payload;
    }

    private function defaultHost(): string
    {
        $appUrl = env('APP_URL', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST);
        $normalized = $this->normalizeDomain(is_string($host) ? $host : $appUrl);

        return $normalized ?: 'localhost';
    }

    private function firstAvailableDomain(string $preferred, array &$usedDomains, string $primaryHost, int $adminId): string
    {
        $candidate = $this->normalizeDomain($preferred) ?: $primaryHost;

        if (! in_array($candidate, $usedDomains, true)) {
            $usedDomains[] = $candidate;

            return $candidate;
        }

        $base = "tenant-{$adminId}.{$primaryHost}";
        $normalizedBase = $this->normalizeDomain($base) ?: "tenant-{$adminId}.localhost";

        if (! in_array($normalizedBase, $usedDomains, true)) {
            $usedDomains[] = $normalizedBase;

            return $normalizedBase;
        }

        $counter = 2;

        do {
            $generated = $this->normalizeDomain("tenant-{$adminId}-{$counter}.{$primaryHost}") ?: "tenant-{$adminId}-{$counter}.localhost";
            $counter++;
        } while (in_array($generated, $usedDomains, true));

        $usedDomains[] = $generated;

        return $generated;
    }

    private function normalizeDomain(string $value): ?string
    {
        $candidate = trim($value);

        if ($candidate === '') {
            return null;
        }

        if (str_contains($candidate, '://')) {
            $candidate = (string) parse_url($candidate, PHP_URL_HOST);
        } else {
            $candidate = preg_replace('#/.*$#', '', $candidate) ?? $candidate;
            $candidate = preg_replace('#:\\d+$#', '', $candidate) ?? $candidate;
        }

        $candidate = trim(mb_strtolower($candidate, 'UTF-8'));

        return $candidate !== '' ? $candidate : null;
    }

    private function ensureSystemSettingColumn(string $tableName, string $afterColumn): void
    {
        if (! Schema::hasColumn($tableName, 'system_setting_id')) {
            Schema::table($tableName, function (Blueprint $table) use ($afterColumn): void {
                $table->foreignId('system_setting_id')
                    ->nullable()
                    ->after($afterColumn);
            });
        }

        if (! $this->hasIndexForColumns($tableName, ['system_setting_id'])) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index('system_setting_id');
            });
        }

        if (! $this->hasForeignKeyForColumns($tableName, ['system_setting_id'], 'system_settings', ['id'])) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->foreign('system_setting_id', "{$tableName}_system_setting_id_foreign")
                    ->references('id')
                    ->on('system_settings')
                    ->nullOnDelete();
            });
        }
    }

    private function ensureUsersEmailUniquePerTenant(): void
    {
        if ($this->hasIndexForColumns('users', ['system_setting_id', 'email'], true)) {
            return;
        }

        if ($this->hasIndexNamed('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_email_unique');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['system_setting_id', 'email']);
        });
    }

    private function dropSystemSettingColumn(string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'system_setting_id')) {
            return;
        }

        foreach ($this->foreignKeyNamesForColumn($tableName, 'system_setting_id') as $foreignKeyName) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKeyName): void {
                $table->dropForeign($foreignKeyName);
            });
        }

        if ($this->hasIndexNamed($tableName, "{$tableName}_system_setting_id_index")) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("{$tableName}_system_setting_id_index");
            });
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropColumn('system_setting_id');
        });
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>|null  $foreignColumns
     */
    private function hasForeignKeyForColumns(string $tableName, array $columns, ?string $foreignTable = null, ?array $foreignColumns = null): bool
    {
        foreach (Schema::getForeignKeys($tableName) as $foreignKey) {
            $matchesColumns = array_values($foreignKey['columns'] ?? []) === $columns;
            $matchesTable = $foreignTable === null || ($foreignKey['foreign_table'] ?? null) === $foreignTable;
            $matchesForeignColumns = $foreignColumns === null || array_values($foreignKey['foreign_columns'] ?? []) === $foreignColumns;

            if ($matchesColumns && $matchesTable && $matchesForeignColumns) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function foreignKeyNamesForColumn(string $tableName, string $column): array
    {
        return collect(Schema::getForeignKeys($tableName))
            ->filter(fn (array $foreignKey) => array_values($foreignKey['columns'] ?? []) === [$column])
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->values()
            ->all();
    }

    private function hasIndexNamed(string $tableName, string $indexName): bool
    {
        return in_array($indexName, Schema::getIndexListing($tableName), true);
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasIndexForColumns(string $tableName, array $columns, ?bool $unique = null): bool
    {
        foreach (Schema::getIndexes($tableName) as $index) {
            $matchesColumns = array_values($index['columns'] ?? []) === $columns;
            $matchesUnique = $unique === null || (bool) ($index['unique'] ?? false) === $unique;

            if ($matchesColumns && $matchesUnique) {
                return true;
            }
        }

        return false;
    }
};
