<?php

namespace App\Livewire\Admin;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TrackingReport extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $utmSource = '';
    public string $utmCampaign = '';
    public string $citySlug = '';
    public string $pageType = '';
    public string $eventName = '';

    protected $queryString = [
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'utmSource' => ['except' => ''],
        'utmCampaign' => ['except' => ''],
        'citySlug' => ['except' => ''],
        'pageType' => ['except' => ''],
        'eventName' => ['except' => ''],
    ];

    public function mount(): void
    {
        $today = now()->toDateString();
        $defaultFrom = now()->subDays(7)->toDateString();

        $this->dateFrom = $this->dateFrom !== '' ? $this->dateFrom : $defaultFrom;
        $this->dateTo = $this->dateTo !== '' ? $this->dateTo : $today;
    }

    public function resetFilters(): void
    {
        $this->dateFrom = now()->subDays(7)->toDateString();
        $this->dateTo = now()->toDateString();
        $this->utmSource = '';
        $this->utmCampaign = '';
        $this->citySlug = '';
        $this->pageType = '';
        $this->eventName = '';
    }

    public function render()
    {
        $baseQuery = $this->baseEventsQuery();

        return view('livewire.admin.tracking-report', [
            'summary' => $this->summaryMetrics(clone $baseQuery),
            'sourceRows' => $this->sourceBreakdown(clone $baseQuery),
            'cityRows' => $this->cityBreakdown(clone $baseQuery),
            'courseRows' => $this->courseBreakdown(clone $baseQuery),
            'funnelRows' => $this->funnelBreakdown(clone $baseQuery),
            'recentEvents' => $this->recentEvents(clone $baseQuery),
            'attributionModelRows' => $this->attributionModelBreakdown(),
            'recentAttributions' => $this->recentAttributions(),
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    private function baseEventsQuery(): Builder
    {
        $query = DB::table('tracking_events as e')
            ->leftJoin('tracking_sessions as s', 's.id', '=', 'e.tracking_session_id');

        $this->applyFilters($query);

        return $query;
    }

    private function baseAttributionsQuery(): Builder
    {
        $query = DB::table('tracking_attributions as a')
            ->leftJoin('tracking_sessions as s', 's.id', '=', 'a.tracking_session_id')
            ->leftJoin('courses as c', 'c.id', '=', 'a.course_id');

        $this->applyAttributionFilters($query);

        return $query;
    }

    private function applyFilters(Builder $query): void
    {
        $from = $this->parseDate($this->dateFrom)?->startOfDay();
        $to = $this->parseDate($this->dateTo)?->endOfDay();

        if ($from) {
            $query->where('e.occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('e.occurred_at', '<=', $to);
        }

        if ($this->utmSource !== '') {
            $query->where('s.utm_source', $this->utmSource);
        }

        if ($this->utmCampaign !== '') {
            $query->where('s.utm_campaign', $this->utmCampaign);
        }

        if ($this->pageType !== '') {
            $query->where('e.page_type', $this->pageType);
        }

        if ($this->eventName !== '') {
            $query->where('e.event_name', $this->eventName);
        }

        if ($this->citySlug !== '') {
            $query->where(function (Builder $cityQuery): void {
                $cityQuery
                    ->where('e.city_slug', $this->citySlug)
                    ->orWhere(function (Builder $fallbackQuery): void {
                        $fallbackQuery->whereNull('e.city_slug')
                            ->where('s.city_slug', $this->citySlug);
                    });
            });
        }
    }

    private function applyAttributionFilters(Builder $query): void
    {
        $from = $this->parseDate($this->dateFrom)?->startOfDay();
        $to = $this->parseDate($this->dateTo)?->endOfDay();

        if ($from) {
            $query->where('a.occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('a.occurred_at', '<=', $to);
        }

        if ($this->utmSource !== '') {
            $query->where(function (Builder $sourceQuery): void {
                $sourceQuery->where('a.source', $this->utmSource)
                    ->orWhere(function (Builder $fallbackQuery): void {
                        $fallbackQuery->whereNull('a.source')
                            ->where('s.utm_source', $this->utmSource);
                    });
            });
        }

        if ($this->utmCampaign !== '') {
            $query->where(function (Builder $campaignQuery): void {
                $campaignQuery->where('a.campaign', $this->utmCampaign)
                    ->orWhere(function (Builder $fallbackQuery): void {
                        $fallbackQuery->whereNull('a.campaign')
                            ->where('s.utm_campaign', $this->utmCampaign);
                    });
            });
        }

        if ($this->citySlug !== '') {
            $query->where(function (Builder $cityQuery): void {
                $cityQuery->where('a.city_slug', $this->citySlug)
                    ->orWhere(function (Builder $fallbackQuery): void {
                        $fallbackQuery->whereNull('a.city_slug')
                            ->where('s.city_slug', $this->citySlug);
                    });
            });
        }
    }

    /**
     * @return array<string, int|float|null>
     */
    private function summaryMetrics(Builder $query): array
    {
        $row = $query
            ->selectRaw('COUNT(*) as events_count')
            ->selectRaw('COUNT(DISTINCT e.tracking_session_id) as sessions_count')
            ->selectRaw('COUNT(DISTINCT e.visitor_uuid) as visitors_count')
            ->selectRaw("SUM(CASE WHEN e.event_name = 'CityCampaignView' THEN 1 ELSE 0 END) as city_views")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'CityCampaignCtaClick' AND COALESCE(e.cta_source, '') IN ('hero_primary', 'hero_primary_v2') THEN 1 ELSE 0 END) as hero_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'CityCampaignCtaClick' AND COALESCE(e.cta_source, '') IN ('course_row', 'course_row_v2') THEN 1 ELSE 0 END) as course_row_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'LPCourseView' THEN 1 ELSE 0 END) as lp_course_views")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'ViewContent' THEN 1 ELSE 0 END) as view_content")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'LPCheckoutClick' THEN 1 ELSE 0 END) as checkout_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'InitiateCheckout' THEN 1 ELSE 0 END) as initiate_checkout")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'Lead' THEN 1 ELSE 0 END) as leads")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'PurchaseApproved' THEN 1 ELSE 0 END) as purchases")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'PurchaseApproved' THEN COALESCE(e.value, 0) ELSE 0 END) as revenue")
            ->first();

        $toInt = static fn ($value): int => (int) ($value ?? 0);

        return [
            'events_count' => $toInt($row?->events_count),
            'sessions_count' => $toInt($row?->sessions_count),
            'visitors_count' => $toInt($row?->visitors_count),
            'city_views' => $toInt($row?->city_views),
            'hero_clicks' => $toInt($row?->hero_clicks),
            'course_row_clicks' => $toInt($row?->course_row_clicks),
            'lp_course_views' => $toInt($row?->lp_course_views),
            'view_content' => $toInt($row?->view_content),
            'checkout_clicks' => $toInt($row?->checkout_clicks),
            'initiate_checkout' => $toInt($row?->initiate_checkout),
            'leads' => $toInt($row?->leads),
            'purchases' => $toInt($row?->purchases),
            'revenue' => $row && $row->revenue !== null ? (float) $row->revenue : 0.0,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function sourceBreakdown(Builder $query): Collection
    {
        $sourceExpr = "COALESCE(NULLIF(s.utm_source, ''), '(direto)')";
        $mediumExpr = "COALESCE(NULLIF(s.utm_medium, ''), '(direto)')";
        $campaignExpr = "COALESCE(NULLIF(s.utm_campaign, ''), '(sem campanha)')";

        return $query
            ->selectRaw("$sourceExpr as source_label")
            ->selectRaw("$mediumExpr as medium_label")
            ->selectRaw("$campaignExpr as campaign_label")
            ->selectRaw('COUNT(*) as events_count')
            ->selectRaw('COUNT(DISTINCT e.tracking_session_id) as sessions_count')
            ->selectRaw("SUM(CASE WHEN e.event_name = 'CityCampaignCtaClick' AND COALESCE(e.cta_source, '') IN ('course_row', 'course_row_v2') THEN 1 ELSE 0 END) as course_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'LPCheckoutClick' THEN 1 ELSE 0 END) as checkout_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'Lead' THEN 1 ELSE 0 END) as leads")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'PurchaseApproved' THEN 1 ELSE 0 END) as purchases")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'PurchaseApproved' THEN COALESCE(e.value, 0) ELSE 0 END) as revenue")
            ->groupByRaw("$sourceExpr, $mediumExpr, $campaignExpr")
            ->orderByDesc('sessions_count')
            ->orderByDesc('course_clicks')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function cityBreakdown(Builder $query): Collection
    {
        $citySlugExpr = "COALESCE(NULLIF(e.city_slug, ''), NULLIF(s.city_slug, ''), '(sem cidade)')";
        $cityNameExpr = "COALESCE(NULLIF(e.city_name, ''), NULLIF(s.city_name, ''), '(sem cidade)')";

        return $query
            ->selectRaw("$citySlugExpr as city_slug_label")
            ->selectRaw("$cityNameExpr as city_name_label")
            ->selectRaw('COUNT(*) as events_count')
            ->selectRaw('COUNT(DISTINCT e.tracking_session_id) as sessions_count')
            ->selectRaw("SUM(CASE WHEN e.event_name = 'CityCampaignView' THEN 1 ELSE 0 END) as city_views")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'CityCampaignCtaClick' AND COALESCE(e.cta_source, '') IN ('course_row', 'course_row_v2') THEN 1 ELSE 0 END) as course_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'LPCheckoutClick' THEN 1 ELSE 0 END) as checkout_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'PurchaseApproved' THEN 1 ELSE 0 END) as purchases")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'PurchaseApproved' THEN COALESCE(e.value, 0) ELSE 0 END) as revenue")
            ->groupByRaw("$citySlugExpr, $cityNameExpr")
            ->orderByDesc('sessions_count')
            ->orderByDesc('course_clicks')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function courseBreakdown(Builder $query): Collection
    {
        return $query
            ->where(function (Builder $courseQuery): void {
                $courseQuery->whereNotNull('e.course_id')
                    ->orWhereNotNull('e.course_slug');
            })
            ->selectRaw('e.course_id')
            ->selectRaw("COALESCE(NULLIF(e.course_slug, ''), '(sem slug)') as course_slug_label")
            ->selectRaw('COUNT(*) as events_count')
            ->selectRaw("SUM(CASE WHEN e.event_name = 'CityCampaignCtaClick' AND COALESCE(e.cta_source, '') IN ('course_row', 'course_row_v2') THEN 1 ELSE 0 END) as city_course_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'LPCourseView' THEN 1 ELSE 0 END) as lp_views")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'ViewContent' THEN 1 ELSE 0 END) as view_content")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'LPCheckoutClick' THEN 1 ELSE 0 END) as checkout_clicks")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'InitiateCheckout' THEN 1 ELSE 0 END) as initiate_checkout")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'PurchaseApproved' THEN 1 ELSE 0 END) as purchases")
            ->selectRaw("SUM(CASE WHEN e.event_name = 'PurchaseApproved' THEN COALESCE(e.value, 0) ELSE 0 END) as revenue")
            ->groupBy('e.course_id', 'e.course_slug')
            ->orderByDesc('purchases')
            ->orderByDesc('revenue')
            ->orderByDesc('checkout_clicks')
            ->orderByDesc('city_course_clicks')
            ->orderByDesc('lp_views')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function funnelBreakdown(Builder $query): Collection
    {
        $counts = $query
            ->select('e.event_name')
            ->selectRaw('COUNT(*) as total')
            ->whereIn('e.event_name', [
                'CityCampaignView',
                'CityCampaignCtaClick',
                'LPCourseView',
                'ViewContent',
                'LPCheckoutClick',
                'InitiateCheckout',
                'PurchaseApproved',
                'Lead',
            ])
            ->groupBy('e.event_name')
            ->pluck('total', 'event_name');

        $rows = collect([
            ['label' => 'Visualização da página cidade', 'event' => 'CityCampaignView'],
            ['label' => 'Clique em CTA da cidade', 'event' => 'CityCampaignCtaClick'],
            ['label' => 'LP do curso visualizada', 'event' => 'LPCourseView'],
            ['label' => 'ViewContent (Meta)', 'event' => 'ViewContent'],
            ['label' => 'Clique em checkout', 'event' => 'LPCheckoutClick'],
            ['label' => 'InitiateCheckout (Meta)', 'event' => 'InitiateCheckout'],
            ['label' => 'Compra aprovada (Webhook)', 'event' => 'PurchaseApproved'],
            ['label' => 'Lead', 'event' => 'Lead'],
        ])->map(function (array $row) use ($counts): array {
            $row['total'] = (int) ($counts[$row['event']] ?? 0);

            return $row;
        });

        $base = max(1, (int) ($rows->first()['total'] ?? 0));

        return $rows->map(function (array $row) use ($base): array {
            $row['rate'] = round(($row['total'] / $base) * 100, 1);

            return $row;
        });
    }

    /**
     * @return Collection<int, object>
     */
    private function recentEvents(Builder $query): Collection
    {
        return $query
            ->select([
                'e.occurred_at',
                'e.event_name',
                'e.page_type',
                'e.city_slug',
                'e.course_slug',
                'e.cta_source',
                'e.value',
                'e.currency',
                's.utm_source',
                's.utm_medium',
                's.utm_campaign',
            ])
            ->orderByDesc('e.occurred_at')
            ->limit(80)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function attributionModelBreakdown(): Collection
    {
        $query = $this->baseAttributionsQuery();

        return $query
            ->selectRaw("COALESCE(NULLIF(a.attribution_model, ''), '(sem modelo)') as attribution_model")
            ->selectRaw('COUNT(*) as conversions_count')
            ->selectRaw('COUNT(DISTINCT a.transaction_code) as transactions_count')
            ->selectRaw('COUNT(DISTINCT a.kavoo_id) as kavoo_count')
            ->selectRaw('SUM(COALESCE(a.amount, 0)) as revenue')
            ->groupBy('a.attribution_model')
            ->orderByDesc('conversions_count')
            ->orderBy('a.attribution_model')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function recentAttributions(): Collection
    {
        $query = $this->baseAttributionsQuery();

        return $query
            ->select([
                'a.occurred_at',
                'a.attribution_model',
                'a.transaction_code',
                'a.item_product_id',
                'a.source',
                'a.medium',
                'a.campaign',
                'a.city_slug',
                'a.city_name',
                'a.amount',
                'a.currency',
                'a.course_id',
                'a.session_uuid',
                'a.visitor_uuid',
            ])
            ->selectRaw("COALESCE(NULLIF(c.slug, ''), '') as course_slug")
            ->orderByDesc('a.occurred_at')
            ->orderByDesc('a.id')
            ->limit(80)
            ->get();
    }

    /**
     * @return array<string, Collection<int, string>>
     */
    private function filterOptions(): array
    {
        return [
            'utmSources' => DB::table('tracking_sessions')
                ->whereNotNull('utm_source')
                ->where('utm_source', '!=', '')
                ->distinct()
                ->orderBy('utm_source')
                ->limit(100)
                ->pluck('utm_source'),
            'utmCampaigns' => DB::table('tracking_sessions')
                ->whereNotNull('utm_campaign')
                ->where('utm_campaign', '!=', '')
                ->distinct()
                ->orderBy('utm_campaign')
                ->limit(100)
                ->pluck('utm_campaign'),
            'citySlugs' => DB::table('tracking_sessions')
                ->whereNotNull('city_slug')
                ->where('city_slug', '!=', '')
                ->distinct()
                ->orderBy('city_slug')
                ->limit(100)
                ->pluck('city_slug'),
            'pageTypes' => DB::table('tracking_events')
                ->whereNotNull('page_type')
                ->where('page_type', '!=', '')
                ->distinct()
                ->orderBy('page_type')
                ->limit(100)
                ->pluck('page_type'),
            'eventNames' => DB::table('tracking_events')
                ->whereNotNull('event_name')
                ->where('event_name', '!=', '')
                ->distinct()
                ->orderBy('event_name')
                ->limit(200)
                ->pluck('event_name'),
        ];
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
