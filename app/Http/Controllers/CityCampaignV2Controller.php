<?php

namespace App\Http\Controllers;

use App\Support\CityCampaignPageDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;

class CityCampaignV2Controller extends Controller
{
    public function __construct(
        private readonly CityCampaignPageDataBuilder $pageDataBuilder
    ) {
    }

    public function __invoke(Request $request, string $cidade): Response
    {
        $payload = $this->pageDataBuilder->build($request, $cidade, 'v2');

        $response = response()->view('cities.campaign-v2', [
            ...$payload['view_data'],
            'cityTrackingPageType' => 'city_campaign_catalog_v2',
        ]);

        if ($payload['should_queue_cookie']) {
            $response->withCookie(Cookie::make(
                $payload['countdown_cookie_name'],
                $payload['countdown_cookie_value'],
                $payload['countdown_cookie_minutes']
            ));
        }

        return $response;
    }
}
