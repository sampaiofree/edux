<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use App\Http\Middleware\PrepareStudentOneSignalPrompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $remember = $request->boolean('remember');
        $sessionIdBeforeLogin = $request->session()->getId();

        try {
            $systemSetting = SystemSetting::current();
        } catch (\Throwable $exception) {
            Log::warning('auth.login.system_setting_resolution_failed', array_merge(
                $this->requestLogContext($request, $credentials),
                [
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                ]
            ));

            throw $exception;
        }

        Log::info('auth.login.attempt', $this->requestLogContext($request, $credentials, $systemSetting));

        $tenantCredentials = [
            ...$credentials,
            'system_setting_id' => $systemSetting->id,
        ];

        $tenantAuthenticated = Auth::attempt($tenantCredentials, $remember);

        if (! $tenantAuthenticated) {
            Log::warning('auth.login.tenant_failed', array_merge(
                $this->requestLogContext($request, $credentials, $systemSetting),
                $this->tenantLoginDiagnostics($credentials, $systemSetting->id)
            ));
        }

        $superAdminAuthenticated = $tenantAuthenticated
            ? false
            : $this->attemptSuperAdminLogin($request, $credentials, $remember, $systemSetting);

        if (! $tenantAuthenticated && ! $superAdminAuthenticated) {
            Log::warning('auth.login.failed', array_merge(
                $this->requestLogContext($request, $credentials, $systemSetting),
                [
                    'auth_check_after_attempts' => Auth::check(),
                    'session_id_after_attempts' => $request->session()->getId(),
                ]
            ));

            return back()->withErrors([
                'email' => 'As credenciais informadas são inválidas.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();
        $authenticatedVia = $tenantAuthenticated ? 'tenant' : 'super_admin';
        $defaultRoute = $user && $user->hasAdminPrivileges()
            ? route('admin.dashboard')
            : route('dashboard');

        Log::info('auth.login.success', array_merge(
            $this->requestLogContext($request, $credentials, $systemSetting),
            [
                'authenticated_via' => $authenticatedVia,
                'user_id' => $user?->id,
                'user_role' => $user?->role?->value ?? $user?->role,
                'is_super_admin' => $user?->isSuperAdmin(),
                'admin_context_system_setting_id' => $user?->adminContextSystemSettingId(),
                'session_id_before_login' => $sessionIdBeforeLogin,
                'session_id_after_regenerate' => $request->session()->getId(),
                'auth_check_after_regenerate' => Auth::check(),
                'intended_url' => $request->session()->get('url.intended'),
                'default_route' => $defaultRoute,
            ]
        ));

        return redirect()->intended($defaultRoute);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Log::info('auth.logout', [
            'host' => $request->getHost(),
            'path' => $request->path(),
            'session_id' => $request->session()->getId(),
            'user_id' => $request->user()?->id,
        ]);

        $request->session()->forget(PrepareStudentOneSignalPrompt::SESSION_KEY);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function attemptSuperAdminLogin(Request $request, array $credentials, bool $remember, SystemSetting $systemSetting): bool
    {
        $email = mb_strtolower(trim((string) ($credentials['email'] ?? '')), 'UTF-8');
        $password = (string) ($credentials['password'] ?? '');

        if ($email === '' || $password === '') {
            Log::warning('auth.login.super_admin_skipped_empty_credentials', $this->requestLogContext($request, $credentials, $systemSetting));

            return false;
        }

        $matchingUsers = User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->get();
        $matchingSuperAdminUsers = $matchingUsers->filter(fn (User $user): bool => $user->isSuperAdmin());
        $candidate = $matchingSuperAdminUsers
            ->first(fn (User $user): bool => Hash::check($password, $user->getAuthPassword()));

        if (! $candidate) {
            Log::warning('auth.login.super_admin_failed', array_merge(
                $this->requestLogContext($request, $credentials, $systemSetting),
                [
                    'matching_user_count' => $matchingUsers->count(),
                    'matching_super_admin_user_count' => $matchingSuperAdminUsers->count(),
                    'matching_users' => $matchingUsers
                        ->map(fn (User $user): array => [
                            'id' => $user->id,
                            'system_setting_id' => $user->system_setting_id,
                            'role' => $user->role?->value ?? $user->role,
                            'is_super_admin' => $user->isSuperAdmin(),
                        ])
                        ->values()
                        ->all(),
                    'configured_super_admin_email_match' => in_array($email, User::configuredSuperAdminEmails(), true),
                    'matching_super_admin_password_user_ids' => $matchingSuperAdminUsers
                        ->filter(fn (User $user): bool => Hash::check($password, $user->getAuthPassword()))
                        ->pluck('id')
                        ->values()
                        ->all(),
                ]
            ));

            return false;
        }

        Auth::login($candidate, $remember);

        Log::info('auth.login.super_admin_success', array_merge(
            $this->requestLogContext($request, $credentials, $systemSetting),
            [
                'user_id' => $candidate->id,
                'user_system_setting_id' => $candidate->system_setting_id,
                'user_role' => $candidate->role?->value ?? $candidate->role,
                'auth_check_after_login' => Auth::check(),
            ]
        ));

        return true;
    }

    private function requestLogContext(Request $request, array $credentials, ?SystemSetting $systemSetting = null): array
    {
        $cookieName = (string) config('session.cookie');
        $email = mb_strtolower(trim((string) ($credentials['email'] ?? '')), 'UTF-8');

        return [
            'host' => $request->getHost(),
            'scheme' => $request->getScheme(),
            'is_secure_request' => $request->isSecure(),
            'full_url' => $request->fullUrl(),
            'path' => $request->path(),
            'route_name' => optional($request->route())->getName(),
            'email' => $email !== '' ? $email : null,
            'remember' => $request->boolean('remember'),
            'session_id' => $request->session()->getId(),
            'session_cookie_name' => $cookieName,
            'has_session_cookie' => $request->cookies->has($cookieName),
            'session_driver' => config('session.driver'),
            'session_domain' => config('session.domain'),
            'session_secure_cookie' => config('session.secure'),
            'session_same_site' => config('session.same_site'),
            'app_url' => config('app.url'),
            'x_forwarded_proto' => $request->headers->get('x-forwarded-proto'),
            'x_forwarded_host' => $request->headers->get('x-forwarded-host'),
            'current_system_setting_id' => $systemSetting?->id,
            'current_system_setting_domain' => $systemSetting?->domain,
        ];
    }

    private function tenantLoginDiagnostics(array $credentials, int $systemSettingId): array
    {
        $email = mb_strtolower(trim((string) ($credentials['email'] ?? '')), 'UTF-8');
        $password = (string) ($credentials['password'] ?? '');
        $matchingUsers = User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->get();
        $matchingUsersInTenant = $matchingUsers
            ->filter(fn (User $user): bool => (int) $user->system_setting_id === $systemSettingId)
            ->values();

        return [
            'matching_user_count_total' => $matchingUsers->count(),
            'matching_user_count_in_current_tenant' => $matchingUsersInTenant->count(),
            'matching_users' => $matchingUsers
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'system_setting_id' => $user->system_setting_id,
                    'role' => $user->role?->value ?? $user->role,
                    'is_super_admin' => $user->isSuperAdmin(),
                ])
                ->values()
                ->all(),
            'matching_password_user_ids_in_current_tenant' => $matchingUsersInTenant
                ->filter(fn (User $user): bool => Hash::check($password, $user->getAuthPassword()))
                ->pluck('id')
                ->values()
                ->all(),
            'configured_super_admin_email_match' => in_array($email, User::configuredSuperAdminEmails(), true),
        ];
    }
}
