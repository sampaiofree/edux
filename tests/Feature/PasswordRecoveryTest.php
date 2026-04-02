<?php

namespace Tests\Feature;

use App\Mail\PasswordRecoveryCodeMail;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_password_recovery_link(): void
    {
        $this->defaultTenantAdmin();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Recuperar senha');
        $response->assertSee(route('password.recovery.request'), false);
    }

    public function test_password_recovery_request_page_renders(): void
    {
        $this->defaultTenantAdmin();

        $response = $this->get(route('password.recovery.request'));

        $response->assertOk();
        $response->assertSee('Receber código por e-mail');
    }

    public function test_password_recovery_request_sends_code_for_existing_tenant_user(): void
    {
        Mail::fake();

        $student = $this->defaultTenantStudent([
            'email' => 'aluno-recuperacao@example.com',
        ]);

        $response = $this->post(route('password.recovery.store'), [
            'email' => $student->email,
        ]);

        $response->assertRedirect(route('password.recovery.code'));
        $response->assertSessionHas('status', 'Se o e-mail existir, enviamos um codigo para continuar.');
        $response->assertSessionHas('password_recovery.pending_email', 'aluno-recuperacao@example.com');

        $sentCode = null;

        Mail::assertSent(PasswordRecoveryCodeMail::class, function (PasswordRecoveryCodeMail $mail) use ($student, &$sentCode): bool {
            $sentCode = $mail->code;

            return $mail->hasTo($student->email);
        });

        $this->assertNotNull($sentCode);

        $token = DB::table('password_reset_tokens')->where('email', $student->email)->value('token');

        $this->assertIsString($token);
        $this->assertTrue(Hash::check($sentCode, $token));
    }

    public function test_password_recovery_request_is_generic_for_unknown_email(): void
    {
        Mail::fake();
        $this->defaultTenantAdmin();

        $response = $this->post(route('password.recovery.store'), [
            'email' => 'nao-existe@example.com',
        ]);

        $response->assertRedirect(route('password.recovery.code'));
        $response->assertSessionHas('status', 'Se o e-mail existir, enviamos um codigo para continuar.');
        $response->assertSessionHas('password_recovery.pending_email', 'nao-existe@example.com');

        Mail::assertNothingSent();
        $this->assertNull(DB::table('password_reset_tokens')->where('email', 'nao-existe@example.com')->value('token'));
    }

    public function test_super_admin_account_is_ignored_by_password_recovery(): void
    {
        Mail::fake();

        $this->defaultTenantAdmin();

        $response = $this->post(route('password.recovery.store'), [
            'email' => 'sampaio.free@gmail.com',
        ]);

        $response->assertRedirect(route('password.recovery.code'));
        $response->assertSessionHas('status', 'Se o e-mail existir, enviamos um codigo para continuar.');

        Mail::assertNothingSent();
        $this->assertNull(DB::table('password_reset_tokens')->where('email', 'sampaio.free@gmail.com')->value('token'));
    }

    public function test_password_recovery_code_must_be_valid_and_can_expire(): void
    {
        Mail::fake();

        $student = $this->defaultTenantStudent([
            'email' => 'aluno-codigo@example.com',
        ]);

        $this->post(route('password.recovery.store'), [
            'email' => $student->email,
        ]);

        $sentCode = null;

        Mail::assertSent(PasswordRecoveryCodeMail::class, function (PasswordRecoveryCodeMail $mail) use (&$sentCode): bool {
            $sentCode = $mail->code;

            return true;
        });

        $invalidResponse = $this->withSession([
            'password_recovery.pending_email' => $student->email,
        ])->from(route('password.recovery.code'))->post(route('password.recovery.code.verify'), [
            'code' => '999999',
        ]);

        $invalidResponse->assertRedirect(route('password.recovery.code'));
        $invalidResponse->assertSessionHasErrors('code');

        Carbon::setTestNow(now()->addMinutes(11));

        $expiredResponse = $this->withSession([
            'password_recovery.pending_email' => $student->email,
        ])->from(route('password.recovery.code'))->post(route('password.recovery.code.verify'), [
            'code' => $sentCode,
        ]);

        Carbon::setTestNow();

        $expiredResponse->assertRedirect(route('password.recovery.code'));
        $expiredResponse->assertSessionHasErrors('code');
    }

    public function test_student_can_complete_full_password_recovery_flow_and_is_logged_in(): void
    {
        Mail::fake();

        $student = $this->defaultTenantStudent([
            'email' => 'aluno-reset-completo@example.com',
            'password' => Hash::make('senha-antiga-123'),
        ]);

        $this->post(route('password.recovery.store'), [
            'email' => $student->email,
        ]);

        $sentCode = null;

        Mail::assertSent(PasswordRecoveryCodeMail::class, function (PasswordRecoveryCodeMail $mail) use (&$sentCode, $student): bool {
            $sentCode = $mail->code;

            return $mail->hasTo($student->email);
        });

        $verifyResponse = $this->withSession([
            'password_recovery.pending_email' => $student->email,
        ])->post(route('password.recovery.code.verify'), [
            'code' => $sentCode,
        ]);

        $verifyResponse->assertRedirect(route('password.recovery.reset'));

        $resetResponse = $this->withSession([
            'password_recovery.verified_email' => $student->email,
            'password_recovery.verified_at' => now()->timestamp,
        ])->post(route('password.recovery.update'), [
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ]);

        $resetResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student->fresh());
        $this->assertTrue(Hash::check('nova-senha-123', $student->fresh()->password));
        $this->assertNull(DB::table('password_reset_tokens')->where('email', $student->email)->value('token'));
    }

    public function test_admin_is_redirected_to_admin_dashboard_after_password_reset(): void
    {
        Mail::fake();

        $admin = $this->createAdminForTenant([
            'email' => 'admin-reset@example.com',
            'password' => Hash::make('admin-antiga-123'),
        ]);

        $this->post(route('password.recovery.store'), [
            'email' => $admin->email,
        ]);

        $sentCode = null;

        Mail::assertSent(PasswordRecoveryCodeMail::class, function (PasswordRecoveryCodeMail $mail) use (&$sentCode, $admin): bool {
            $sentCode = $mail->code;

            return $mail->hasTo($admin->email);
        });

        $this->withSession([
            'password_recovery.pending_email' => $admin->email,
        ])->post(route('password.recovery.code.verify'), [
            'code' => $sentCode,
        ]);

        $resetResponse = $this->withSession([
            'password_recovery.verified_email' => $admin->email,
            'password_recovery.verified_at' => now()->timestamp,
        ])->post(route('password.recovery.update'), [
            'password' => 'admin-nova-123',
            'password_confirmation' => 'admin-nova-123',
        ]);

        $resetResponse->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin->fresh());
        $this->assertTrue(Hash::check('admin-nova-123', $admin->fresh()->password));
    }

    public function test_resend_respects_cooldown(): void
    {
        Mail::fake();

        $student = $this->defaultTenantStudent([
            'email' => 'aluno-reenvio@example.com',
        ]);

        $this->post(route('password.recovery.store'), [
            'email' => $student->email,
        ]);

        $response = $this->withSession([
            'password_recovery.pending_email' => $student->email,
        ])->from(route('password.recovery.code'))->post(route('password.recovery.resend'));

        $response->assertRedirect(route('password.recovery.code'));
        $response->assertSessionHasErrors('code');
    }
}
