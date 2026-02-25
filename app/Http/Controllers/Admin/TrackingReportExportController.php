<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrackingReportExportController extends Controller
{
    public function sources(Request $request): StreamedResponse
    {
        $query = DB::table('tracking_events as e')
            ->leftJoin('tracking_sessions as s', 's.id', '=', 'e.tracking_session_id');

        $this->applyEventFilters($query, $request);

        $sourceExpr = "COALESCE(NULLIF(s.utm_source, ''), '(direto)')";
        $mediumExpr = "COALESCE(NULLIF(s.utm_medium, ''), '(direto)')";
        $campaignExpr = "COALESCE(NULLIF(s.utm_campaign, ''), '(sem campanha)')";

        $rows = $query
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
            ->cursor();

        return $this->streamCsv(
            'tracking-origens',
            [
                'source',
                'medium',
                'campaign',
                'sessions_count',
                'events_count',
                'course_clicks',
                'checkout_clicks',
                'leads',
                'purchases',
                'revenue',
            ],
            function ($handle) use ($rows): void {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        (string) $row->source_label,
                        (string) $row->medium_label,
                        (string) $row->campaign_label,
                        (int) ($row->sessions_count ?? 0),
                        (int) ($row->events_count ?? 0),
                        (int) ($row->course_clicks ?? 0),
                        (int) ($row->checkout_clicks ?? 0),
                        (int) ($row->leads ?? 0),
                        (int) ($row->purchases ?? 0),
                        $this->decimalCsv($row->revenue ?? 0),
                    ], ';');
                }
            }
        );
    }

    public function attributions(Request $request): StreamedResponse
    {
        $query = DB::table('tracking_attributions as a')
            ->leftJoin('tracking_sessions as s', 's.id', '=', 'a.tracking_session_id')
            ->leftJoin('courses as c', 'c.id', '=', 'a.course_id');

        $this->applyAttributionFilters($query, $request);

        $rows = $query
            ->select([
                'a.id',
                'a.occurred_at',
                'a.attribution_model',
                'a.transaction_code',
                'a.item_product_id',
                'a.kavoo_id',
                'a.source',
                'a.medium',
                'a.campaign',
                'a.city_slug',
                'a.city_name',
                'a.course_id',
                'a.amount',
                'a.currency',
                'a.session_uuid',
                'a.visitor_uuid',
            ])
            ->selectRaw("COALESCE(NULLIF(c.slug, ''), '') as course_slug")
            ->orderByDesc('a.occurred_at')
            ->orderByDesc('a.id')
            ->cursor();

        return $this->streamCsv(
            'tracking-atribuicoes',
            [
                'occurred_at',
                'attribution_model',
                'transaction_code',
                'item_product_id',
                'kavoo_id',
                'source',
                'medium',
                'campaign',
                'city_slug',
                'city_name',
                'course_id',
                'course_slug',
                'amount',
                'currency',
                'session_uuid',
                'visitor_uuid',
            ],
            function ($handle) use ($rows): void {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->occurred_at ? Carbon::parse($row->occurred_at)->format('Y-m-d H:i:s') : '',
                        (string) ($row->attribution_model ?? ''),
                        (string) ($row->transaction_code ?? ''),
                        $row->item_product_id !== null ? (string) $row->item_product_id : '',
                        $row->kavoo_id !== null ? (string) $row->kavoo_id : '',
                        (string) ($row->source ?? ''),
                        (string) ($row->medium ?? ''),
                        (string) ($row->campaign ?? ''),
                        (string) ($row->city_slug ?? ''),
                        (string) ($row->city_name ?? ''),
                        $row->course_id !== null ? (string) $row->course_id : '',
                        (string) ($row->course_slug ?? ''),
                        $row->amount !== null ? $this->decimalCsv($row->amount) : '',
                        (string) ($row->currency ?? ''),
                        (string) ($row->session_uuid ?? ''),
                        (string) ($row->visitor_uuid ?? ''),
                    ], ';');
                }
            }
        );
    }

    private function applyEventFilters(Builder $query, Request $request): void
    {
        $from = $this->parseDate((string) $request->query('dateFrom', ''))?->startOfDay();
        $to = $this->parseDate((string) $request->query('dateTo', ''))?->endOfDay();

        if ($from) {
            $query->where('e.occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('e.occurred_at', '<=', $to);
        }

        $utmSource = trim((string) $request->query('utmSource', ''));
        if ($utmSource !== '') {
            $query->where('s.utm_source', $utmSource);
        }

        $utmCampaign = trim((string) $request->query('utmCampaign', ''));
        if ($utmCampaign !== '') {
            $query->where('s.utm_campaign', $utmCampaign);
        }

        $pageType = trim((string) $request->query('pageType', ''));
        if ($pageType !== '') {
            $query->where('e.page_type', $pageType);
        }

        $eventName = trim((string) $request->query('eventName', ''));
        if ($eventName !== '') {
            $query->where('e.event_name', $eventName);
        }

        $citySlug = trim((string) $request->query('citySlug', ''));
        if ($citySlug !== '') {
            $query->where(function (Builder $cityQuery) use ($citySlug): void {
                $cityQuery
                    ->where('e.city_slug', $citySlug)
                    ->orWhere(function (Builder $fallbackQuery) use ($citySlug): void {
                        $fallbackQuery->whereNull('e.city_slug')
                            ->where('s.city_slug', $citySlug);
                    });
            });
        }
    }

    private function applyAttributionFilters(Builder $query, Request $request): void
    {
        $from = $this->parseDate((string) $request->query('dateFrom', ''))?->startOfDay();
        $to = $this->parseDate((string) $request->query('dateTo', ''))?->endOfDay();

        if ($from) {
            $query->where('a.occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('a.occurred_at', '<=', $to);
        }

        $utmSource = trim((string) $request->query('utmSource', ''));
        if ($utmSource !== '') {
            $query->where(function (Builder $sourceQuery) use ($utmSource): void {
                $sourceQuery->where('a.source', $utmSource)
                    ->orWhere(function (Builder $fallbackQuery) use ($utmSource): void {
                        $fallbackQuery->whereNull('a.source')
                            ->where('s.utm_source', $utmSource);
                    });
            });
        }

        $utmCampaign = trim((string) $request->query('utmCampaign', ''));
        if ($utmCampaign !== '') {
            $query->where(function (Builder $campaignQuery) use ($utmCampaign): void {
                $campaignQuery->where('a.campaign', $utmCampaign)
                    ->orWhere(function (Builder $fallbackQuery) use ($utmCampaign): void {
                        $fallbackQuery->whereNull('a.campaign')
                            ->where('s.utm_campaign', $utmCampaign);
                    });
            });
        }

        $citySlug = trim((string) $request->query('citySlug', ''));
        if ($citySlug !== '') {
            $query->where(function (Builder $cityQuery) use ($citySlug): void {
                $cityQuery->where('a.city_slug', $citySlug)
                    ->orWhere(function (Builder $fallbackQuery) use ($citySlug): void {
                        $fallbackQuery->whereNull('a.city_slug')
                            ->where('s.city_slug', $citySlug);
                    });
            });
        }
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

    private function decimalCsv(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * @param  array<int, string>  $header
     * @param  \Closure(resource): void  $writer
     */
    private function streamCsv(string $prefix, array $header, \Closure $writer): StreamedResponse
    {
        $filename = sprintf('%s-%s.csv', $prefix, now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($header, $writer): void {
            $handle = fopen('php://output', 'wb');

            if (! is_resource($handle)) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $header, ';');
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
