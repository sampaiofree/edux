<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrepareStudentOneSignalPrompt
{
    public const AUTO_SHOW_ATTRIBUTE = 'edux.onesignal.auto_show_modal';
    public const POST_LOGIN_REDIRECT_ATTRIBUTE = 'edux.onesignal.post_login_redirect_url';

    public const SESSION_KEY = 'onesignal_prompt_seen';
    public const POST_LOGIN_REDIRECT_SESSION_KEY = 'onesignal_post_login_redirect_pending';

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

        $isNotificationsRoute = $request->routeIs('learning.notifications.index');
        $isPushOnboardingRequest = $request->boolean('push_onboarding');

        if ($request->session()->pull(self::POST_LOGIN_REDIRECT_SESSION_KEY, false)) {
            if ($isNotificationsRoute) {
                if (! $request->session()->has(self::SESSION_KEY)) {
                    $request->attributes->set(self::AUTO_SHOW_ATTRIBUTE, true);
                    $request->session()->put(self::SESSION_KEY, true);
                }

                return $next($request);
            }

            $request->attributes->set(
                self::POST_LOGIN_REDIRECT_ATTRIBUTE,
                route('learning.notifications.index', ['push_onboarding' => 1])
            );

            return $next($request);
        }

        if ($isNotificationsRoute && $isPushOnboardingRequest && ! $request->session()->has(self::SESSION_KEY)) {
            $request->attributes->set(self::AUTO_SHOW_ATTRIBUTE, true);
            $request->session()->put(self::SESSION_KEY, true);

            return $next($request);
        }

        if (! $request->session()->has(self::SESSION_KEY)) {
            $request->attributes->set(self::AUTO_SHOW_ATTRIBUTE, true);
            $request->session()->put(self::SESSION_KEY, true);
        }

        return $next($request);
    }
}
