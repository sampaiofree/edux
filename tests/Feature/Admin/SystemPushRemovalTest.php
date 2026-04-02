<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SystemAssetsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemPushRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_system_page_no_longer_renders_push_settings(): void
    {
        $admin = $this->defaultTenantAdmin();

        $response = $this->actingAs($admin)->get(route('admin.system.edit'));

        $response->assertOk();
        $response->assertDontSee('Notificações Push', false);
        $response->assertDontSee('Chave REST API', false);
        $response->assertDontSee('Enviar push de teste', false);
    }

    public function test_system_assets_manager_still_saves_mail_settings_without_push_fields(): void
    {
        $admin = $this->defaultTenantAdmin();

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('mail_mailer', 'log')
            ->set('mail_from_address', 'contato@example.com')
            ->set('mail_from_name', 'Escola Teste')
            ->call('saveMailSettings')
            ->assertHasNoErrors();

        $setting = $admin->systemSetting->fresh();

        $this->assertSame('log', $setting->mail_mailer);
        $this->assertSame('contato@example.com', $setting->mail_from_address);
        $this->assertSame('Escola Teste', $setting->mail_from_name);
    }
}
