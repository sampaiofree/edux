<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    protected function authenticate($request, array $guards)
    {
        try {
            parent::authenticate($request, $guards);

            if ($this->shouldLogSuccessfulPass($request)) {
                $user = $request->user();

                Log::info('auth.middleware.passed', [
                    'host' => $request->getHost(),
                    'scheme' => $request->getScheme(),
                    'path' => $request->path(),
                    'route_name' => optional($request->route())->getName(),
                    'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                    'session_cookie_name' => (string) config('session.cookie'),
                    'has_session_cookie' => $request->cookies->has((string) config('session.cookie')),
                    'user_id' => $user?->id,
                    'user_role' => $user?->role?->value ?? $user?->role,
                    'is_super_admin' => $user?->isSuperAdmin(),
                    'user_system_setting_id' => $user?->system_setting_id,
                ]);
            }
        } catch (AuthenticationException $exception) {
            Log::warning('auth.middleware.unauthenticated', [
                'host' => $request->getHost(),
                'scheme' => $request->getScheme(),
                'is_secure_request' => $request->isSecure(),
                'full_url' => $request->fullUrl(),
                'path' => $request->path(),
                'route_name' => optional($request->route())->getName(),
                'guards' => $guards,
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'session_cookie_name' => (string) config('session.cookie'),
                'has_session_cookie' => $request->cookies->has((string) config('session.cookie')),
                'session_domain' => config('session.domain'),
                'session_secure_cookie' => config('session.secure'),
                'session_same_site' => config('session.same_site'),
                'app_url' => config('app.url'),
                'x_forwarded_proto' => $request->headers->get('x-forwarded-proto'),
                'x_forwarded_host' => $request->headers->get('x-forwarded-host'),
                'referer' => $request->headers->get('referer'),
            ]);

            throw $exception;
        }
    }

    private function shouldLogSuccessfulPass($request): bool
    {
        $routeName = (string) optional($request->route())->getName();

        return $routeName === 'dashboard' || str_starts_with($routeName, 'admin.');
    }
}
