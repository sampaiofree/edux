<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_login_page_footer_links_to_public_legal_pages(): void
    {
        $this->createAdminForTenant(
            ['email' => 'login-page-admin@example.com'],
            [
                'domain' => 'cursos.login-page.example.test',
                'escola_nome' => 'Escola Login',
            ]
        );

        $response = $this->forceTestHost('cursos.login-page.example.test')
            ->get('http://cursos.login-page.example.test/login');

        $response->assertOk();
        $response->assertSee(route('legal.terms', absolute: false), false);
        $response->assertSee(route('legal.privacy', absolute: false), false);
        $response->assertSee(route('legal.support', absolute: false), false);
        $response->assertSee('Termos', false);
        $response->assertSee('Privacidade', false);
        $response->assertSee('Suporte', false);
    }
}
