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
        $roles = array_values(array_filter(array_map('trim', $roles)));

        if (! $user) {
            abort(403);
        }

        $currentSystemSettingId = SystemSetting::currentId();

        if ($currentSystemSettingId !== null && ! $user->canAccessSystemSetting($currentSystemSettingId)) {
            abort(403);
        }

        if (! $this->userHasAnyAllowedRole($user, $roles)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $roles
     */
    private function userHasAnyAllowedRole($user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->userCanAccessRole($user, $role)) {
                return true;
            }
        }

        return false;
    }

    private function userCanAccessRole($user, string $role): bool
    {
        return match ($role) {
            'admin' => $user->hasAdminPrivileges(),
            'teacher' => $user->hasAdminPrivileges() || $user->isTeacher(),
            'student' => $user->isStudent(),
            default => ($user->role->value ?? $user->role) === $role,
        };
    }
}
