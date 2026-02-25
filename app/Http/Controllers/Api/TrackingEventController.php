<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;

class TrackingEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! (bool) config('tracking.enabled', true)) {
            return response()->json(['status' => 'disabled'], 202);
        }

        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            $decoded = json_decode($request->getContent(), true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $rawEvents = $payload['events'] ?? [];
        if (! is_array($rawEvents)) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $rawEvents = array_slice($rawEvents, 0, max(1, (int) config('tracking.batch_max', 40)));
        $now = CarbonImmutable::now('UTC');
        $normalizedEvents = [];

        foreach ($rawEvents as $rawEvent) {
            if (! is_array($rawEvent)) {
                continue;
            }

            $event = $this->normalizeEvent($rawEvent, $now);
            if ($event !== null) {
                $normalizedEvents[] = $event;
            }
        }

        if ($normalizedEvents === []) {
            return response()->json(['status' => 'ignored'], 202);
        }

        [$visitorUuid, $queueVisitorCookie] = $this->resolveVisitorUuid($request);
        [$session, $queueSessionCookie] = $this->resolveSession($request, $visitorUuid, $normalizedEvents, $now);

        $userId = auth()->id();
        $timestamp = $now->toDateTimeString();

        $rows = [];
        foreach ($normalizedEvents as $event) {
            $rows[] = [
                'tracking_session_id' => $session->id,
                'event_uuid' => $event['event_uuid'],
                'session_uuid' => $session->session_uuid,
                'visitor_uuid' => $visitorUuid,
                'user_id' => $userId,
                'event_name' => $event['event_name'],
                'event_category' => $event['event_category'],
                'event_source' => $event['event_source'],
                'occurred_at' => $event['occurred_at']->toDateTimeString(),
                'received_at' => $timestamp,
                'page_url' => $event['page_url'],
                'page_path' => $event['page_path'],
                'page_type' => $event['page_type'],
                'referrer' => $event['referrer'],
                'course_id' => $event['course_id'],
                'checkout_id' => $event['checkout_id'],
                'course_slug' => $event['course_slug'],
                'city_slug' => $event['city_slug'],
                'city_name' => $event['city_name'],
                'cta_source' => $event['cta_source'],
                'value' => $event['value'],
                'currency' => $event['currency'],
                'properties' => json_encode($event['properties'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('tracking_events')->insertOrIgnore($rows);

        $session->forceFill([
            'last_seen_at' => $now,
            'user_id' => $session->user_id ?: $userId,
        ])->save();

        $response = response()->json(['status' => 'ok'], 202);

        if ($queueVisitorCookie) {
            $response->withCookie($this->makeCookie(
                (string) config('tracking.visitor_cookie', 'edux_vid'),
                $visitorUuid,
                max(1, (int) config('tracking.visitor_cookie_days', 365)) * 24 * 60,
                $request
            ));
        }

        if ($queueSessionCookie) {
            $response->withCookie($this->makeCookie(
                (string) config('tracking.session_cookie', 'edux_sid'),
                $session->session_uuid,
                max(1, (int) config('tracking.session_cookie_days', 30)) * 24 * 60,
                $request
            ));
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $rawEvent
     * @return array<string, mixed>|null
     */
    private function normalizeEvent(array $rawEvent, CarbonImmutable $now): ?array
    {
        $eventName = $this->sanitizeString($rawEvent['event_name'] ?? null, 120);
        if ($eventName === null) {
            return null;
        }

        $occurredAt = $now;
        $occurredAtMs = is_numeric($rawEvent['occurred_at_ms'] ?? null) ? (int) $rawEvent['occurred_at_ms'] : null;
        if ($occurredAtMs !== null && $occurredAtMs > 0) {
            $seconds = (int) floor($occurredAtMs / 1000);
            $candidate = CarbonImmutable::createFromTimestampUTC($seconds);
            if ($candidate->between($now->subDays(7), $now->addDays(1))) {
                $occurredAt = $candidate;
            }
        }

        $properties = $this->sanitizeProperties(is_array($rawEvent['properties'] ?? null) ? $rawEvent['properties'] : []);
        $courseId = $this->positiveInt($rawEvent['course_id'] ?? ($properties['course_id'] ?? null));
        $checkoutId = $this->positiveInt($rawEvent['checkout_id'] ?? ($properties['checkout_id'] ?? null));
        $value = $this->decimalValue(
            $rawEvent['value']
            ?? ($properties['value'] ?? $properties['course_price'] ?? $properties['checkout_price'] ?? null)
        );

        return [
            'event_uuid' => $this->sanitizeUuid($rawEvent['event_uuid'] ?? null),
            'event_name' => $eventName,
            'event_category' => $this->sanitizeString($rawEvent['event_category'] ?? null, 64) ?? $this->inferCategory($eventName),
            'event_source' => $this->sanitizeString($rawEvent['event_source'] ?? null, 64) ?? 'internal',
            'occurred_at' => $occurredAt,
            'page_url' => $this->sanitizeString($rawEvent['page_url'] ?? null, 2000),
            'page_path' => $this->sanitizeString($rawEvent['page_path'] ?? null, 255),
            'page_type' => $this->sanitizeString($rawEvent['page_type'] ?? ($properties['page_type'] ?? null), 120),
            'referrer' => $this->sanitizeString($rawEvent['referrer'] ?? null, 2000),
            'course_id' => $courseId,
            'checkout_id' => $checkoutId,
            'course_slug' => $this->sanitizeString($rawEvent['course_slug'] ?? ($properties['course_slug'] ?? null), 255),
            'city_slug' => $this->sanitizeString($rawEvent['city_slug'] ?? ($properties['city_slug'] ?? null), 255),
            'city_name' => $this->sanitizeString($rawEvent['city_name'] ?? ($properties['city_name'] ?? null), 255),
            'cta_source' => $this->sanitizeString(
                $rawEvent['cta_source'] ?? ($properties['cta_source'] ?? $properties['checkout_source'] ?? null),
                120
            ),
            'value' => $value,
            'currency' => $this->sanitizeString($rawEvent['currency'] ?? ($properties['currency'] ?? null), 12) ?? ($value !== null ? 'BRL' : null),
            'properties' => $properties,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array{0: string, 1: bool}
     */
    private function resolveVisitorUuid(Request $request): array
    {
        $cookieName = (string) config('tracking.visitor_cookie', 'edux_vid');
        $raw = $request->cookie($cookieName);

        if (is_string($raw) && $this->isAcceptableToken($raw)) {
            return [$raw, false];
        }

        return [(string) Str::uuid(), true];
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array{0: TrackingSession, 1: bool}
     */
    private function resolveSession(Request $request, string $visitorUuid, array $events, CarbonImmutable $now): array
    {
        $cookieName = (string) config('tracking.session_cookie', 'edux_sid');
        $rawSessionUuid = $request->cookie($cookieName);
        $idleThreshold = $now->subMinutes(max(1, (int) config('tracking.session_idle_minutes', 30)));

        if (is_string($rawSessionUuid) && $this->isAcceptableToken($rawSessionUuid)) {
            $session = TrackingSession::query()
                ->where('session_uuid', $rawSessionUuid)
                ->where('visitor_uuid', $visitorUuid)
                ->first();

            if ($session && $session->last_seen_at && ! $session->last_seen_at->lt($idleThreshold)) {
                return [$session, false];
            }
        }

        $seed = $this->extractSessionSeed($events);
        $userAgent = (string) ($request->userAgent() ?? '');

        $session = TrackingSession::create([
            'session_uuid' => (string) Str::uuid(),
            'visitor_uuid' => $visitorUuid,
            'user_id' => auth()->id(),
            'started_at' => $now,
            'last_seen_at' => $now,
            'landing_url' => $seed['landing_url'],
            'landing_path' => $seed['landing_path'],
            'first_page_type' => $seed['first_page_type'],
            'referrer' => $seed['referrer'],
            'referrer_host' => $seed['referrer_host'],
            'utm_source' => $seed['utm_source'],
            'utm_medium' => $seed['utm_medium'],
            'utm_campaign' => $seed['utm_campaign'],
            'utm_content' => $seed['utm_content'],
            'utm_term' => $seed['utm_term'],
            'fbclid' => $seed['fbclid'],
            'gclid' => $seed['gclid'],
            'ttclid' => $seed['ttclid'],
            'city_slug' => $seed['city_slug'],
            'city_name' => $seed['city_name'],
            'ip_hash' => $this->ipHash($request),
            'device_type' => $this->detectDeviceType($userAgent),
            'os' => $this->detectOs($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'user_agent' => $this->sanitizeString($userAgent, 4000),
            'properties' => [
                'session_idle_minutes' => (int) config('tracking.session_idle_minutes', 30),
            ],
        ]);

        return [$session, true];
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<string, string|null>
     */
    private function extractSessionSeed(array $events): array
    {
        $first = $events[0] ?? [];
        $pageUrl = $this->sanitizeString($first['page_url'] ?? null, 2000);
        $referrer = $this->sanitizeString($first['referrer'] ?? null, 2000);
        $pagePath = $this->sanitizeString($first['page_path'] ?? $this->pathFromUrl($pageUrl), 255);
        $pageType = $this->sanitizeString($first['page_type'] ?? null, 120);
        $citySlug = $this->sanitizeString($first['city_slug'] ?? null, 255);
        $cityName = $this->sanitizeString($first['city_name'] ?? null, 255);
        $properties = is_array($first['properties'] ?? null) ? $first['properties'] : [];
        $query = $this->queryFromUrl($pageUrl);

        return [
            'landing_url' => $pageUrl,
            'landing_path' => $pagePath,
            'first_page_type' => $pageType,
            'referrer' => $referrer,
            'referrer_host' => $this->hostFromUrl($referrer),
            'utm_source' => $this->sanitizeString($query['utm_source'] ?? ($properties['qp_utm_source'] ?? null), 255),
            'utm_medium' => $this->sanitizeString($query['utm_medium'] ?? ($properties['qp_utm_medium'] ?? null), 255),
            'utm_campaign' => $this->sanitizeString($query['utm_campaign'] ?? ($properties['qp_utm_campaign'] ?? null), 255),
            'utm_content' => $this->sanitizeString($query['utm_content'] ?? ($properties['qp_utm_content'] ?? null), 255),
            'utm_term' => $this->sanitizeString($query['utm_term'] ?? ($properties['qp_utm_term'] ?? null), 255),
            'fbclid' => $this->sanitizeString($query['fbclid'] ?? ($properties['qp_fbclid'] ?? null), 255),
            'gclid' => $this->sanitizeString($query['gclid'] ?? ($properties['qp_gclid'] ?? null), 255),
            'ttclid' => $this->sanitizeString($query['ttclid'] ?? ($properties['qp_ttclid'] ?? null), 255),
            'city_slug' => $citySlug,
            'city_name' => $cityName,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizeProperties(array $value, int $depth = 0): array
    {
        if ($depth >= 4) {
            return [];
        }

        $result = [];
        $count = 0;

        foreach ($value as $key => $item) {
            if ($count >= 100) {
                break;
            }

            $safeKey = $this->sanitizeString((string) $key, 80);
            if ($safeKey === null) {
                continue;
            }

            if (is_array($item)) {
                $result[$safeKey] = $this->sanitizeNestedArray($item, $depth + 1);
            } elseif (is_bool($item) || is_null($item) || is_int($item) || is_float($item)) {
                $result[$safeKey] = $item;
            } else {
                $result[$safeKey] = $this->sanitizeString($item, 500);
            }

            $count++;
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $value
     * @return array<int|string, mixed>
     */
    private function sanitizeNestedArray(array $value, int $depth): array
    {
        if ($depth >= 4) {
            return [];
        }

        $isAssoc = array_keys($value) !== range(0, count($value) - 1);
        $result = [];
        $count = 0;

        foreach ($value as $key => $item) {
            if ($count >= 50) {
                break;
            }

            if (is_array($item)) {
                $clean = $this->sanitizeNestedArray($item, $depth + 1);
            } elseif (is_bool($item) || is_null($item) || is_int($item) || is_float($item)) {
                $clean = $item;
            } else {
                $clean = $this->sanitizeString($item, 300);
            }

            if ($isAssoc) {
                $safeKey = $this->sanitizeString((string) $key, 80);
                if ($safeKey === null) {
                    continue;
                }
                $result[$safeKey] = $clean;
            } else {
                $result[] = $clean;
            }

            $count++;
        }

        return $result;
    }

    private function sanitizeString(mixed $value, int $max = 255): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        return Str::limit($string, $max, '');
    }

    private function sanitizeUuid(mixed $value): string
    {
        $token = $this->sanitizeString($value, 64);

        return $this->isAcceptableToken($token) ? $token : (string) Str::uuid();
    }

    private function isAcceptableToken(?string $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        return preg_match('/^[A-Za-z0-9._:-]{8,64}$/', $token) === 1;
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function decimalValue(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;
        if (! is_finite($number) || $number <= 0) {
            return null;
        }

        return round($number, 2);
    }

    private function inferCategory(string $eventName): string
    {
        $value = strtolower($eventName);

        return match (true) {
            str_contains($value, 'checkout') => 'checkout',
            str_contains($value, 'lead') => 'lead',
            str_contains($value, 'click') => 'cta',
            str_contains($value, 'view') => 'view',
            default => 'event',
        };
    }

    private function pathFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return $this->sanitizeString(is_string($path) ? $path : null, 255);
    }

    private function hostFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $this->sanitizeString(is_string($host) ? $host : null, 255);
    }

    /**
     * @return array<string, string>
     */
    private function queryFromUrl(?string $url): array
    {
        if (! $url) {
            return [];
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);
        if (! is_array($params)) {
            return [];
        }

        $result = [];
        foreach ($params as $key => $value) {
            if (! is_string($key) || is_array($value)) {
                continue;
            }

            $result[$key] = (string) $value;
        }

        return $result;
    }

    private function ipHash(Request $request): ?string
    {
        $ip = trim((string) $request->ip());
        if ($ip === '') {
            return null;
        }

        return hash('sha256', $ip.'|'.(string) config('app.key'));
    }

    private function detectDeviceType(string $userAgent): ?string
    {
        $ua = strtolower($userAgent);

        if ($ua === '') {
            return null;
        }

        return match (true) {
            str_contains($ua, 'ipad') || str_contains($ua, 'tablet') => 'tablet',
            str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') => 'mobile',
            default => 'desktop',
        };
    }

    private function detectOs(string $userAgent): ?string
    {
        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => null,
        };
    }

    private function detectBrowser(string $userAgent): ?string
    {
        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'edg/') => 'Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'chrome/') && ! str_contains($ua, 'edg/') => 'Chrome',
            str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/') => 'Safari',
            str_contains($ua, 'firefox/') => 'Firefox',
            default => null,
        };
    }

    private function makeCookie(string $name, string $value, int $minutes, Request $request): HttpCookie
    {
        return Cookie::make(
            $name,
            $value,
            $minutes,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'lax'
        );
    }
}

