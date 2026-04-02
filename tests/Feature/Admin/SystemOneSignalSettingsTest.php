<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SystemAssetsManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class SystemOneSignalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_system_page_renders_onesignal_section(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.system.edit'));

        $response->assertOk();
        $response->assertSee('Notificações Push', false);
        $response->assertSee('OneSignal App ID', false);
        $response->assertSee('Chave REST API', false);
        $response->assertSee('Enviar push de teste', false);
    }

    public function test_admin_can_save_onesignal_settings_and_keep_existing_rest_key(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('onesignal_app_id', '11111111-1111-1111-1111-111111111111')
            ->set('onesignal_rest_api_key', 'rest-secret-alpha')
            ->call('saveOneSignalSettings')
            ->assertHasNoErrors();

        $setting = $admin->systemSetting->fresh();

        $this->assertSame('11111111-1111-1111-1111-111111111111', $setting->onesignal_app_id);
        $this->assertSame('rest-secret-alpha', $setting->onesignal_rest_api_key);
        $this->assertNotSame(
            'rest-secret-alpha',
            (string) DB::table('system_settings')->where('id', $setting->id)->value('onesignal_rest_api_key')
        );

        Livewire::test(SystemAssetsManager::class)
            ->set('onesignal_app_id', '11111111-1111-1111-1111-111111111111')
            ->set('onesignal_rest_api_key', '')
            ->call('saveOneSignalSettings')
            ->assertHasNoErrors();

        $this->assertSame('rest-secret-alpha', $setting->fresh()->onesignal_rest_api_key);
    }

    public function test_admin_can_save_long_onesignal_rest_key(): void
    {
        $admin = User::factory()->admin()->create();
        $restApiKey = 'os_v2_app_'.str_repeat('segment', 250);

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('onesignal_app_id', '12345678-1234-1234-1234-123456789012')
            ->set('onesignal_rest_api_key', $restApiKey)
            ->call('saveOneSignalSettings')
            ->assertHasNoErrors();

        $this->assertSame($restApiKey, $admin->systemSetting->fresh()->onesignal_rest_api_key);
    }

    public function test_super_admin_can_edit_selected_tenant_onesignal_settings(): void
    {
        $superAdmin = $this->bootstrapSuperAdmin();
        $tenantAdmin = User::factory()->admin()->create([
            'email' => 'tenant-onesignal-owner@example.com',
        ]);
        $tenant = $tenantAdmin->systemSetting()->withoutGlobalScopes()->firstOrFail();

        $this->actingAs($superAdmin);

        Livewire::test(SystemAssetsManager::class, ['systemSettingId' => $tenant->id])
            ->set('onesignal_app_id', '22222222-2222-2222-2222-222222222222')
            ->set('onesignal_rest_api_key', 'rest-secret-tenant')
            ->call('saveOneSignalSettings')
            ->assertHasNoErrors();

        $tenant->refresh();

        $this->assertSame('22222222-2222-2222-2222-222222222222', $tenant->onesignal_app_id);
        $this->assertSame('rest-secret-tenant', $tenant->onesignal_rest_api_key);
    }

    public function test_test_push_only_lists_and_accepts_students_from_the_same_tenant(): void
    {
        Http::fake([
            'https://api.onesignal.com/*' => Http::response(['id' => 'push-test-1'], 200),
        ]);

        $admin = $this->defaultTenantAdmin();
        $tenantStudent = $this->defaultTenantStudent([
            'email' => 'tenant-student-push@example.com',
            'name' => 'Aluno Tenant',
        ]);

        $otherAdmin = $this->createAdminForTenant(
            ['email' => 'other-tenant-admin-push@example.com'],
            ['domain' => 'cursos.other-push.test', 'escola_nome' => 'Outra Escola']
        );
        $otherStudent = $this->createStudentForTenant($otherAdmin, [
            'email' => 'other-tenant-student-push@example.com',
            'name' => 'Aluno Outro Tenant',
        ]);

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->assertSee($tenantStudent->email)
            ->assertDontSee($otherStudent->email)
            ->set('onesignal_app_id', '33333333-3333-3333-3333-333333333333')
            ->set('onesignal_rest_api_key', 'rest-secret-test')
            ->set('test_push_user_id', (string) $tenantStudent->id)
            ->call('sendTestPush')
            ->assertHasNoErrors();

        Http::assertSent(function ($request) use ($tenantStudent): bool {
            $data = $request->data();
            $targetUrl = $tenantStudent->systemSetting->appUrl(route('learning.notifications.index', absolute: false));

            return $request->url() === 'https://api.onesignal.com/notifications?c=push'
                && $data['app_id'] === '33333333-3333-3333-3333-333333333333'
                && $data['include_aliases']['external_id'] === [$tenantStudent->oneSignalExternalId()]
                && ! array_key_exists('url', $data)
                && $data['app_url'] === $targetUrl
                && $data['web_url'] === $targetUrl;
        });

        Livewire::test(SystemAssetsManager::class)
            ->set('onesignal_app_id', '33333333-3333-3333-3333-333333333333')
            ->set('onesignal_rest_api_key', 'rest-secret-test')
            ->set('test_push_user_id', (string) $otherStudent->id)
            ->call('sendTestPush')
            ->assertHasErrors(['test_push_user_id']);
    }

    public function test_test_push_logs_safe_diagnostics_without_exposing_full_rest_key(): void
    {
        Http::fake([
            'https://api.onesignal.com/*' => Http::response(['id' => 'push-test-logged'], 200),
        ]);

        Log::spy();

        $admin = $this->createAdminForTenant(
            ['email' => 'tenant-admin-logged-push@example.com'],
            ['domain' => 'cursos.tenant-logged-push.test', 'escola_nome' => 'Escola Logged Push']
        );
        $tenantStudent = $this->defaultTenantStudent([
            'email' => 'tenant-student-logged-push@example.com',
            'name' => 'Aluno Logado',
        ]);
        $restApiKey = 'os_v2_app_rest-secret-test';

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('onesignal_app_id', '44444444-4444-4444-4444-444444444444')
            ->set('onesignal_rest_api_key', $restApiKey)
            ->set('test_push_user_id', (string) $tenantStudent->id)
            ->call('sendTestPush')
            ->assertHasNoErrors();

        $expectedHash = hash('sha256', $restApiKey);
        $expectedLength = strlen($restApiKey);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($admin, $tenantStudent, $expectedHash, $expectedLength): bool {
            return $message === 'onesignal.test_push_requested'
                && ($context['actor_user_id'] ?? null) === $admin->id
                && ($context['student_user_id'] ?? null) === $tenantStudent->id
                && ($context['rest_api_key_source'] ?? null) === 'typed'
                && ($context['onesignal_rest_api_key_length'] ?? null) === $expectedLength
                && ($context['onesignal_rest_api_key_sha256'] ?? null) === $expectedHash
                && ! array_key_exists('onesignal_rest_api_key', $context);
        })->once();

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($expectedHash, $expectedLength): bool {
            return $message === 'onesignal.push_sent'
                && ($context['response_status'] ?? null) === 200
                && ($context['onesignal_rest_api_key_length'] ?? null) === $expectedLength
                && ($context['onesignal_rest_api_key_sha256'] ?? null) === $expectedHash
                && ! array_key_exists('onesignal_rest_api_key', $context);
        })->once();
    }

    private function bootstrapSuperAdmin(): User
    {
        return User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', ['sampaio.free@gmail.com'])
            ->firstOrFail();
    }
}
