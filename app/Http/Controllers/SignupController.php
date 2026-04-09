<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\PublicSignupService;
use App\Support\AuthPageDataBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SignupController extends Controller
{
    private const SESSION_PENDING_NAME = 'signup.pending_name';
    private const SESSION_PENDING_EMAIL = 'signup.pending_email';
    private const SESSION_VERIFIED_NAME = 'signup.verified_name';
    private const SESSION_VERIFIED_EMAIL = 'signup.verified_email';
    private const SESSION_VERIFIED_AT = 'signup.verified_at';

    public function create(Request $request, AuthPageDataBuilder $builder): View
    {
        $this->clearSignupSession($request);

        return view('auth.signup-request', $builder->build($request));
    }

    public function store(Request $request, PublicSignupService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ]);

        $name = $service->normalizeName($validated['name']);
        $email = $service->normalizeEmail($validated['email']);
        $systemSetting = SystemSetting::current();

        try {
            $result = $service->requestCode($name, $email, (string) $request->ip(), $systemSetting);
        } catch (\Throwable $exception) {
            Log::warning('auth.signup.request_failed', [
                'name' => $name,
                'email' => $email,
                'system_setting_id' => $systemSetting->id,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Não foi possível enviar o código agora. Tente novamente em alguns minutos.',
            ])->withInput();
        }

        if (($result['status'] ?? null) === 'existing') {
            return back()->withErrors([
                'email' => $result['message'] ?? 'Já existe uma conta com esse e-mail.',
            ])->withInput();
        }

        if (($result['status'] ?? null) === 'cooldown' || ($result['status'] ?? null) === 'locked') {
            return back()->withErrors([
                'email' => $result['message'] ?? 'Não foi possível enviar o código agora.',
            ])->withInput();
        }

        $this->clearSignupSession($request);
        $request->session()->put(self::SESSION_PENDING_NAME, $name);
        $request->session()->put(self::SESSION_PENDING_EMAIL, $email);

        return redirect()
            ->route('signup.code')
            ->with('status', 'Enviamos um código para ativar sua conta.');
    }

    public function showCodeForm(Request $request, AuthPageDataBuilder $builder): View|RedirectResponse
    {
        $name = $this->pendingName($request);
        $email = $this->pendingEmail($request);

        if (! $name || ! $email) {
            return redirect()->route('signup.create');
        }

        return view('auth.signup-code', array_merge($builder->build($request), [
            'name' => $name,
            'email' => $email,
        ]));
    }

    public function verifyCode(Request $request, PublicSignupService $service): RedirectResponse
    {
        $name = $this->pendingName($request);
        $email = $this->pendingEmail($request);

        if (! $name || ! $email) {
            return redirect()->route('signup.create');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $result = $service->verifyCode($email, (string) $request->ip(), SystemSetting::current(), $validated['code']);

        if (($result['status'] ?? null) !== 'verified') {
            return back()->withErrors([
                'code' => $result['message'] ?? 'Código inválido.',
            ]);
        }

        $request->session()->forget([
            self::SESSION_PENDING_NAME,
            self::SESSION_PENDING_EMAIL,
        ]);

        $request->session()->put(self::SESSION_VERIFIED_NAME, $name);
        $request->session()->put(self::SESSION_VERIFIED_EMAIL, $email);
        $request->session()->put(self::SESSION_VERIFIED_AT, now()->timestamp);

        return redirect()
            ->route('signup.password')
            ->with('status', 'Código confirmado. Agora crie sua senha.');
    }

    public function resendCode(Request $request, PublicSignupService $service): RedirectResponse
    {
        $name = $this->pendingName($request);
        $email = $this->pendingEmail($request);

        if (! $name || ! $email) {
            return redirect()->route('signup.create');
        }

        $systemSetting = SystemSetting::current();

        try {
            $result = $service->requestCode($name, $email, (string) $request->ip(), $systemSetting);
        } catch (\Throwable $exception) {
            Log::warning('auth.signup.resend_failed', [
                'name' => $name,
                'email' => $email,
                'system_setting_id' => $systemSetting->id,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'code' => 'Não foi possível reenviar o código agora. Tente novamente em alguns minutos.',
            ]);
        }

        if (($result['status'] ?? null) === 'existing') {
            return redirect()->route('login')->withErrors([
                'email' => $result['message'] ?? 'Já existe uma conta com esse e-mail.',
            ]);
        }

        if (($result['status'] ?? null) === 'cooldown' || ($result['status'] ?? null) === 'locked') {
            return back()->withErrors([
                'code' => $result['message'] ?? 'Não foi possível reenviar o código agora.',
            ]);
        }

        return back()->with('status', 'Enviamos um novo código para ativar sua conta.');
    }

    public function showPasswordForm(Request $request, AuthPageDataBuilder $builder): View|RedirectResponse
    {
        $name = $this->verifiedName($request);
        $email = $this->verifiedEmail($request);

        if (! $name || ! $email) {
            return redirect()->route('signup.create');
        }

        return view('auth.signup-password', array_merge($builder->build($request), [
            'name' => $name,
            'email' => $email,
        ]));
    }

    public function activate(Request $request, PublicSignupService $service): RedirectResponse
    {
        $name = $this->verifiedName($request);
        $email = $this->verifiedEmail($request);

        if (! $name || ! $email) {
            return redirect()->route('signup.create');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $service->activateAccount($name, $email, $validated['password'], SystemSetting::current());

        $this->clearSignupSession($request);

        if (! $user) {
            return redirect()->route('signup.create')->withErrors([
                'email' => 'Não foi possível ativar sua conta. Tente novamente.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Conta criada com sucesso.');
    }

    private function pendingName(Request $request): ?string
    {
        $name = $request->session()->get(self::SESSION_PENDING_NAME);

        return is_string($name) && trim($name) !== '' ? $name : null;
    }

    private function pendingEmail(Request $request): ?string
    {
        $email = $request->session()->get(self::SESSION_PENDING_EMAIL);

        return is_string($email) && trim($email) !== '' ? $email : null;
    }

    private function verifiedName(Request $request): ?string
    {
        $name = $request->session()->get(self::SESSION_VERIFIED_NAME);
        $verifiedAt = (int) $request->session()->get(self::SESSION_VERIFIED_AT, 0);

        if (! is_string($name) || trim($name) === '' || $verifiedAt <= 0) {
            return null;
        }

        if ((now()->timestamp - $verifiedAt) > PublicSignupService::CODE_TTL_SECONDS) {
            $this->clearSignupSession($request);

            return null;
        }

        return $name;
    }

    private function verifiedEmail(Request $request): ?string
    {
        $email = $request->session()->get(self::SESSION_VERIFIED_EMAIL);
        $verifiedAt = (int) $request->session()->get(self::SESSION_VERIFIED_AT, 0);

        if (! is_string($email) || trim($email) === '' || $verifiedAt <= 0) {
            return null;
        }

        if ((now()->timestamp - $verifiedAt) > PublicSignupService::CODE_TTL_SECONDS) {
            $this->clearSignupSession($request);

            return null;
        }

        return $email;
    }

    private function clearSignupSession(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_PENDING_NAME,
            self::SESSION_PENDING_EMAIL,
            self::SESSION_VERIFIED_NAME,
            self::SESSION_VERIFIED_EMAIL,
            self::SESSION_VERIFIED_AT,
        ]);
    }
}
