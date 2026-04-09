<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class AuthPageDataBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $settings = SystemSetting::current();
        $playStoreLink = trim((string) ($settings->play_store_link ?? ''));
        $appleStoreLink = trim((string) ($settings->apple_store_link ?? ''));
        $forceAppEnabled = (bool) $settings->force_app;
        $hasStoreLinks = $playStoreLink !== '' || $appleStoreLink !== '';
        $admBypass = $request->query('adm') === '1';
        $loginForceAppActive = $forceAppEnabled && $hasStoreLinks && ! $admBypass;

        return [
            'settings' => $settings,
            'playStoreLink' => $playStoreLink !== '' ? $playStoreLink : null,
            'appleStoreLink' => $appleStoreLink !== '' ? $appleStoreLink : null,
            'forceAppEnabled' => $forceAppEnabled,
            'hasStoreLinks' => $hasStoreLinks,
            'admBypass' => $admBypass,
            'loginForceAppActive' => $loginForceAppActive,
        ];
    }
}
