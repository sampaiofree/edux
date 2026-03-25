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
        $vacancyContext = $this->resolveVacancyContext($course, $data['supportWhatsappContact'] ?? null);

        return view('courses.public-v4', [
            ...$data,
            ...$cityContext,
            ...$vacancyContext,
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

    /**
     * @param  mixed  $supportWhatsappContact
     * @return array<string, mixed>
     */
    private function resolveVacancyContext(Course $course, mixed $supportWhatsappContact): array
    {
        $waitlistMessage = "Quero entrar na lista de espera do curso {$course->title}.";

        return [
            'lpVacancyWaitlistUrl' => $this->buildWaitlistWhatsappUrl($supportWhatsappContact, $waitlistMessage),
            'lpVacancyWaitlistMessage' => $waitlistMessage,
        ];
    }

    /**
     * @param  mixed  $supportWhatsappContact
     */
    private function buildWaitlistWhatsappUrl(mixed $supportWhatsappContact, string $message): ?string
    {
        if (! is_array($supportWhatsappContact)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) ($supportWhatsappContact['whatsapp'] ?? '')) ?: '';
        if ($digits === '') {
            $supportWhatsappLink = trim((string) ($supportWhatsappContact['link'] ?? ''));
            if ($supportWhatsappLink !== '' && preg_match('/wa\.me\/(\d+)/', $supportWhatsappLink, $matches) === 1) {
                $digits = (string) ($matches[1] ?? '');
            }
        }

        if ($digits === '') {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }
}
