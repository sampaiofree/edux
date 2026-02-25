<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\TrackingEvent;
use App\Models\TrackingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingReportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_can_view_tracking_report_with_seeded_data(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $session = TrackingSession::create([
            'session_uuid' => 'sess_test_001',
            'visitor_uuid' => 'vis_test_001',
            'started_at' => now()->subMinutes(10),
            'last_seen_at' => now(),
            'landing_url' => 'https://edux.test/cidade/sao-paulo?utm_source=meta&utm_medium=cpc&utm_campaign=campanha-x',
            'landing_path' => '/cidade/sao-paulo',
            'first_page_type' => 'city_campaign_catalog',
            'utm_source' => 'meta',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'campanha-x',
            'city_slug' => 'sao-paulo',
            'city_name' => 'Sao Paulo',
        ]);

        TrackingEvent::create([
            'tracking_session_id' => $session->id,
            'event_uuid' => 'evt_report_001',
            'session_uuid' => $session->session_uuid,
            'visitor_uuid' => $session->visitor_uuid,
            'event_name' => 'CityCampaignCtaClick',
            'event_category' => 'cta',
            'event_source' => 'meta_custom',
            'occurred_at' => now()->subMinutes(5),
            'received_at' => now()->subMinutes(5),
            'page_url' => 'https://edux.test/cidade/sao-paulo',
            'page_path' => '/cidade/sao-paulo',
            'page_type' => 'city_campaign_catalog',
            'course_slug' => 'informatica-basica',
            'city_slug' => 'sao-paulo',
            'city_name' => 'Sao Paulo',
            'cta_source' => 'course_row',
            'properties' => [
                'course_title' => 'Informática Básica',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tracking.index'));

        $response->assertOk();
        $response->assertSee('Tracking Interno (First-Party)');
        $response->assertSee('Origens de trafego');
        $response->assertSee('meta');
        $response->assertSee('campanha-x');
        $response->assertSee('sao-paulo');
        $response->assertSee('informatica-basica');
    }

    public function test_student_cannot_access_admin_tracking_report(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::STUDENT->value,
        ]);

        $this->actingAs($student)
            ->get(route('admin.tracking.index'))
            ->assertForbidden();
    }

    public function test_report_summary_counts_v2_cta_sources_in_hero_and_course_clicks(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $session = TrackingSession::create([
            'session_uuid' => 'sess_test_v2_001',
            'visitor_uuid' => 'vis_test_v2_001',
            'started_at' => now()->subMinutes(20),
            'last_seen_at' => now(),
            'landing_url' => 'https://edux.test/cidade-2/sao-paulo?utm_source=meta&utm_medium=cpc&utm_campaign=campanha-v2',
            'landing_path' => '/cidade-2/sao-paulo',
            'first_page_type' => 'city_campaign_catalog_v2',
            'utm_source' => 'meta',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'campanha-v2',
            'city_slug' => 'sao-paulo',
            'city_name' => 'Sao Paulo',
        ]);

        TrackingEvent::create([
            'tracking_session_id' => $session->id,
            'event_uuid' => 'evt_report_v2_hero',
            'session_uuid' => $session->session_uuid,
            'visitor_uuid' => $session->visitor_uuid,
            'event_name' => 'CityCampaignCtaClick',
            'event_category' => 'cta',
            'event_source' => 'meta_custom',
            'occurred_at' => now()->subMinutes(8),
            'received_at' => now()->subMinutes(8),
            'page_url' => 'https://edux.test/cidade-2/sao-paulo',
            'page_path' => '/cidade-2/sao-paulo',
            'page_type' => 'city_campaign_catalog_v2',
            'city_slug' => 'sao-paulo',
            'city_name' => 'Sao Paulo',
            'cta_source' => 'hero_primary_v2',
        ]);

        TrackingEvent::create([
            'tracking_session_id' => $session->id,
            'event_uuid' => 'evt_report_v2_course',
            'session_uuid' => $session->session_uuid,
            'visitor_uuid' => $session->visitor_uuid,
            'event_name' => 'CityCampaignCtaClick',
            'event_category' => 'cta',
            'event_source' => 'meta_custom',
            'occurred_at' => now()->subMinutes(7),
            'received_at' => now()->subMinutes(7),
            'page_url' => 'https://edux.test/cidade-2/sao-paulo',
            'page_path' => '/cidade-2/sao-paulo',
            'page_type' => 'city_campaign_catalog_v2',
            'course_slug' => 'informatica-basica',
            'city_slug' => 'sao-paulo',
            'city_name' => 'Sao Paulo',
            'cta_source' => 'course_row_v2',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tracking.index', [
            'pageType' => 'city_campaign_catalog_v2',
            'eventName' => 'CityCampaignCtaClick',
        ]));

        $response->assertOk();
        $response->assertSee('campanha-v2');
        $response->assertSee('city_campaign_catalog_v2');

        $html = $response->getContent();
        $this->assertSame('1', $this->extractMetricCardValue($html, 'Cliques no hero'));
        $this->assertSame('1', $this->extractMetricCardValue($html, 'Cliques em curso'));
    }

    private function extractMetricCardValue(string $html, string $label): string
    {
        $escapedLabel = preg_quote($label, '/');
        $pattern = "/{$escapedLabel}<\\/p>\\s*<p[^>]*>\\s*([0-9\\.,]+)\\s*<\\/p>/u";

        preg_match($pattern, $html, $matches);

        return $matches[1] ?? '';
    }
}
