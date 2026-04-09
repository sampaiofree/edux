<?php

namespace Tests;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    protected ?User $defaultTenantAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://cursos.example.test');
        $this->defaultTenantAdmin = null;
        $this->forceTestHost($this->defaultTenantDomain());
    }

    protected function defaultTenantDomain(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'cursos.example.test';
    }

    protected function forceTestHost(string $domain): static
    {
        $this->withServerVariables([
            'HTTP_HOST' => $domain,
            'SERVER_NAME' => $domain,
        ]);

        URL::forceRootUrl('http://'.$domain);

        return $this;
    }

    protected function defaultTenantAdmin(): User
    {
        if (! $this->defaultTenantAdmin) {
            $this->defaultTenantAdmin = $this->createAdminForTenant();
        }

        return $this->defaultTenantAdmin->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $settingAttributes
     */
    protected function createAdminForTenant(array $attributes = [], array $settingAttributes = []): User
    {
        $admin = User::factory()->admin()->create($attributes);

        $this->syncTenantForAdmin($admin, $settingAttributes);

        return $admin->fresh();
    }

    /**
     * @param  array<string, mixed>  $settingAttributes
     */
    protected function syncTenantForAdmin(User $admin, array $settingAttributes = []): SystemSetting
    {
        $admin = $admin->fresh();
        $setting = $admin->systemSetting()->withoutGlobalScopes()->firstOrFail();

        $setting->forceFill(array_merge([
            'domain' => $this->defaultTenantDomain(),
            'escola_nome' => $setting->escola_nome ?: 'Escola Teste',
        ], $settingAttributes))->save();

        $admin->forceFill([
            'system_setting_id' => $setting->id,
        ])->saveQuietly();

        return $setting->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createStudentForTenant(User $admin, array $attributes = []): User
    {
        return User::factory()->student()->create(array_merge([
            'system_setting_id' => $admin->system_setting_id,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createTeacherForTenant(User $admin, array $attributes = []): User
    {
        return User::factory()->teacher()->create(array_merge([
            'system_setting_id' => $admin->system_setting_id,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function defaultTenantStudent(array $attributes = []): User
    {
        return $this->createStudentForTenant($this->defaultTenantAdmin(), $attributes);
    }
}
