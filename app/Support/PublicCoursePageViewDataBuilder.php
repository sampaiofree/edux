<?php

namespace App\Support;

use App\Models\CertificateBranding;
use App\Models\Course;
use Illuminate\Support\Arr;

class PublicCoursePageViewDataBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Course $course): array
    {
        abort_if($course->status !== 'published', 404);

        $course->loadMissing([
            'owner',
            'modules.lessons' => fn ($query) => $query->orderBy('position'),
            'certificateBranding',
            'enrollments',
            'checkouts' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['bonuses' => fn ($bonusQuery) => $bonusQuery->orderBy('id')])
                ->orderBy('hours'),
        ]);

        $branding = CertificateBranding::resolveForCourse($course);

        $previewLessons = $course->modules
            ->sortBy('position')
            ->flatMap(fn ($module) => $module->lessons->sortBy('position'))
            ->take(5)
            ->values()
            ->map(function ($lesson) use ($course): array {
                $youtubeId = $this->extractYoutubeId($lesson->video_url);
                $playerType = $this->playerTypeFor($lesson->video_url, $youtubeId);
                $playerUrl = $this->playerUrlFor($lesson->video_url, $youtubeId);

                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'youtube_id' => $youtubeId,
                    'video_url' => $lesson->video_url,
                    'player_type' => $playerType,
                    'player_url' => $playerUrl,
                    'thumb_url' => $youtubeId
                        ? "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg"
                        : $course->coverImageUrl(),
                ];
            });

        $buyUrl = $course->checkouts->first()?->checkout_url
            ?? $course->certificate_payment_url
            ?? '#oferta';

        $certificateFrontPreview = view('learning.certificates.templates.front', [
            'course' => $course,
            'branding' => $branding,
            'displayName' => 'Seu nome aqui',
            'issuedAt' => now(),
            'mode' => 'preview',
            'presentation' => 'minimal',
        ])->render();

        $certificateBackPreview = view('learning.certificates.templates.back', [
            'course' => $course,
            'branding' => $branding,
            'mode' => 'preview',
            'presentation' => 'minimal',
        ])->render();

        return [
            'course' => $course,
            'studentCount' => $course->enrollments->count(),
            'previewLessons' => $previewLessons,
            'buyUrl' => $buyUrl,
            'certificateFrontPreview' => $certificateFrontPreview,
            'certificateBackPreview' => $certificateBackPreview,
        ];
    }

    private function extractYoutubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $patterns = [
            '/youtu\.be\/([\w-]+)/',
            '/youtube\.com\/watch\?v=([\w-]+)/',
            '/youtube\.com\/embed\/([\w-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return Arr::get($matches, 1);
            }
        }

        return null;
    }

    private function playerTypeFor(?string $url, ?string $youtubeId): string
    {
        if ($youtubeId) {
            return 'youtube';
        }

        if (! $url) {
            return 'none';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['mp4', 'webm', 'ogg', 'mov', 'm4v'], true)) {
            return 'video';
        }

        return 'iframe';
    }

    private function playerUrlFor(?string $url, ?string $youtubeId): ?string
    {
        if ($youtubeId) {
            return "https://www.youtube.com/embed/{$youtubeId}?modestbranding=1&rel=0&enablejsapi=1";
        }

        if (! $url) {
            return null;
        }

        return $url;
    }
}
