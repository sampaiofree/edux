<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminBootstrapMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'sampaio.free@gmail.com';

    private const PASSWORD = 'admin123';

    private const MIGRATION_PATH = 'database/migrations/2026_03_29_150000_bootstrap_global_super_admin_user.php';

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_migration_bootstraps_super_admin_user_from_empty_state(): void
    {
        DB::table('users')->whereRaw('LOWER(email) = ?', [self::EMAIL])->delete();
        DB::table('system_settings')->delete();

        $this->runBootstrapMigration();

        $user = $this->bootstrapUsers()->sole();

        $this->assertSame(UserRole::ADMIN, $user->role);
        $this->assertNotNull($user->system_setting_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->getAuthPassword()));
        $this->assertDatabaseCount('system_settings', 1);

        $targetTenant = $this->createTargetTenant('cursos.bootstrap-target.test', 'Bootstrap Target');

        $this->forceTestHost($targetTenant->domain)
            ->post('http://'.$targetTenant->domain.'/login', [
                'email' => self::EMAIL,
                'password' => self::PASSWORD,
            ])
            ->assertRedirect('http://'.$targetTenant->domain.'/admin/dashboard');

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_migration_updates_all_existing_super_admin_matches_across_tenants(): void
    {
        DB::table('users')->whereRaw('LOWER(email) = ?', [self::EMAIL])->delete();

        $tenantA = $this->createTenantSetting('cursos.duplicate-alpha.test', 'Duplicate Alpha');
        $tenantB = $this->createTenantSetting('cursos.duplicate-beta.test', 'Duplicate Beta');

        $userA = User::factory()->student()->create([
            'name' => 'Alpha User',
            'email' => self::EMAIL,
            'password' => 'old-alpha-password',
            'role' => UserRole::STUDENT->value,
            'system_setting_id' => $tenantA->id,
            'email_verified_at' => null,
        ]);
        $userB = User::factory()->student()->create([
            'name' => 'Beta User',
            'email' => 'SAMPAIO.FREE@GMAIL.COM',
            'password' => 'old-beta-password',
            'role' => UserRole::STUDENT->value,
            'system_setting_id' => $tenantB->id,
            'email_verified_at' => now()->subDay(),
        ]);

        $this->runBootstrapMigration();

        $users = $this->bootstrapUsers()->sortBy('id')->values();

        $this->assertCount(2, $users);
        $this->assertSame(['Alpha User', 'Beta User'], $users->pluck('name')->all());
        $this->assertSame([$tenantA->id, $tenantB->id], $users->pluck('system_setting_id')->all());

        foreach ($users as $user) {
            $this->assertSame(self::EMAIL, $user->email);
            $this->assertSame(UserRole::ADMIN, $user->role);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue(Hash::check(self::PASSWORD, $user->getAuthPassword()));
        }

        $this->assertNotSame($userA->getAuthPassword(), $users[0]->getAuthPassword());
        $this->assertNotSame($userB->getAuthPassword(), $users[1]->getAuthPassword());
    }

    public function test_migration_fills_missing_system_setting_id_for_existing_super_admin_match(): void
    {
        DB::table('users')->whereRaw('LOWER(email) = ?', [self::EMAIL])->delete();

        $canonicalSystemSettingId = (int) SystemSetting::query()->orderBy('id')->value('id');

        DB::table('users')->insert([
            'name' => 'Sem Vinculo',
            'email' => self::EMAIL,
            'password' => Hash::make('old-password'),
            'role' => UserRole::STUDENT->value,
            'system_setting_id' => null,
            'email_verified_at' => null,
            'remember_token' => null,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $this->runBootstrapMigration();

        $user = $this->bootstrapUsers()->sole();

        $this->assertSame($canonicalSystemSettingId, $user->system_setting_id);
        $this->assertSame(UserRole::ADMIN, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->getAuthPassword()));
    }

    private function runBootstrapMigration(): void
    {
        $migration = require base_path(self::MIGRATION_PATH);
        $migration->up();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function bootstrapUsers()
    {
        return User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [self::EMAIL])
            ->orderBy('id')
            ->get();
    }

    private function createTargetTenant(string $domain, string $schoolName): SystemSetting
    {
        $setting = $this->createTenantSetting($domain, $schoolName);
        $admin = User::factory()->admin()->create([
            'email' => str_replace('@', '+'.$setting->id.'@', 'tenant-admin@example.com'),
            'system_setting_id' => $setting->id,
        ]);

        $setting->forceFill([
            'owner_user_id' => $admin->id,
        ])->save();

        return $setting->fresh();
    }

    private function createTenantSetting(string $domain, string $schoolName): SystemSetting
    {
        return SystemSetting::create([
            'domain' => $domain,
            'escola_nome' => $schoolName,
        ]);
    }
}
