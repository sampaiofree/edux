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
        $response->assertSee('data-auth-login-page="1"', false);
        $response->assertSee('data-auth-login-brand="1"', false);
        $response->assertSee('data-auth-login-shell="1"', false);
        $response->assertSee('data-auth-login-legal="1"', false);
        $response->assertSeeInOrder([
            'data-auth-login-brand="1"',
            'data-auth-login-shell="1"',
            'data-auth-login-legal="1"',
        ], false);
        $response->assertSee(route('legal.terms', absolute: false), false);
        $response->assertSee(route('legal.privacy', absolute: false), false);
        $response->assertSee(route('legal.support', absolute: false), false);
        $response->assertSee('Termos', false);
        $response->assertSee('Privacidade', false);
        $response->assertSee('Suporte', false);
        $response->assertDontSee('<header class="bg-edux-primary text-white shadow-lg">', false);
        $response->assertDontSee('<footer class="fixed inset-x-0 bottom-0 bg-edux-primary text-white">', false);
        $response->assertDontSee('aria-label="Abrir menu"', false);
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
        $response->assertSee('data-auth-login-shell="1"', false);
        $response->assertSee('name="remember" value="0"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('checked', false);
    }

    public function test_login_page_shows_force_app_gate_when_enabled_with_store_links(): void
    {
        $this->createAdminForTenant(
            ['email' => 'login-force-app@example.com'],
            [
                'domain' => 'cursos.force-app.example.test',
                'escola_nome' => 'Escola App',
                'force_app' => true,
                'play_store_link' => 'https://play.google.com/store/apps/details?id=com.edux.app',
                'apple_store_link' => 'https://apps.apple.com/br/app/edux/id123456789',
            ]
        );

        $response = $this->forceTestHost('cursos.force-app.example.test')
            ->get('http://cursos.force-app.example.test/login');

        $response->assertOk();
        $response->assertSee('data-auth-login-shell="1"', false);
        $response->assertSee('data-login-force-app-root="1"', false);
        $response->assertSee('Identificando seu acesso');
        $response->assertSee('Baixe nosso aplicativo');
        $response->assertSee('Para entrar na sua conta, use o aplicativo Portal JE. Baixe o app na loja do seu celular e faça login por lá.');
        $response->assertSee('Abrir na Play Store');
        $response->assertSee('Abrir na App Store');
        $response->assertSee('href="https://play.google.com/store/apps/details?id=com.edux.app"', false);
        $response->assertSee('href="https://apps.apple.com/br/app/edux/id123456789"', false);
    }

    public function test_login_page_renders_only_available_store_button_when_single_link_exists(): void
    {
        $this->createAdminForTenant(
            ['email' => 'login-force-app-single-store@example.com'],
            [
                'domain' => 'cursos.force-app-single.example.test',
                'escola_nome' => 'Escola App',
                'force_app' => true,
                'play_store_link' => 'https://play.google.com/store/apps/details?id=com.edux.app',
                'apple_store_link' => null,
            ]
        );

        $response = $this->forceTestHost('cursos.force-app-single.example.test')
            ->get('http://cursos.force-app-single.example.test/login');

        $response->assertOk();
        $response->assertSee('data-auth-login-shell="1"', false);
        $response->assertSee('data-login-force-app-root="1"', false);
        $response->assertSee('Abrir na Play Store');
        $response->assertDontSee('Abrir na App Store');
    }

    public function test_login_page_falls_back_to_regular_form_when_force_app_has_no_store_links(): void
    {
        $this->createAdminForTenant(
            ['email' => 'login-force-app-no-links@example.com'],
            [
                'domain' => 'cursos.force-app-no-links.example.test',
                'escola_nome' => 'Escola App',
                'force_app' => true,
                'play_store_link' => null,
                'apple_store_link' => null,
            ]
        );

        $response = $this->forceTestHost('cursos.force-app-no-links.example.test')
            ->get('http://cursos.force-app-no-links.example.test/login');

        $response->assertOk();
        $response->assertSee('data-auth-login-shell="1"', false);
        $response->assertDontSee('data-login-force-app-root="1"', false);
        $response->assertSee('Acesse sua conta');
        $response->assertSee('Recuperar senha');
    }

    public function test_login_page_adm_query_bypasses_force_app_gate(): void
    {
        $this->createAdminForTenant(
            ['email' => 'login-force-app-bypass@example.com'],
            [
                'domain' => 'cursos.force-app-bypass.example.test',
                'escola_nome' => 'Escola App',
                'force_app' => true,
                'play_store_link' => 'https://play.google.com/store/apps/details?id=com.edux.app',
                'apple_store_link' => 'https://apps.apple.com/br/app/edux/id123456789',
            ]
        );

        $response = $this->forceTestHost('cursos.force-app-bypass.example.test')
            ->get('http://cursos.force-app-bypass.example.test/login?adm=1');

        $response->assertOk();
        $response->assertSee('data-auth-login-shell="1"', false);
        $response->assertDontSee('data-login-force-app-root="1"', false);
        $response->assertSee('Acesse sua conta');
        $response->assertSee('Recuperar senha');
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
