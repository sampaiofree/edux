<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SystemAssetsManager;
use App\Mail\SystemMailTestMessage;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\Mail\TenantMailManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SystemMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_system_page_renders_mail_settings_section(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.system.edit'));

        $response->assertOk();
        $response->assertSee('Configurações de e-mail', false);
        $response->assertSee('Mailer', false);
        $response->assertSee('Remetente (e-mail)', false);
        $response->assertSee('Salvar e-mail', false);
        $response->assertSee('E-mail para teste', false);
        $response->assertSee('Enviar e-mail de teste', false);
    }

    public function test_smtp_fields_are_rendered_when_mailer_is_switched_to_smtp(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('mail_mailer', 'smtp')
            ->assertSee('Host SMTP', false)
            ->assertSee('Porta SMTP', false)
            ->assertSee('Usuário SMTP', false)
            ->assertSee('Senha SMTP', false);
    }

    public function test_admin_can_save_tenant_mail_settings_and_keep_existing_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('mail_mailer', 'smtp')
            ->set('mail_scheme', 'tls')
            ->set('mail_host', 'smtp.escola.test')
            ->set('mail_port', '587')
            ->set('mail_username', 'contato@escola.test')
            ->set('mail_password', 'segredo-smtp')
            ->set('mail_from_address', 'contato@escola.test')
            ->set('mail_from_name', 'Escola Alpha')
            ->call('saveMailSettings')
            ->assertHasNoErrors();

        $setting = $admin->systemSetting->fresh();

        $this->assertSame('smtp', $setting->mail_mailer);
        $this->assertSame('tls', $setting->mail_scheme);
        $this->assertSame('smtp.escola.test', $setting->mail_host);
        $this->assertSame(587, $setting->mail_port);
        $this->assertSame('contato@escola.test', $setting->mail_username);
        $this->assertSame('contato@escola.test', $setting->mail_from_address);
        $this->assertSame('Escola Alpha', $setting->mail_from_name);
        $this->assertSame('segredo-smtp', $setting->mail_password);
        $this->assertNotSame(
            'segredo-smtp',
            (string) DB::table('system_settings')->where('id', $setting->id)->value('mail_password')
        );

        Livewire::test(SystemAssetsManager::class)
            ->set('mail_mailer', 'smtp')
            ->set('mail_scheme', 'tls')
            ->set('mail_host', 'smtp.escola.test')
            ->set('mail_port', '587')
            ->set('mail_username', 'contato@escola.test')
            ->set('mail_password', '')
            ->set('mail_from_address', 'contato@escola.test')
            ->set('mail_from_name', 'Escola Alpha Atualizada')
            ->call('saveMailSettings')
            ->assertHasNoErrors();

        $setting->refresh();

        $this->assertSame('Escola Alpha Atualizada', $setting->mail_from_name);
        $this->assertSame('segredo-smtp', $setting->mail_password);
    }

    public function test_admin_can_save_valid_courses_subdomain(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('domain', 'CuRsOs.Dominio.com.br')
            ->set('escola_nome', 'Escola Dominio')
            ->set('escola_cnpj', '12.345.678/0001-90')
            ->call('saveSchoolIdentity')
            ->assertHasNoErrors();

        $this->assertSame('cursos.dominio.com.br', $admin->systemSetting->fresh()->domain);
    }

    #[DataProvider('invalidDomainProvider')]
    public function test_admin_cannot_save_invalid_system_domain(string $domain, string $expectedMessage): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('domain', $domain)
            ->set('escola_nome', 'Escola Dominio')
            ->call('saveSchoolIdentity')
            ->assertHasErrors(['domain'])
            ->assertSee($expectedMessage, false);
    }

    public function test_tenant_mail_manager_builds_mailer_config_from_system_setting(): void
    {
        $admin = User::factory()->admin()->create();
        $setting = $admin->systemSetting;

        $setting->update([
            'mail_mailer' => 'smtp',
            'mail_scheme' => 'tls',
            'mail_host' => 'smtp.mailer.test',
            'mail_port' => 2525,
            'mail_username' => 'mailer-user',
            'mail_password' => 'mailer-secret',
            'mail_from_address' => 'nao-responda@mailer.test',
            'mail_from_name' => 'Mailer Tenant',
        ]);

        $config = app(TenantMailManager::class)->mailerConfigFor($setting->fresh());

        $this->assertIsArray($config);
        $this->assertSame('smtp', $config['transport']);
        $this->assertSame('smtp', $config['scheme']);
        $this->assertTrue($config['require_tls']);
        $this->assertSame('smtp.mailer.test', $config['host']);
        $this->assertSame(2525, $config['port']);
        $this->assertSame('mailer-user', $config['username']);
        $this->assertSame('mailer-secret', $config['password']);
        $this->assertSame('nao-responda@mailer.test', $config['from']['address']);
        $this->assertSame('Mailer Tenant', $config['from']['name']);
    }

    public function test_tenant_mail_manager_maps_ssl_scheme_to_smtps(): void
    {
        $admin = User::factory()->admin()->create();
        $setting = $admin->systemSetting;

        $setting->update([
            'mail_mailer' => 'smtp',
            'mail_scheme' => 'ssl',
            'mail_host' => 'smtp.mailer.test',
            'mail_port' => 465,
            'mail_username' => 'mailer-user',
            'mail_password' => 'mailer-secret',
            'mail_from_address' => 'nao-responda@mailer.test',
            'mail_from_name' => 'Mailer Tenant',
        ]);

        $config = app(TenantMailManager::class)->mailerConfigFor($setting->fresh());

        $this->assertIsArray($config);
        $this->assertSame('smtps', $config['scheme']);
        $this->assertArrayNotHasKey('require_tls', $config);
    }

    public function test_admin_can_send_test_email_with_current_form_values(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@alpha.test',
        ]);

        $this->actingAs($admin);

        Livewire::test(SystemAssetsManager::class)
            ->set('mail_mailer', 'smtp')
            ->set('mail_scheme', 'tls')
            ->set('mail_host', 'smtp.alpha.test')
            ->set('mail_port', '587')
            ->set('mail_username', 'mailer@alpha.test')
            ->set('mail_password', 'segredo-alpha')
            ->set('mail_from_address', 'contato@alpha.test')
            ->set('mail_from_name', 'Escola Alpha')
            ->set('test_email', 'teste@alpha.test')
            ->call('sendTestEmail')
            ->assertHasNoErrors();

        Mail::assertSent(SystemMailTestMessage::class, function (SystemMailTestMessage $mail): bool {
            return $mail->hasTo('teste@alpha.test')
                && $mail->systemSetting->mail_host === 'smtp.alpha.test'
                && $mail->systemSetting->mail_from_name === 'Escola Alpha';
        });
    }

    public function test_super_admin_can_edit_selected_tenant_school_identity_and_owner(): void
    {
        $superAdmin = $this->bootstrapSuperAdmin();
        $tenantAdmin = User::factory()->admin()->create([
            'email' => 'tenant-admin-owner@example.com',
        ]);
        $tenantAdmin->refresh();
        $tenant = $tenantAdmin->systemSetting()->withoutGlobalScopes()->firstOrFail();
        $tenant->update([
            'domain' => 'cursos.tenant-owner.test',
            'escola_nome' => 'Tenant Owner',
        ]);

        $replacementOwner = User::factory()->admin()->create([
            'system_setting_id' => $tenant->id,
            'email' => 'replacement-owner@example.com',
            'name' => 'Replacement Owner',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(SystemAssetsManager::class, ['systemSettingId' => $tenant->id])
            ->set('domain', 'cursos.tenant-owner-editado.test')
            ->set('escola_nome', 'Tenant Owner Editado')
            ->set('escola_cnpj', '12.345.678/0001-90')
            ->set('owner_user_id', (string) $replacementOwner->id)
            ->call('saveSchoolIdentity')
            ->assertHasNoErrors();

        $tenant->refresh();

        $this->assertSame('cursos.tenant-owner-editado.test', $tenant->domain);
        $this->assertSame('Tenant Owner Editado', $tenant->escola_nome);
        $this->assertSame('12.345.678/0001-90', $tenant->escola_cnpj);
        $this->assertSame($replacementOwner->id, $tenant->owner_user_id);
    }

    public function test_super_admin_cannot_assign_owner_from_another_tenant_or_student(): void
    {
        $superAdmin = $this->bootstrapSuperAdmin();
        $tenantAdmin = User::factory()->admin()->create([
            'email' => 'tenant-owner-invalid@example.com',
        ]);
        $tenantAdmin->refresh();
        $tenant = $tenantAdmin->systemSetting()->withoutGlobalScopes()->firstOrFail();
        $tenant->update([
            'domain' => 'cursos.owner-invalid.test',
            'escola_nome' => 'Owner Invalid',
        ]);

        $otherTenantAdmin = User::factory()->admin()->create([
            'email' => 'other-tenant-admin@example.com',
        ]);
        $student = User::factory()->student()->create([
            'system_setting_id' => $tenant->id,
            'email' => 'tenant-student-owner@example.com',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(SystemAssetsManager::class, ['systemSettingId' => $tenant->id])
            ->set('domain', 'cursos.owner-invalid.test')
            ->set('escola_nome', 'Owner Invalid')
            ->set('owner_user_id', (string) $otherTenantAdmin->id)
            ->call('saveSchoolIdentity')
            ->assertHasErrors(['owner_user_id']);

        Livewire::test(SystemAssetsManager::class, ['systemSettingId' => $tenant->id])
            ->set('domain', 'cursos.owner-invalid.test')
            ->set('escola_nome', 'Owner Invalid')
            ->set('owner_user_id', (string) $student->id)
            ->call('saveSchoolIdentity')
            ->assertHasErrors(['owner_user_id']);
    }

    public function test_super_admin_can_send_test_email_for_explicit_tenant_context(): void
    {
        Mail::fake();

        $superAdmin = $this->bootstrapSuperAdmin();
        $tenantAdmin = User::factory()->admin()->create([
            'email' => 'tenant-mail-owner@example.com',
        ]);
        $tenantAdmin->refresh();
        $tenant = $tenantAdmin->systemSetting()->withoutGlobalScopes()->firstOrFail();
        $tenant->update([
            'domain' => 'cursos.explicit-mail.test',
            'escola_nome' => 'Tenant Mail',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(SystemAssetsManager::class, ['systemSettingId' => $tenant->id])
            ->set('mail_mailer', 'smtp')
            ->set('mail_scheme', 'tls')
            ->set('mail_host', 'smtp.explicit-tenant.test')
            ->set('mail_port', '587')
            ->set('mail_username', 'mailer@explicit-tenant.test')
            ->set('mail_password', 'segredo-explicito')
            ->set('mail_from_address', 'contato@explicit-tenant.test')
            ->set('mail_from_name', 'Tenant Mailer')
            ->set('test_email', 'teste@explicit-tenant.test')
            ->call('sendTestEmail')
            ->assertHasNoErrors();

        Mail::assertSent(SystemMailTestMessage::class, function (SystemMailTestMessage $mail) use ($tenant): bool {
            return $mail->hasTo('teste@explicit-tenant.test')
                && $mail->systemSetting->mail_host === 'smtp.explicit-tenant.test'
                && $mail->systemSetting->escola_nome === $tenant->escola_nome
                && $mail->systemSetting->domain === $tenant->domain;
        });
    }

    public function test_super_admin_can_upload_asset_for_selected_tenant(): void
    {
        Storage::fake('public');

        $superAdmin = $this->bootstrapSuperAdmin();
        $tenantAdmin = User::factory()->admin()->create([
            'email' => 'tenant-upload-owner@example.com',
        ]);
        $tenantAdmin->refresh();
        $tenant = $tenantAdmin->systemSetting()->withoutGlobalScopes()->firstOrFail();
        $tenant->update([
            'domain' => 'cursos.explicit-upload.test',
            'escola_nome' => 'Tenant Upload',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(SystemAssetsManager::class, ['systemSettingId' => $tenant->id])
            ->set('uploads.logo', UploadedFile::fake()->image('logo-tenant.png'))
            ->call('save', 'logo')
            ->assertHasNoErrors();

        $tenant->refresh();

        $this->assertNotNull($tenant->default_logo_path);
        Storage::disk('public')->assertExists($tenant->default_logo_path);
    }

    private function bootstrapSuperAdmin(): User
    {
        return User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', ['sampaio.free@gmail.com'])
            ->firstOrFail();
    }

    /**
     * @return array<string, array{0:string,1:string}>
     */
    public static function invalidDomainProvider(): array
    {
        return [
            'missing prefix' => ['dominio.com', 'O domínio deve começar com cursos.'],
            'wrong subdomain' => ['app.dominio.com', 'O domínio deve começar com cursos.'],
            'leading spaces' => [' cursos.dominio.com', 'O domínio não pode conter espaços.'],
            'internal spaces' => ['cursos.dominio .com', 'O domínio não pode conter espaços.'],
            'protocol' => ['https://cursos.dominio.com', 'Informe apenas o host, sem protocolo, porta ou caminho.'],
            'path' => ['cursos.dominio.com/path', 'Informe apenas o host, sem protocolo, porta ou caminho.'],
            'port' => ['cursos.dominio.com:8000', 'Informe apenas o host, sem protocolo, porta ou caminho.'],
            'invalid char underscore' => ['cursos.dominio_com', 'Use apenas letras, números, hífen e ponto no domínio.'],
            'invalid char at' => ['cursos@dominio.com', 'Use apenas letras, números, hífen e ponto no domínio.'],
        ];
    }
}
