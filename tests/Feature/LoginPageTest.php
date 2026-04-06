<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
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

    public function test_login_page_enables_remember_session_by_default(): void
    {
        $this->createAdminForTenant(
            ['email' => 'login-page-default-remember@example.com'],
            [
                'domain' => 'cursos.login-remember.example.test',
                'escola_nome' => 'Escola Login',
            ]
        );

        $response = $this->forceTestHost('cursos.login-remember.example.test')
            ->get('http://cursos.login-remember.example.test/login');

        $response->assertOk();
        $response->assertSee('name="remember" value="0"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('checked', false);
    }

    public function test_login_without_explicit_remember_field_still_queues_recaller_cookie(): void
    {
        $user = $this->createAdminForTenant([
            'email' => 'remember-default@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->forceTestHost('cursos.example.test')
            ->from('http://cursos.example.test/login')
            ->post('http://cursos.example.test/login', [
                'email' => $user->email,
                'password' => 'secret123',
            ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($user);
    }
}
