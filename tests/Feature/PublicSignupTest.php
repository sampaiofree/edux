<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\SignupActivationCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicSignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_login_page_shows_create_account_link(): void
    {
        $this->defaultTenantAdmin();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Criar conta');
        $response->assertSee(route('signup.create', absolute: false), false);
    }

    public function test_login_force_app_gate_shows_create_account_link(): void
    {
        $this->createAdminForTenant(
            ['email' => 'login-signup-link@example.com'],
            [
                'domain' => 'cursos.signup-login-link.example.test',
                'escola_nome' => 'Escola Signup',
                'force_app' => true,
                'play_store_link' => 'https://play.google.com/store/apps/details?id=com.edux.app',
            ]
        );

        $response = $this->forceTestHost('cursos.signup-login-link.example.test')
            ->get('http://cursos.signup-login-link.example.test/login');

        $response->assertOk();
        $response->assertSee('data-login-force-app-root="1"', false);
        $response->assertSee('Ainda não tem conta? Criar conta');
        $response->assertSee(route('signup.create', absolute: false), false);
    }

    public function test_signup_page_renders_with_auth_shell_and_branding(): void
    {
        $this->createAdminForTenant(
            ['email' => 'signup-page-admin@example.com'],
            [
                'domain' => 'cursos.signup-page.example.test',
                'escola_nome' => 'Escola Signup',
            ]
        );

        $response = $this->forceTestHost('cursos.signup-page.example.test')
            ->get('http://cursos.signup-page.example.test/criar-conta');

        $response->assertOk();
        $response->assertSee('data-auth-signup-page="1"', false);
        $response->assertSee('Cadastro público da plataforma');
        $response->assertSee('Crie sua conta');
        $response->assertSee('Escola Signup');
    }

    public function test_signup_page_shows_force_app_gate_when_enabled_with_store_links(): void
    {
        $this->createAdminForTenant(
            ['email' => 'signup-force-app@example.com'],
            [
                'domain' => 'cursos.signup-force-app.example.test',
                'escola_nome' => 'Escola Signup',
                'force_app' => true,
                'play_store_link' => 'https://play.google.com/store/apps/details?id=com.edux.app',
                'apple_store_link' => 'https://apps.apple.com/br/app/edux/id123456789',
            ]
        );

        $response = $this->forceTestHost('cursos.signup-force-app.example.test')
            ->get('http://cursos.signup-force-app.example.test/criar-conta');

        $response->assertOk();
        $response->assertSee('data-login-force-app-root="1"', false);
        $response->assertSee('Baixe nosso aplicativo');
        $response->assertSee('Para criar sua conta, use o aplicativo Portal JE. Baixe o app na loja do seu celular e continue por lá.');
        $response->assertSee(route('login', absolute: false), false);
    }

    public function test_signup_request_sends_code_for_new_email_and_does_not_create_user_yet(): void
    {
        Mail::fake();

        $this->defaultTenantAdmin();

        $response = $this->post(route('signup.store'), [
            'name' => 'Novo Aluno',
            'email' => 'novo-aluno@example.com',
        ]);

        $response->assertRedirect(route('signup.code'));
        $response->assertSessionHas('status', 'Enviamos um código para ativar sua conta.');
        $response->assertSessionHas('signup.pending_name', 'Novo Aluno');
        $response->assertSessionHas('signup.pending_email', 'novo-aluno@example.com');

        Mail::assertSent(SignupActivationCodeMail::class, function (SignupActivationCodeMail $mail): bool {
            return $mail->hasTo('novo-aluno@example.com');
        });

        $this->assertFalse(User::query()->where('email', 'novo-aluno@example.com')->exists());
    }

    public function test_signup_request_returns_explicit_error_for_existing_email_in_same_tenant(): void
    {
        Mail::fake();

        $student = $this->defaultTenantStudent([
            'email' => 'existente@example.com',
        ]);

        $response = $this->from(route('signup.create'))->post(route('signup.store'), [
            'name' => 'Aluno Existente',
            'email' => $student->email,
        ]);

        $response->assertRedirect(route('signup.create'));
        $response->assertSessionHasErrors('email');
        $response->assertSessionHasInput('email', $student->email);

        Mail::assertNothingSent();
    }

    public function test_signup_request_accepts_email_that_exists_in_another_tenant(): void
    {
        Mail::fake();

        $this->defaultTenantAdmin();

        $otherAdmin = $this->createAdminForTenant(
            ['email' => 'other-tenant-admin@example.com'],
            [
                'domain' => 'cursos.outro-tenant.example.test',
                'escola_nome' => 'Escola B',
            ]
        );

        $this->createStudentForTenant($otherAdmin, [
            'email' => 'duplicado@example.com',
        ]);

        $response = $this->forceTestHost($this->defaultTenantDomain())
            ->post(route('signup.store'), [
                'name' => 'Novo Cadastro',
                'email' => 'duplicado@example.com',
            ]);

        $response->assertRedirect(route('signup.code'));
        $response->assertSessionHas('signup.pending_email', 'duplicado@example.com');

        Mail::assertSent(SignupActivationCodeMail::class, function (SignupActivationCodeMail $mail): bool {
            return $mail->hasTo('duplicado@example.com');
        });
    }

    public function test_signup_code_must_be_valid_and_can_expire(): void
    {
        Mail::fake();

        $this->defaultTenantAdmin();

        $this->post(route('signup.store'), [
            'name' => 'Aluno Codigo',
            'email' => 'aluno-codigo-signup@example.com',
        ]);

        $sentCode = null;

        Mail::assertSent(SignupActivationCodeMail::class, function (SignupActivationCodeMail $mail) use (&$sentCode): bool {
            $sentCode = $mail->code;

            return true;
        });

        $invalidResponse = $this->withSession([
            'signup.pending_name' => 'Aluno Codigo',
            'signup.pending_email' => 'aluno-codigo-signup@example.com',
        ])->from(route('signup.code'))->post(route('signup.code.verify'), [
            'code' => '999999',
        ]);

        $invalidResponse->assertRedirect(route('signup.code'));
        $invalidResponse->assertSessionHasErrors('code');

        Carbon::setTestNow(now()->addMinutes(11));

        $expiredResponse = $this->withSession([
            'signup.pending_name' => 'Aluno Codigo',
            'signup.pending_email' => 'aluno-codigo-signup@example.com',
        ])->from(route('signup.code'))->post(route('signup.code.verify'), [
            'code' => $sentCode,
        ]);

        Carbon::setTestNow();

        $expiredResponse->assertRedirect(route('signup.code'));
        $expiredResponse->assertSessionHasErrors('code');
    }

    public function test_signup_resend_respects_cooldown(): void
    {
        Mail::fake();

        $this->defaultTenantAdmin();

        $this->post(route('signup.store'), [
            'name' => 'Aluno Reenvio',
            'email' => 'aluno-reenvio-signup@example.com',
        ]);

        $response = $this->withSession([
            'signup.pending_name' => 'Aluno Reenvio',
            'signup.pending_email' => 'aluno-reenvio-signup@example.com',
        ])->from(route('signup.code'))->post(route('signup.resend'));

        $response->assertRedirect(route('signup.code'));
        $response->assertSessionHasErrors('code');
    }

    public function test_signup_activation_flow_creates_student_marks_email_verified_and_logs_in(): void
    {
        Mail::fake();

        $this->defaultTenantAdmin();

        $this->post(route('signup.store'), [
            'name' => 'Aluno Novo',
            'email' => 'aluno-novo@example.com',
        ]);

        $sentCode = null;

        Mail::assertSent(SignupActivationCodeMail::class, function (SignupActivationCodeMail $mail) use (&$sentCode): bool {
            $sentCode = $mail->code;

            return true;
        });

        $verifyResponse = $this->withSession([
            'signup.pending_name' => 'Aluno Novo',
            'signup.pending_email' => 'aluno-novo@example.com',
        ])->post(route('signup.code.verify'), [
            'code' => $sentCode,
        ]);

        $verifyResponse->assertRedirect(route('signup.password'));

        $activationResponse = $this->withSession([
            'signup.verified_name' => 'Aluno Novo',
            'signup.verified_email' => 'aluno-novo@example.com',
            'signup.verified_at' => now()->timestamp,
        ])->post(route('signup.activate'), [
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ]);

        $user = User::query()->where('email', 'aluno-novo@example.com')->first();

        $this->assertNotNull($user);
        $activationResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::STUDENT, $user->role);
        $this->assertSame('Aluno Novo', $user->name);
        $this->assertSame('Aluno Novo', $user->display_name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('nova-senha-123', $user->password));
    }
}
