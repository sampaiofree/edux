<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $systemSetting = SystemSetting::current();
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $remember = $request->boolean('remember');

        $tenantCredentials = [
            ...$credentials,
            'system_setting_id' => $systemSetting->id,
        ];

        if (! Auth::attempt($tenantCredentials, $remember) && ! $this->attemptSuperAdminLogin($credentials, $remember)) {
            return back()->withErrors([
                'email' => 'As credenciais informadas são inválidas.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();
        $defaultRoute = $user && $user->hasAdminPrivileges()
            ? route('admin.dashboard')
            : route('dashboard');

        return redirect()->intended($defaultRoute);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function attemptSuperAdminLogin(array $credentials, bool $remember): bool
    {
        $email = mb_strtolower(trim((string) ($credentials['email'] ?? '')), 'UTF-8');
        $password = (string) ($credentials['password'] ?? '');

        if ($email === '' || $password === '') {
            return false;
        }

        $candidate = User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->get()
            ->first(fn (User $user): bool => $user->isSuperAdmin() && Hash::check($password, $user->getAuthPassword()));

        if (! $candidate) {
            return false;
        }

        Auth::login($candidate, $remember);

        return true;
    }
}
