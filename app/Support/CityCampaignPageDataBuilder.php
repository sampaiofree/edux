<?php

namespace App\Support;

use App\Models\Course;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CityCampaignPageDataBuilder
{
    /**
     * @return array{
     *   view_data: array<string, mixed>,
     *   should_queue_cookie: bool,
     *   countdown_cookie_name: string,
     *   countdown_cookie_value: string,
     *   countdown_cookie_minutes: int
     * }
     */
    public function build(Request $request, string $cidade, ?string $cityVariant = null): array
    {
        $cityDisplayName = $this->formatCityName($cidade);
        $citySlug = $this->normalizeCitySlug($cidade, $cityDisplayName);

        $countdownHours = max(1, (int) config('city_campaign.countdown_hours', 8));
        $cookieName = (string) config('city_campaign.cookie_prefix', 'edux_city_campaign_')
            . sha1($citySlug.'|all_courses');
        $cookieMinutes = max(1, (int) config('city_campaign.cookie_days', 30)) * 24 * 60;

        [$startedAtUtc, $shouldQueueCookie, $isFreshCountdown] = $this->resolveStartedAtUtc($request, $cookieName);

        $nowUtc = CarbonImmutable::now('UTC');
        $expiresAtUtc = $startedAtUtc->addHours($countdownHours);
        $isClosed = $nowUtc->greaterThanOrEqualTo($expiresAtUtc);
        $secondsRemaining = max(0, $expiresAtUtc->getTimestamp() - $nowUtc->getTimestamp());

        $catalogData = $this->catalogBaseData();
        $courseCards = collect($catalogData['courses'])
            ->map(function (array $course) use ($request, $citySlug, $cityDisplayName, $cityVariant): array {
                $course['course_url'] = $this->appendCampaignQuery(
                    $request,
                    route('courses.public.show', ['course' => $course['slug']]),
                    $citySlug,
                    $cityDisplayName,
                    $cityVariant
                );

                return $course;
            })
            ->values();

        $coursesCount = (int) ($catalogData['courses_count'] ?? 0);
        $globalLowestCheckoutValue = isset($catalogData['global_lowest_checkout_value']) && is_numeric($catalogData['global_lowest_checkout_value'])
            ? (float) $catalogData['global_lowest_checkout_value']
            : null;
        $globalLowestCheckoutPriceLabel = $catalogData['global_lowest_checkout_price_label'] ?? null;

        $catalogUrl = $this->appendCampaignQuery(
            $request,
            route('courses.public.index'),
            $citySlug,
            $cityDisplayName,
            $cityVariant
        );
        $waitlistUrl = (string) config('city_campaign.waitlist_url', '#');

        $pageSettings = $this->pageSettingsData();

        return [
            'view_data' => [
                'citySlug' => $citySlug,
                'cityDisplayName' => $cityDisplayName,
                'courses' => $courseCards,
                'coursesCount' => $coursesCount,
                'catalogUrl' => $catalogUrl,
                'waitlistUrl' => $waitlistUrl,
                'countdownHours' => $countdownHours,
                'countdownStartedAtIso' => $startedAtUtc->toIso8601String(),
                'countdownExpiresAtIso' => $expiresAtUtc->toIso8601String(),
                'countdownExpiresAtUnix' => $expiresAtUtc->getTimestamp(),
                'secondsRemaining' => $secondsRemaining,
                'isClosed' => $isClosed,
                'isFreshCountdown' => $isFreshCountdown,
                'showEmploymentDisclaimer' => (bool) config('city_campaign.show_employment_disclaimer', true),
                'metaAdsPixelId' => (string) ($pageSettings['meta_ads_pixel_id'] ?? ''),
                'cartaEstagioImageUrl' => $pageSettings['carta_estagio_image_url'] ?? null,
                'globalLowestCheckoutValue' => $globalLowestCheckoutValue,
                'globalLowestCheckoutPriceLabel' => $globalLowestCheckoutPriceLabel,
            ],
            'should_queue_cookie' => $shouldQueueCookie,
            'countdown_cookie_name' => $cookieName,
            'countdown_cookie_value' => (string) $startedAtUtc->getTimestamp(),
            'countdown_cookie_minutes' => $cookieMinutes,
        ];
    }

    /**
     * @return array{courses: array<int, array<string, mixed>>, courses_count: int, global_lowest_checkout_value: float|null, global_lowest_checkout_price_label: string|null}
     */
    private function catalogBaseData(): array
    {
        $ttlMinutes = max(1, (int) config('city_campaign.catalog_cache_minutes', 5));
        $cacheEnabled = (bool) config('city_campaign.cache_enabled', true) && ! app()->runningUnitTests();

        $resolver = function (): array {
            $courses = Course::query()
                ->select([
                    'id',
                    'slug',
                    'title',
                    'summary',
                    'description',
                    'cover_image_path',
                    'duration_minutes',
                    'published_at',
                ])
                ->where('status', 'published')
                ->with([
                    'checkouts' => fn ($query) => $query
                        ->select([
                            'id',
                            'course_id',
                            'nome',
                            'hours',
                            'price',
                        ])
                        ->where('is_active', true)
                        ->orderBy('price')
                        ->orderBy('hours'),
                ])
                ->orderByDesc('published_at')
                ->orderBy('title')
                ->get();

            $courseCards = $courses->map(function (Course $course): array {
                $lowestCheckout = $course->checkouts->first();
                $lowestCheckoutValue = $lowestCheckout ? (float) $lowestCheckout->price : null;
                $durationHoursLabel = $course->duration_minutes
                    ? rtrim(rtrim(number_format($course->duration_minutes / 60, 1, ',', '.'), '0'), ',')
                    : null;

                return [
                    'id' => $course->id,
                    'slug' => $course->slug,
                    'title' => $course->title,
                    'summary' => $course->summary ?: Str::limit(strip_tags((string) $course->description), 150),
                    'cover_image_url' => $course->coverImageUrl(),
                    'duration_hours_label' => $durationHoursLabel,
                    'lowest_checkout_value' => $lowestCheckoutValue,
                    'lowest_checkout_price_label' => $lowestCheckoutValue !== null
                        ? 'R$ '.number_format($lowestCheckoutValue, 2, ',', '.')
                        : null,
                    'lowest_checkout_name' => $lowestCheckout
                        ? ($lowestCheckout->nome ?: ('Opção '.$lowestCheckout->hours.'h'))
                        : null,
                ];
            });

            $courseCards = $this->sortCourseCards($courseCards)->values();
            $globalLowestCheckoutValue = $courseCards
                ->pluck('lowest_checkout_value')
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (float) $value)
                ->min();

            return [
                'courses' => $courseCards->all(),
                'courses_count' => $courseCards->count(),
                'global_lowest_checkout_value' => $globalLowestCheckoutValue !== null ? (float) $globalLowestCheckoutValue : null,
                'global_lowest_checkout_price_label' => $globalLowestCheckoutValue !== null
                    ? 'R$ '.number_format((float) $globalLowestCheckoutValue, 2, ',', '.')
                    : null,
            ];
        };

        if (! $cacheEnabled) {
            return $resolver();
        }

        return Cache::remember(
            CityCampaignCache::catalogDataKey(),
            now()->addMinutes($ttlMinutes),
            $resolver
        );
    }

    /**
     * @return array{meta_ads_pixel_id: string, carta_estagio_image_url: string|null}
     */
    private function pageSettingsData(): array
    {
        $ttlMinutes = max(1, (int) config('city_campaign.settings_cache_minutes', 5));
        $cacheEnabled = (bool) config('city_campaign.cache_enabled', true) && ! app()->runningUnitTests();

        $resolver = function (): array {
            $settings = SystemSetting::current();

            return [
                'meta_ads_pixel_id' => trim((string) ($settings->meta_ads_pixel ?? '')),
                'carta_estagio_image_url' => $settings->assetUrl('carta_estagio'),
            ];
        };

        if (! $cacheEnabled) {
            return $resolver();
        }

        return Cache::remember(
            CityCampaignCache::settingsDataKey(),
            now()->addMinutes($ttlMinutes),
            $resolver
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $courseCards
     * @return Collection<int, array<string, mixed>>
     */
    private function sortCourseCards(Collection $courseCards): Collection
    {
        return $courseCards->sort(function (array $a, array $b): int {
            $aHasPrice = is_numeric($a['lowest_checkout_value']);
            $bHasPrice = is_numeric($b['lowest_checkout_value']);

            if ($aHasPrice && $bHasPrice) {
                $priceComparison = ((float) $a['lowest_checkout_value']) <=> ((float) $b['lowest_checkout_value']);

                if ($priceComparison !== 0) {
                    return $priceComparison;
                }
            } elseif ($aHasPrice !== $bHasPrice) {
                return $aHasPrice ? -1 : 1;
            }

            return strcasecmp((string) $a['title'], (string) $b['title']);
        });
    }

    /**
     * @return array{0: CarbonImmutable, 1: bool, 2: bool}
     */
    private function resolveStartedAtUtc(Request $request, string $cookieName): array
    {
        $raw = $request->cookie($cookieName);

        if (is_string($raw) && preg_match('/^\d{9,12}$/', $raw) === 1) {
            $timestamp = (int) $raw;
            $maxFuture = CarbonImmutable::now('UTC')->addDays(365)->getTimestamp();

            if ($timestamp > 0 && $timestamp <= $maxFuture) {
                return [CarbonImmutable::createFromTimestampUTC($timestamp), false, false];
            }
        }

        return [CarbonImmutable::now('UTC'), true, true];
    }

    private function appendCampaignQuery(
        Request $request,
        string $baseUrl,
        string $citySlug,
        string $cityDisplayName,
        ?string $cityVariant = null
    ): string {
        $query = $request->query();
        unset($query['curso']);

        $context = [
            'edux_city_slug' => $citySlug,
            'edux_city_name' => $cityDisplayName,
            'edux_source' => 'city_campaign',
            'edux_campaign_window' => 'open_8h',
        ];

        if ($cityVariant !== null && $cityVariant !== '') {
            $context['edux_city_variant'] = $cityVariant;
        }

        foreach ($context as $key => $value) {
            if (! array_key_exists($key, $query)) {
                $query[$key] = $value;
            }
        }

        return $query === [] ? $baseUrl : $baseUrl.'?'.http_build_query($query);
    }

    private function normalizeCitySlug(string $rawCity, string $displayName): string
    {
        $decoded = urldecode($rawCity);
        $slug = Str::slug(str_replace('_', ' ', $decoded), '-');

        if ($slug !== '') {
            return $slug;
        }

        $displaySlug = Str::slug($displayName, '-');

        return $displaySlug !== '' ? $displaySlug : 'cidade';
    }

    private function formatCityName(string $rawCity): string
    {
        $decoded = urldecode($rawCity);
        $decoded = str_replace(['-', '_'], ' ', $decoded);
        $decoded = trim(preg_replace('/\s+/u', ' ', $decoded) ?? $decoded);

        if ($decoded === '') {
            return 'Sua Cidade';
        }

        return mb_convert_case(mb_strtolower($decoded, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
