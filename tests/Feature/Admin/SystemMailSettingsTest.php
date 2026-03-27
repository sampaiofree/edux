<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SystemAssetsManager;
use App\Mail\SystemMailTestMessage;
use App\Models\User;
use App\Support\Mail\TenantMailManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        $this->assertSame('tls', $config['scheme']);
        $this->assertSame('smtp.mailer.test', $config['host']);
        $this->assertSame(2525, $config['port']);
        $this->assertSame('mailer-user', $config['username']);
        $this->assertSame('mailer-secret', $config['password']);
        $this->assertSame('nao-responda@mailer.test', $config['from']['address']);
        $this->assertSame('Mailer Tenant', $config['from']['name']);
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
