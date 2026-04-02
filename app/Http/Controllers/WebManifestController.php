<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = SystemSetting::current();
        $name = trim((string) ($settings->escola_nome ?? '')) ?: config('app.name', 'EduX');
        $shortName = Str::limit($name, 24, '');
        $scope = rtrim($settings->appUrl(''), '/').'/';
        $iconUrl = $settings->assetUrl('favicon_path')
            ?? $settings->assetUrl('default_logo_dark_path')
            ?? asset('favicon.ico');

        return response()->json([
            'name' => $name,
            'short_name' => $shortName,
            'start_url' => route('dashboard'),
            'scope' => $scope,
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#2563eb',
            'icons' => [
                [
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => $this->iconMimeType($iconUrl),
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $iconUrl,
                    'sizes' => '512x512',
                    'type' => $this->iconMimeType($iconUrl),
                    'purpose' => 'any maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }

    private function iconMimeType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'image/x-icon',
        };
    }
}
