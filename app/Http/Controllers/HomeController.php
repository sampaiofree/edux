<?php

namespace App\Http\Controllers;

use App\Support\HomePageDataBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, HomePageDataBuilder $builder): View
    {
        return view('home.index', [
            ...$builder->build(),
            'cityDisplayName' => $this->resolveCityDisplayName($request),
        ]);
    }

    private function resolveCityDisplayName(Request $request): ?string
    {
        $raw = (string) $request->query('cidade', '');
        $trimmed = trim(strip_tags($raw));

        if ($trimmed === '') {
            return null;
        }

        $normalized = urldecode($trimmed);
        $normalized = str_replace(['-', '_'], ' ', $normalized);
        $normalized = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim(mb_substr($normalized, 0, 60));

        if ($normalized === '') {
            return null;
        }

        return mb_convert_case(mb_strtolower($normalized, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
