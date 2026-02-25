<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Kavoo;
use App\Models\TrackingAttribution;
use App\Models\TrackingEvent;
use App\Models\TrackingSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class KavooTrackingAttributionService
{
    public function recordApprovedSale(Kavoo $kavoo, ?Course $course = null, ?User $user = null): void
    {
        $context = $this->extractTrackingContext($kavoo->tracking);

        $matchedSession = $this->resolveMatchedSession($context);
        $visitorUuid = $context['edux_vid'] ?? ($matchedSession?->visitor_uuid ?: null);

        $firstTouch = $this->resolveTouchSession($visitorUuid, true) ?? $matchedSession;
        $lastTouch = $this->resolveTouchSession($visitorUuid, false) ?? $matchedSession;

        $occurredAt = $this->resolveOccurredAt($kavoo);
        $amount = $this->resolveAmount($kavoo);
        $currency = $this->resolveCurrency($kavoo) ?? 'BRL';

        $linkedSession = $lastTouch ?? $matchedSession ?? $firstTouch;

        $this->recordPurchaseApprovedEvent(
            $kavoo,
            $course,
            $user,
            $linkedSession,
            $context,
            $occurredAt,
            $amount,
            $currency
        );

        if ($firstTouch) {
            $this->upsertAttributionFromSession(
                $kavoo,
                $course,
                $user,
                $firstTouch,
                'first_touch',
                $occurredAt,
                $amount,
                $currency
            );
        }

        if ($lastTouch) {
            $this->upsertAttributionFromSession(
                $kavoo,
                $course,
                $user,
                $lastTouch,
                'last_touch',
                $occurredAt,
                $amount,
                $currency
            );
        }

        if ($this->hasWebhookAttributionSignal($context)) {
            $this->upsertWebhookTrackingAttribution(
                $kavoo,
                $course,
                $user,
                $matchedSession ?? $lastTouch ?? $firstTouch,
                $context,
                $occurredAt,
                $amount,
                $currency
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $trackingPayload
     * @return array<string, string|null>
     */
    private function extractTrackingContext(?array $trackingPayload): array
    {
        $trackingPayload = is_array($trackingPayload) ? $trackingPayload : [];

        $flat = [];
        $this->flattenTracking($trackingPayload, $flat);

        foreach ($flat as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            foreach ($this->extractQueryPairsFromString($value) as $qKey => $qValue) {
                $flat[$qKey] = $qValue;
                $flat[$this->normalizeKey($qKey)] = $qValue;
            }
        }

        $get = function (array $keys) use ($flat): ?string {
            foreach ($keys as $key) {
                $value = $flat[$key] ?? $flat[$this->normalizeKey($key)] ?? null;
                if (! is_scalar($value)) {
                    continue;
                }
                $text = trim((string) $value);
                if ($text !== '') {
                    return Str::limit($text, 255, '');
                }
            }

            return null;
        };

        return [
            'edux_sid' => $get(['edux_sid', 'session_uuid', 'sid']),
            'edux_vid' => $get(['edux_vid', 'visitor_uuid', 'vid']),
            'utm_source' => $get(['utm_source', 'source']),
            'utm_medium' => $get(['utm_medium', 'medium']),
            'utm_campaign' => $get(['utm_campaign', 'campaign']),
            'utm_content' => $get(['utm_content', 'content']),
            'utm_term' => $get(['utm_term', 'term']),
            'fbclid' => $get(['fbclid']),
            'gclid' => $get(['gclid']),
            'ttclid' => $get(['ttclid']),
            'city_slug' => $get(['edux_city_slug', 'city_slug']),
            'city_name' => $get(['edux_city_name', 'city_name']),
            'course_slug' => $get(['edux_course_slug', 'course_slug']),
            'referrer' => $get(['referrer', 'referer']),
            'raw_tracking_json' => json_encode($trackingPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $flat
     */
    private function flattenTracking(array $source, array &$flat, string $prefix = '', int $depth = 0): void
    {
        if ($depth >= 4) {
            return;
        }

        foreach ($source as $key => $value) {
            $rawKey = trim((string) $key);
            if ($rawKey === '') {
                continue;
            }

            $pathKey = $prefix !== '' ? $prefix.'.'.$rawKey : $rawKey;

            if (is_array($value)) {
                $this->flattenTracking($value, $flat, $pathKey, $depth + 1);
                continue;
            }

            if (! is_scalar($value) || $value === '') {
                continue;
            }

            $text = Str::limit(trim((string) $value), 2000, '');
            $flat[$rawKey] = $text;
            $flat[$pathKey] = $text;
            $flat[$this->normalizeKey($rawKey)] = $text;
            $flat[$this->normalizeKey($pathKey)] = $text;
        }
    }

    /**
     * @return array<string, string>
     */
    private function extractQueryPairsFromString(string $value): array
    {
        $value = trim($value);
        if ($value === '' || ! str_contains($value, '=')) {
            return [];
        }

        $candidates = [$value];
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $query = parse_url($value, PHP_URL_QUERY);
            if (is_string($query) && $query !== '') {
                $candidates[] = $query;
            }
        }

        $pairs = [];
        foreach ($candidates as $candidate) {
            parse_str($candidate, $parsed);
            if (! is_array($parsed)) {
                continue;
            }

            foreach ($parsed as $key => $item) {
                if (! is_string($key) || is_array($item)) {
                    continue;
                }
                $text = trim((string) $item);
                if ($text === '') {
                    continue;
                }
                $pairs[$key] = Str::limit($text, 255, '');
            }
        }

        return $pairs;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $key) ?? $key);
    }

    /**
     * @param  array<string, string|null>  $context
     */
    private function resolveMatchedSession(array $context): ?TrackingSession
    {
        $sid = $context['edux_sid'] ?? null;
        $vid = $context['edux_vid'] ?? null;

        if (is_string($sid) && $sid !== '') {
            $session = TrackingSession::query()->where('session_uuid', $sid)->first();
            if ($session) {
                return $session;
            }
        }

        if (is_string($vid) && $vid !== '') {
            return TrackingSession::query()
                ->where('visitor_uuid', $vid)
                ->orderByDesc('last_seen_at')
                ->first();
        }

        return null;
    }

    private function resolveTouchSession(?string $visitorUuid, bool $first): ?TrackingSession
    {
        if (! is_string($visitorUuid) || trim($visitorUuid) === '') {
            return null;
        }

        $query = TrackingSession::query()->where('visitor_uuid', $visitorUuid);

        if ($first) {
            return $query->orderBy('started_at')->orderBy('id')->first();
        }

        return $query->orderByDesc('last_seen_at')->orderByDesc('id')->first();
    }

    private function resolveOccurredAt(Kavoo $kavoo): CarbonImmutable
    {
        $candidates = [
            Arr::get($kavoo->transaction, 'approved_at'),
            Arr::get($kavoo->payment, 'approved_at'),
            Arr::get($kavoo->status, 'updated_at'),
            Arr::get($kavoo->transaction, 'updated_at'),
            Arr::get($kavoo->transaction, 'created_at'),
            optional($kavoo->updated_at)?->toIso8601String(),
            optional($kavoo->created_at)?->toIso8601String(),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            try {
                return CarbonImmutable::parse($candidate)->utc();
            } catch (\Throwable) {
                continue;
            }
        }

        return CarbonImmutable::now('UTC');
    }

    private function resolveAmount(Kavoo $kavoo): ?float
    {
        $items = is_array($kavoo->items) ? $kavoo->items : [];
        $item = is_array($items[0] ?? null) ? $items[0] : [];

        $itemKeys = [
            'amount',
            'total_amount',
            'total',
            'price',
            'unit_price',
            'offer_price',
            'sale_price',
            'paid_amount',
            'value',
        ];

        foreach ($itemKeys as $key) {
            $value = $this->toMoney(Arr::get($item, $key));
            if ($value !== null) {
                return $value;
            }
        }

        if (count($items) === 1) {
            $fallbackKeys = [
                Arr::get($kavoo->transaction, 'amount'),
                Arr::get($kavoo->transaction, 'total_amount'),
                Arr::get($kavoo->transaction, 'value'),
                Arr::get($kavoo->payment, 'amount'),
                Arr::get($kavoo->payment, 'total'),
                Arr::get($kavoo->payment, 'paid_amount'),
            ];

            foreach ($fallbackKeys as $candidate) {
                $value = $this->toMoney($candidate);
                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function resolveCurrency(Kavoo $kavoo): ?string
    {
        $items = is_array($kavoo->items) ? $kavoo->items : [];
        $item = is_array($items[0] ?? null) ? $items[0] : [];

        foreach ([
            Arr::get($item, 'currency'),
            Arr::get($kavoo->transaction, 'currency'),
            Arr::get($kavoo->payment, 'currency'),
        ] as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }
            $value = strtoupper(trim((string) $candidate));
            if ($value !== '') {
                return Str::limit($value, 12, '');
            }
        }

        return null;
    }

    private function toMoney(mixed $value): ?float
    {
        if (is_string($value)) {
            $raw = trim($value);
            if ($raw === '') {
                return null;
            }

            $normalized = preg_replace('/[^0-9,.\-]/', '', $raw) ?? $raw;
            $hasComma = str_contains($normalized, ',');
            $hasDot = str_contains($normalized, '.');

            if ($hasComma && $hasDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } elseif ($hasComma && ! $hasDot) {
                $normalized = str_replace(',', '.', $normalized);
            }

            if (is_numeric($normalized)) {
                $number = (float) $normalized;
                if (is_finite($number) && $number > 0) {
                    return round($number, 2);
                }
            }
        }

        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) && $number > 0 ? round($number, 2) : null;
    }

    /**
     * @param  array<string, string|null>  $context
     */
    private function recordPurchaseApprovedEvent(
        Kavoo $kavoo,
        ?Course $course,
        ?User $user,
        ?TrackingSession $linkedSession,
        array $context,
        CarbonImmutable $occurredAt,
        ?float $amount,
        ?string $currency
    ): void {
        $eventUuid = 'kavoo_purchase_'.$kavoo->id.'_approved';

        TrackingEvent::updateOrCreate(
            ['event_uuid' => $eventUuid],
            [
                'tracking_session_id' => $linkedSession?->id,
                'session_uuid' => $linkedSession?->session_uuid,
                'visitor_uuid' => $linkedSession?->visitor_uuid ?? $context['edux_vid'],
                'user_id' => $user?->id ?? $linkedSession?->user_id,
                'event_name' => 'PurchaseApproved',
                'event_category' => 'conversion',
                'event_source' => 'kavoo_webhook',
                'occurred_at' => $occurredAt,
                'received_at' => now(),
                'page_url' => null,
                'page_path' => null,
                'page_type' => 'kavoo_webhook',
                'referrer' => $context['referrer'] ?? $linkedSession?->referrer,
                'course_id' => $course?->id,
                'checkout_id' => null,
                'course_slug' => $course?->slug ?? $context['course_slug'],
                'city_slug' => $linkedSession?->city_slug ?? $context['city_slug'],
                'city_name' => $linkedSession?->city_name ?? $context['city_name'],
                'cta_source' => null,
                'value' => $amount,
                'currency' => $currency,
                'properties' => [
                    'transaction_code' => $kavoo->transaction_code,
                    'item_product_id' => $kavoo->item_product_id,
                    'status_code' => $kavoo->status_code,
                    'tracking_context' => array_filter($context, static fn ($v) => $v !== null && $v !== ''),
                ],
            ]
        );
    }

    private function upsertAttributionFromSession(
        Kavoo $kavoo,
        ?Course $course,
        ?User $user,
        TrackingSession $session,
        string $model,
        CarbonImmutable $occurredAt,
        ?float $amount,
        ?string $currency
    ): void {
        TrackingAttribution::updateOrCreate(
            [
                'kavoo_id' => $kavoo->id,
                'attribution_model' => $model,
            ],
            [
                'tracking_session_id' => $session->id,
                'user_id' => $user?->id ?? $session->user_id,
                'course_id' => $course?->id,
                'transaction_code' => $kavoo->transaction_code,
                'item_product_id' => $kavoo->item_product_id,
                'session_uuid' => $session->session_uuid,
                'visitor_uuid' => $session->visitor_uuid,
                'source' => $session->utm_source,
                'medium' => $session->utm_medium,
                'campaign' => $session->utm_campaign,
                'content' => $session->utm_content,
                'term' => $session->utm_term,
                'fbclid' => $session->fbclid,
                'gclid' => $session->gclid,
                'ttclid' => $session->ttclid,
                'referrer' => $session->referrer,
                'referrer_host' => $session->referrer_host,
                'city_slug' => $session->city_slug,
                'city_name' => $session->city_name,
                'amount' => $amount,
                'currency' => $currency,
                'occurred_at' => $occurredAt,
                'properties' => [
                    'first_page_type' => $session->first_page_type,
                    'landing_path' => $session->landing_path,
                    'landing_url' => $session->landing_url,
                ],
            ]
        );
    }

    /**
     * @param  array<string, string|null>  $context
     */
    private function upsertWebhookTrackingAttribution(
        Kavoo $kavoo,
        ?Course $course,
        ?User $user,
        ?TrackingSession $matchedSession,
        array $context,
        CarbonImmutable $occurredAt,
        ?float $amount,
        ?string $currency
    ): void {
        TrackingAttribution::updateOrCreate(
            [
                'kavoo_id' => $kavoo->id,
                'attribution_model' => 'webhook_tracking',
            ],
            [
                'tracking_session_id' => $matchedSession?->id,
                'user_id' => $user?->id ?? $matchedSession?->user_id,
                'course_id' => $course?->id,
                'transaction_code' => $kavoo->transaction_code,
                'item_product_id' => $kavoo->item_product_id,
                'session_uuid' => $matchedSession?->session_uuid ?? $context['edux_sid'],
                'visitor_uuid' => $matchedSession?->visitor_uuid ?? $context['edux_vid'],
                'source' => $context['utm_source'],
                'medium' => $context['utm_medium'],
                'campaign' => $context['utm_campaign'],
                'content' => $context['utm_content'],
                'term' => $context['utm_term'],
                'fbclid' => $context['fbclid'],
                'gclid' => $context['gclid'],
                'ttclid' => $context['ttclid'],
                'referrer' => $context['referrer'],
                'referrer_host' => $this->hostFromUrl($context['referrer']),
                'city_slug' => $context['city_slug'] ?? $matchedSession?->city_slug,
                'city_name' => $context['city_name'] ?? $matchedSession?->city_name,
                'amount' => $amount,
                'currency' => $currency,
                'occurred_at' => $occurredAt,
                'properties' => [
                    'raw_tracking_json' => $context['raw_tracking_json'] ?? null,
                ],
            ]
        );
    }

    /**
     * @param  array<string, string|null>  $context
     */
    private function hasWebhookAttributionSignal(array $context): bool
    {
        foreach ([
            'edux_sid',
            'edux_vid',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'fbclid',
            'gclid',
            'ttclid',
            'city_slug',
        ] as $key) {
            if (! empty($context[$key])) {
                return true;
            }
        }

        return false;
    }

    private function hostFromUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? Str::limit($host, 255, '') : null;
    }
}
