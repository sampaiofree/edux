<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrepareStudentOneSignalPrompt
{
    public const AUTO_SHOW_ATTRIBUTE = 'edux.onesignal.auto_show_modal';

    public const SESSION_KEY = 'onesignal_prompt_seen';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user?->isStudent()
            || ! $request->isMethod('GET')
            || $request->expectsJson()
            || $request->ajax()
        ) {
            return $next($request);
        }

        $settings = SystemSetting::current();

        if (! filled($settings?->onesignal_app_id)) {
            return $next($request);
        }

        if (! $request->session()->has(self::SESSION_KEY)) {
            $request->attributes->set(self::AUTO_SHOW_ATTRIBUTE, true);
            $request->session()->put(self::SESSION_KEY, true);
        }

        return $next($request);
    }
}
