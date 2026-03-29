<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EMAIL = 'sampaio.free@gmail.com';

    private const PASSWORD = 'admin123';

    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('system_settings')) {
            return;
        }

        $now = now();
        $systemSettingId = $this->resolveCanonicalSystemSettingId($now);
        $matches = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [self::EMAIL])
            ->select(['id', 'system_setting_id', 'email_verified_at'])
            ->orderBy('id')
            ->get();
        $hashedPassword = Hash::make(self::PASSWORD);

        if ($matches->isEmpty()) {
            DB::table('users')->insert([
                'name' => 'Administrador',
                'email' => self::EMAIL,
                'password' => $hashedPassword,
                'role' => UserRole::ADMIN->value,
                'system_setting_id' => $systemSettingId,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        foreach ($matches as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'email' => self::EMAIL,
                    'password' => $hashedPassword,
                    'role' => UserRole::ADMIN->value,
                    'email_verified_at' => $user->email_verified_at ?: $now,
                    'system_setting_id' => $user->system_setting_id ?: $systemSettingId,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        //
    }

    private function resolveCanonicalSystemSettingId(object $now): int
    {
        $systemSettingId = DB::table('system_settings')
            ->orderBy('id')
            ->value('id');

        if ($systemSettingId) {
            return (int) $systemSettingId;
        }

        return (int) DB::table('system_settings')->insertGetId([
            'domain' => $this->defaultHost(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function defaultHost(): string
    {
        $host = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST);
        $normalized = is_string($host) ? trim(mb_strtolower($host, 'UTF-8')) : '';

        return $normalized !== '' ? $normalized : 'localhost';
    }
};
