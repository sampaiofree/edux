<?php

namespace App\Support;

use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\SupportWhatsappNumber;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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
            'supportWhatsappNumber',
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
            'supportWhatsappContact' => $this->resolveSupportWhatsappContact($course),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveSupportWhatsappContact(Course $course): ?array
    {
        $mode = $course->support_whatsapp_mode ?: Course::SUPPORT_WHATSAPP_MODE_ALL;
        $selected = $course->supportWhatsappNumber;

        $number = null;

        if ($mode === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC && $selected) {
            $number = $selected;
        } else {
            $activeNumbers = SupportWhatsappNumber::query()
                ->active()
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            if ($activeNumbers->isNotEmpty()) {
                $number = $this->pickRotatingWhatsappNumber($activeNumbers, $course);
            } elseif ($selected) {
                // Fallback para não quebrar a LP caso o número específico exista mas esteja inativo.
                $number = $selected;
            }
        }

        if (! $number) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $number->whatsapp) ?: '';
        if ($digits === '') {
            return null;
        }

        $message = rawurlencode("Olá! Quero tirar dúvidas sobre o curso {$course->title}.");
        $link = "https://wa.me/{$digits}?text={$message}";

        return [
            'id' => $number->id,
            'label' => $number->label,
            'whatsapp' => $number->whatsapp,
            'description' => $number->description,
            'link' => $link,
            'mode' => $mode,
            'is_rotating' => $mode === Course::SUPPORT_WHATSAPP_MODE_ALL,
        ];
    }

    private function pickRotatingWhatsappNumber(Collection $numbers, Course $course): ?SupportWhatsappNumber
    {
        if ($numbers->isEmpty()) {
            return null;
        }

        $visitorSeed = $this->supportRotationSeed($course);
        $hash = hash('sha256', $visitorSeed);
        $index = hexdec(substr($hash, 0, 8)) % $numbers->count();

        return $numbers->values()->get($index);
    }

    private function supportRotationSeed(Course $course): string
    {
        $request = request();

        $visitorUuid = trim((string) $request->cookie('edux_vid', ''));
        $sessionId = '';

        try {
            if (method_exists($request, 'hasSession') && $request->hasSession()) {
                $sessionId = (string) optional($request->session())->getId();
            }
        } catch (\Throwable) {
            $sessionId = '';
        }

        $ip = (string) ($request->ip() ?? '');
        $ua = (string) ($request->userAgent() ?? '');

        return implode('|', [
            'course',
            (string) $course->id,
            $visitorUuid !== '' ? $visitorUuid : 'no-visitor',
            $sessionId !== '' ? $sessionId : 'no-session',
            $ip !== '' ? $ip : 'no-ip',
            $ua !== '' ? substr($ua, 0, 120) : 'no-ua',
        ]);
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
