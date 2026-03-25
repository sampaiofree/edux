<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\SupportWhatsappNumber;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class PublicCatalog extends Component
{
    public string $context = 'catalog';
    public string $search = '';
    public int $perPage = 9;

    public function mount(string $context = 'catalog'): void
    {
        $this->context = $context === 'home' ? 'home' : 'catalog';
        $this->perPage = $this->context === 'home' ? 6 : 9;
    }

    public function updatedSearch(): void
    {
        $this->perPage = $this->context === 'home' ? 6 : 9;
    }

    public function loadMore(): void
    {
        $this->perPage += 6;
    }

    public function render()
    {
        $query = Course::query()
            ->where('status', 'published')
            ->with('supportWhatsappNumber')
            ->when($this->search !== '', function ($query) {
                $search = $this->search;

                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('summary', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            });

        if ($this->context === 'home') {
            $query
                ->withMin([
                    'checkouts as active_checkout_min_price' => fn ($query) => $query->where('is_active', true),
                ], 'price')
                ->orderByRaw('case when active_checkout_min_price is null then 1 else 0 end')
                ->orderBy('active_checkout_min_price')
                ->orderBy('title');
        } else {
            $query
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');
        }

        $total = (clone $query)->count();
        $courses = $query->take($this->perPage)->get();
        $defaultCourseCover = SystemSetting::current()->assetUrl('default_course_cover_path');
        $homeCityScope = $this->context === 'home'
            ? $this->normalizeCityScope((string) request()->query('cidade', ''))
            : '';
        $activeSupportWhatsappNumbers = $this->context === 'home'
            ? SupportWhatsappNumber::query()
                ->active()
                ->orderBy('position')
                ->orderBy('id')
                ->get()
            : collect();

        $courseCards = $courses->map(function (Course $course) use ($defaultCourseCover, $activeSupportWhatsappNumbers, $homeCityScope): array {
            $summary = $course->summary ?: Str::limit(strip_tags((string) $course->description), 150);
            $durationHoursLabel = $course->duration_minutes
                ? rtrim(rtrim(number_format($course->duration_minutes / 60, 1, ',', '.'), '0'), ',')
                : null;
            $waitlistMessage = $this->buildWaitlistMessage($course);
            $waitlistWhatsappUrl = $this->context === 'home'
                ? $this->resolveWaitlistWhatsappUrl($course, $activeSupportWhatsappNumbers, $waitlistMessage)
                : null;
            $courseUrl = $this->buildCourseUrl($course, $homeCityScope);

            return [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
                'summary' => $summary,
                'headline' => Str::limit($summary, 135),
                'cover_url' => $course->coverImageUrl() ?? $defaultCourseCover,
                'duration_label' => $durationHoursLabel,
                'min_checkout_price' => $course->active_checkout_min_price !== null
                    ? (float) $course->active_checkout_min_price
                    : null,
                'course_url' => $courseUrl,
                'waitlist_message' => $waitlistMessage,
                'waitlist_whatsapp_url' => $waitlistWhatsappUrl,
                'city_scope' => $homeCityScope,
            ];
        })->values();

        return view('livewire.public-catalog', [
            'courses' => $courseCards,
            'context' => $this->context,
            'hasMore' => $courseCards->count() < $total,
        ]);
    }

    private function buildWaitlistMessage(Course $course): string
    {
        return "Quero entrar na lista de espera do curso {$course->title}.";
    }

    private function buildCourseUrl(Course $course, string $homeCityScope): string
    {
        $baseUrl = route('courses.public.show', $course);

        if ($this->context !== 'home') {
            return $baseUrl;
        }

        $query = ['edux_source' => 'home'];
        if ($homeCityScope !== '') {
            $query['cidade'] = $homeCityScope;
        }

        return $baseUrl.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function normalizeCityScope(string $value): string
    {
        $trimmed = trim(strip_tags($value));
        if ($trimmed === '') {
            return '';
        }

        $normalized = urldecode($trimmed);
        $normalized = str_replace(['-', '_'], ' ', $normalized);
        $normalized = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim(mb_strtolower(mb_substr($normalized, 0, 60), 'UTF-8'));

        return $normalized;
    }

    private function resolveWaitlistWhatsappUrl(
        Course $course,
        Collection $activeSupportWhatsappNumbers,
        string $message
    ): ?string {
        $mode = $course->support_whatsapp_mode ?: Course::SUPPORT_WHATSAPP_MODE_ALL;
        $selected = $course->supportWhatsappNumber;

        $number = null;

        if ($mode === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC && $selected) {
            $number = $selected;
        } elseif ($mode === Course::SUPPORT_WHATSAPP_MODE_ALL && $activeSupportWhatsappNumbers->isNotEmpty()) {
            $number = $this->pickRotatingWhatsappNumber($activeSupportWhatsappNumbers, $course);
        }

        if (! $number) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $number->whatsapp) ?: '';
        if ($digits === '') {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
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
}
