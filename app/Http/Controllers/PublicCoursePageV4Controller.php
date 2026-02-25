<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Support\PublicCoursePageViewDataBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCoursePageV4Controller extends Controller
{
    public function __invoke(Request $request, Course $course, PublicCoursePageViewDataBuilder $builder): View
    {
        $data = $builder->build($course);
        $cityContext = $this->resolveCityContext($request);

        return view('courses.public-v4', [
            ...$data,
            ...$cityContext,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCityContext(Request $request): array
    {
        $raw = (string) $request->query('cidade', '');
        $trimmed = trim(strip_tags($raw));

        if ($trimmed === '') {
            return [
                'hasCityContext' => false,
                'cityDisplayName' => null,
                'cityRaw' => null,
                'cityQueryNormalized' => null,
            ];
        }

        $normalized = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $trimmed) ?? $trimmed;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim(mb_substr($normalized, 0, 60));

        if ($normalized === '') {
            return [
                'hasCityContext' => false,
                'cityDisplayName' => null,
                'cityRaw' => $raw !== '' ? $raw : null,
                'cityQueryNormalized' => null,
            ];
        }

        $displayName = mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');

        return [
            'hasCityContext' => true,
            'cityDisplayName' => $displayName,
            'cityRaw' => $raw,
            'cityQueryNormalized' => $normalized,
        ];
    }
}
