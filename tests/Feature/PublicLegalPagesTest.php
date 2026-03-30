<?php

namespace Tests\Feature;

use App\Models\SupportWhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_privacy_page_uses_current_tenant_data_from_host(): void
    {
        $tenantAAdmin = $this->createAdminForTenant(
            ['email' => 'alpha-admin@example.com'],
            [
                'domain' => 'cursos.alpha.example.test',
                'escola_nome' => 'Escola Alpha',
                'escola_cnpj' => '12.345.678/0001-90',
                'mail_from_address' => 'privacidade@cursos.alpha.example.test',
                'mail_from_name' => 'Equipe Alpha',
            ]
        );

        $tenantBAdmin = $this->createAdminForTenant(
            ['email' => 'beta-admin@example.com'],
            [
                'domain' => 'cursos.beta.example.test',
                'escola_nome' => 'Escola Beta',
                'escola_cnpj' => '98.765.432/0001-10',
                'mail_from_address' => 'privacidade@cursos.beta.example.test',
                'mail_from_name' => 'Equipe Beta',
            ]
        );

        $this->forceTestHost('cursos.alpha.example.test')
            ->get('http://cursos.alpha.example.test/privacidade')
            ->assertOk()
            ->assertSee('Escola Alpha', false)
            ->assertSee('cursos.alpha.example.test', false)
            ->assertSee('12.345.678/0001-90', false)
            ->assertSee('privacidade@cursos.alpha.example.test', false)
            ->assertDontSee('Escola Beta', false)
            ->assertDontSee('privacidade@cursos.beta.example.test', false);
    }

    public function test_support_page_renders_only_active_whatsapp_numbers_for_current_tenant(): void
    {
        $tenantAAdmin = $this->createAdminForTenant(
            ['email' => 'support-alpha@example.com'],
            [
                'domain' => 'cursos.support-alpha.example.test',
                'escola_nome' => 'Suporte Alpha',
                'mail_from_address' => 'contato@cursos.support-alpha.example.test',
            ]
        );
        $tenantBAdmin = $this->createAdminForTenant(
            ['email' => 'support-beta@example.com'],
            [
                'domain' => 'cursos.support-beta.example.test',
                'escola_nome' => 'Suporte Beta',
                'mail_from_address' => 'contato@cursos.support-beta.example.test',
            ]
        );

        SupportWhatsappNumber::create([
            'system_setting_id' => $tenantAAdmin->system_setting_id,
            'label' => 'Atendimento Alpha',
            'whatsapp' => '+55 (62) 99999-0001',
            'description' => 'Canal principal Alpha',
            'is_active' => true,
            'position' => 2,
        ]);

        SupportWhatsappNumber::create([
            'system_setting_id' => $tenantAAdmin->system_setting_id,
            'label' => 'Financeiro Alpha',
            'whatsapp' => '+55 (62) 99999-0002',
            'description' => 'Canal financeiro',
            'is_active' => false,
            'position' => 1,
        ]);

        SupportWhatsappNumber::create([
            'system_setting_id' => $tenantBAdmin->system_setting_id,
            'label' => 'Atendimento Beta',
            'whatsapp' => '+55 (11) 99999-0003',
            'description' => 'Canal Beta',
            'is_active' => true,
            'position' => 1,
        ]);

        $this->forceTestHost('cursos.support-alpha.example.test')
            ->get('http://cursos.support-alpha.example.test/suporte')
            ->assertOk()
            ->assertSee('Suporte Alpha', false)
            ->assertSee('Atendimento Alpha', false)
            ->assertSee('+55 (62) 99999-0001', false)
            ->assertSee('contato@cursos.support-alpha.example.test', false)
            ->assertDontSee('Financeiro Alpha', false)
            ->assertDontSee('Atendimento Beta', false)
            ->assertDontSee('contato@cursos.support-beta.example.test', false);
    }

    public function test_support_page_falls_back_when_whatsapp_and_email_are_missing(): void
    {
        $admin = $this->createAdminForTenant(
            ['email' => 'fallback-admin@example.com'],
            [
                'domain' => 'cursos.fallback.example.test',
                'escola_nome' => 'Escola Fallback',
                'mail_from_address' => null,
                'mail_from_name' => null,
            ]
        );

        $this->forceTestHost('cursos.fallback.example.test')
            ->get('http://cursos.fallback.example.test/suporte')
            ->assertOk()
            ->assertSee('Escola Fallback', false)
            ->assertSee('ainda não publicou números de WhatsApp', false)
            ->assertSee('ainda não configurou um e-mail público de atendimento', false);
    }

    public function test_public_legal_pages_return_404_when_host_does_not_resolve_a_tenant(): void
    {
        $this->forceTestHost('desconhecido.example.test')
            ->get('http://desconhecido.example.test/privacidade')
            ->assertNotFound();

        $this->forceTestHost('desconhecido.example.test')
            ->get('http://desconhecido.example.test/suporte')
            ->assertNotFound();
    }
}
