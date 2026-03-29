<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin() && in_array('admin', $roles, true)) {
            return $next($request);
        }

        $currentSystemSettingId = SystemSetting::currentId();

        if ($currentSystemSettingId !== null && ! $user->canAccessSystemSetting($currentSystemSettingId)) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! in_array($user->role->value ?? $user->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
