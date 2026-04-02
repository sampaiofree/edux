<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\PasswordRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    private const SESSION_PENDING_EMAIL = 'password_recovery.pending_email';
    private const SESSION_VERIFIED_EMAIL = 'password_recovery.verified_email';
    private const SESSION_VERIFIED_AT = 'password_recovery.verified_at';

    public function create(Request $request): View
    {
        $this->clearRecoverySession($request);

        return view('auth.password-recovery-request');
    }

    public function store(Request $request, PasswordRecoveryService $service): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $service->normalizeEmail($validated['email']);
        $systemSetting = SystemSetting::current();

        try {
            $service->requestCode($email, (string) $request->ip(), $systemSetting);
        } catch (\Throwable $exception) {
            Log::warning('auth.password_recovery.request_failed', [
                'email' => $email,
                'system_setting_id' => $systemSetting->id,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Nao foi possivel enviar o codigo agora. Tente novamente em alguns minutos.',
            ])->onlyInput('email');
        }

        $this->clearRecoverySession($request);
        $request->session()->put(self::SESSION_PENDING_EMAIL, $email);

        return redirect()
            ->route('password.recovery.code')
            ->with('status', 'Se o e-mail existir, enviamos um codigo para continuar.');
    }

    public function showCodeForm(Request $request): View|RedirectResponse
    {
        $email = $this->pendingEmail($request);

        if (! $email) {
            return redirect()->route('password.recovery.request');
        }

        return view('auth.password-recovery-code', [
            'email' => $email,
        ]);
    }

    public function verifyCode(Request $request, PasswordRecoveryService $service): RedirectResponse
    {
        $email = $this->pendingEmail($request);

        if (! $email) {
            return redirect()->route('password.recovery.request');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $result = $service->verifyCode($email, (string) $request->ip(), $validated['code']);

        if (($result['status'] ?? null) !== 'verified') {
            return back()->withErrors([
                'code' => $result['message'] ?? 'Codigo invalido.',
            ]);
        }

        $request->session()->forget(self::SESSION_PENDING_EMAIL);
        $request->session()->put(self::SESSION_VERIFIED_EMAIL, $email);
        $request->session()->put(self::SESSION_VERIFIED_AT, now()->timestamp);

        return redirect()
            ->route('password.recovery.reset')
            ->with('status', 'Codigo confirmado. Agora crie sua nova senha.');
    }

    public function resendCode(Request $request, PasswordRecoveryService $service): RedirectResponse
    {
        $email = $this->pendingEmail($request);

        if (! $email) {
            return redirect()->route('password.recovery.request');
        }

        $systemSetting = SystemSetting::current();

        try {
            $result = $service->requestCode($email, (string) $request->ip(), $systemSetting);
        } catch (\Throwable $exception) {
            Log::warning('auth.password_recovery.resend_failed', [
                'email' => $email,
                'system_setting_id' => $systemSetting->id,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'code' => 'Nao foi possivel reenviar o codigo agora. Tente novamente em alguns minutos.',
            ]);
        }

        if (($result['status'] ?? null) === 'cooldown' || ($result['status'] ?? null) === 'locked') {
            return back()->withErrors([
                'code' => $result['message'] ?? 'Nao foi possivel reenviar o codigo agora.',
            ]);
        }

        return back()->with('status', 'Se o e-mail existir, enviamos um novo codigo.');
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        $email = $this->verifiedEmail($request);

        if (! $email) {
            return redirect()->route('password.recovery.request');
        }

        return view('auth.password-recovery-reset', [
            'email' => $email,
        ]);
    }

    public function updatePassword(Request $request, PasswordRecoveryService $service): RedirectResponse
    {
        $email = $this->verifiedEmail($request);

        if (! $email) {
            return redirect()->route('password.recovery.request');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $service->resetPassword($email, $validated['password'], SystemSetting::current());

        $this->clearRecoverySession($request);

        if (! $user) {
            return redirect()->route('password.recovery.request')->withErrors([
                'email' => 'Nao foi possivel atualizar a senha. Solicite um novo codigo.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $defaultRoute = $user->hasAdminPrivileges()
            ? route('admin.dashboard')
            : route('dashboard');

        return redirect()->to($defaultRoute)->with('status', 'Senha atualizada com sucesso.');
    }

    private function pendingEmail(Request $request): ?string
    {
        $email = $request->session()->get(self::SESSION_PENDING_EMAIL);

        return is_string($email) && trim($email) !== '' ? $email : null;
    }

    private function verifiedEmail(Request $request): ?string
    {
        $email = $request->session()->get(self::SESSION_VERIFIED_EMAIL);
        $verifiedAt = (int) $request->session()->get(self::SESSION_VERIFIED_AT, 0);

        if (! is_string($email) || trim($email) === '' || $verifiedAt <= 0) {
            return null;
        }

        if ((now()->timestamp - $verifiedAt) > PasswordRecoveryService::CODE_TTL_SECONDS) {
            $this->clearRecoverySession($request);

            return null;
        }

        return $email;
    }

    private function clearRecoverySession(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_PENDING_EMAIL,
            self::SESSION_VERIFIED_EMAIL,
            self::SESSION_VERIFIED_AT,
        ]);
    }
}
