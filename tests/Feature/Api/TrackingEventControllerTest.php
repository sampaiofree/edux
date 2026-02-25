<?php

namespace Tests\Feature\Api;

use App\Models\TrackingEvent;
use App\Models\TrackingSession;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingEventControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_stores_tracking_session_and_event_with_origin_data(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-25 12:00:00', 'UTC'));

        $response = $this->postJson('/api/tracking/events', [
            'events' => [[
                'event_uuid' => 'evt_test_city_click_001',
                'event_name' => 'CityCampaignCtaClick',
                'event_source' => 'meta_custom',
                'event_category' => 'cta',
                'occurred_at_ms' => CarbonImmutable::now('UTC')->getTimestampMs(),
                'page_url' => 'https://edux.test/cidade/sao-paulo?utm_source=meta&utm_medium=cpc&utm_campaign=campanha-1&fbclid=abc123',
                'page_path' => '/cidade/sao-paulo',
                'page_type' => 'city_campaign_catalog',
                'referrer' => 'https://l.facebook.com/',
                'city_slug' => 'sao-paulo',
                'city_name' => 'Sao Paulo',
                'cta_source' => 'hero_primary',
                'value' => 27,
                'currency' => 'BRL',
                'properties' => [
                    'courses_count' => 2,
                    'qp_utm_source' => 'meta',
                ],
            ]],
        ]);

        $response->assertStatus(202);
        $response->assertCookie(config('tracking.visitor_cookie', 'edux_vid'));
        $response->assertCookie(config('tracking.session_cookie', 'edux_sid'));

        $this->assertDatabaseCount('tracking_sessions', 1);
        $this->assertDatabaseCount('tracking_events', 1);

        $session = TrackingSession::query()->firstOrFail();
        $event = TrackingEvent::query()->firstOrFail();

        $this->assertSame('meta', $session->utm_source);
        $this->assertSame('cpc', $session->utm_medium);
        $this->assertSame('campanha-1', $session->utm_campaign);
        $this->assertSame('sao-paulo', $session->city_slug);
        $this->assertSame('city_campaign_catalog', $session->first_page_type);

        $this->assertSame($session->id, $event->tracking_session_id);
        $this->assertSame('CityCampaignCtaClick', $event->event_name);
        $this->assertSame('meta_custom', $event->event_source);
        $this->assertSame('city_campaign_catalog', $event->page_type);
        $this->assertSame('sao-paulo', $event->city_slug);
        $this->assertSame('hero_primary', $event->cta_source);
        $this->assertSame('27.00', (string) $event->value);
    }

    public function test_reuses_session_cookie_within_idle_window(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-25 12:00:00', 'UTC'));

        $first = $this->postJson('/api/tracking/events', [
            'events' => [[
                'event_uuid' => 'evt_first_001',
                'event_name' => 'CityCampaignView',
                'page_url' => 'https://edux.test/cidade/recife?utm_source=google',
                'page_path' => '/cidade/recife',
                'page_type' => 'city_campaign_catalog',
                'city_slug' => 'recife',
                'city_name' => 'Recife',
                'properties' => [],
            ]],
        ]);

        $first->assertStatus(202);

        $session = TrackingSession::query()->firstOrFail();
        $visitorCookieName = config('tracking.visitor_cookie', 'edux_vid');
        $sessionCookieName = config('tracking.session_cookie', 'edux_sid');

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-25 12:10:00', 'UTC'));

        $second = $this->call(
            'POST',
            '/api/tracking/events',
            [],
            [
                $visitorCookieName => $session->visitor_uuid,
                $sessionCookieName => $session->session_uuid,
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'events' => [[
                    'event_uuid' => 'evt_second_001',
                    'event_name' => 'CityCampaignCtaClick',
                    'page_url' => 'https://edux.test/cidade/recife',
                    'page_path' => '/cidade/recife',
                    'page_type' => 'city_campaign_catalog',
                    'city_slug' => 'recife',
                    'city_name' => 'Recife',
                    'cta_source' => 'course_row',
                    'properties' => [],
                ]],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $second->assertStatus(202);

        $this->assertDatabaseCount('tracking_sessions', 1);
        $this->assertDatabaseCount('tracking_events', 2);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}
