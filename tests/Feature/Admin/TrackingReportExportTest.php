<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\TrackingAttribution;
use App\Models\TrackingEvent;
use App\Models\TrackingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_can_export_sources_csv(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $session = TrackingSession::create([
            'session_uuid' => 'sess_export_001',
            'visitor_uuid' => 'vis_export_001',
            'started_at' => now()->subMinutes(30),
            'last_seen_at' => now(),
            'landing_url' => 'https://edux.test/cidade/sao-paulo?utm_source=meta&utm_medium=cpc&utm_campaign=campanha-csv',
            'landing_path' => '/cidade/sao-paulo',
            'first_page_type' => 'city_campaign_catalog',
            'utm_source' => 'meta',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'campanha-csv',
            'city_slug' => 'sao-paulo',
            'city_name' => 'Sao Paulo',
        ]);

        TrackingEvent::create([
            'tracking_session_id' => $session->id,
            'event_uuid' => 'evt_export_001',
            'session_uuid' => $session->session_uuid,
            'visitor_uuid' => $session->visitor_uuid,
            'event_name' => 'CityCampaignCtaClick',
            'event_category' => 'cta',
            'event_source' => 'internal',
            'occurred_at' => now()->subMinutes(10),
            'received_at' => now()->subMinutes(10),
            'page_url' => 'https://edux.test/cidade/sao-paulo',
            'page_path' => '/cidade/sao-paulo',
            'page_type' => 'city_campaign_catalog',
            'cta_source' => 'course_row',
            'city_slug' => 'sao-paulo',
            'city_name' => 'Sao Paulo',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tracking.export.sources', [
            'dateFrom' => now()->subDay()->toDateString(),
            'dateTo' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $this->stripUtf8Bom($response->streamedContent());

        $this->assertStringContainsString("source;medium;campaign;", $csv);
        $this->assertStringContainsString('meta;cpc;campanha-csv', $csv);
        $this->assertStringContainsString(';1;1;1;0;0;0;0.00', $csv);
    }

    public function test_admin_can_export_attributions_csv(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $session = TrackingSession::create([
            'session_uuid' => 'sess_attr_001',
            'visitor_uuid' => 'vis_attr_001',
            'started_at' => now()->subHour(),
            'last_seen_at' => now(),
            'landing_url' => 'https://edux.test/cidade/rio-de-janeiro?utm_source=google&utm_campaign=camp-attr',
            'landing_path' => '/cidade/rio-de-janeiro',
            'first_page_type' => 'city_campaign_catalog',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'camp-attr',
            'city_slug' => 'rio-de-janeiro',
            'city_name' => 'Rio de Janeiro',
        ]);

        TrackingAttribution::create([
            'tracking_session_id' => $session->id,
            'transaction_code' => 'TRX-CSV-001',
            'item_product_id' => 12345,
            'attribution_model' => 'last_touch',
            'session_uuid' => $session->session_uuid,
            'visitor_uuid' => $session->visitor_uuid,
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => 'camp-attr',
            'city_slug' => 'rio-de-janeiro',
            'city_name' => 'Rio de Janeiro',
            'amount' => 27.00,
            'currency' => 'BRL',
            'occurred_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tracking.export.attributions', [
            'dateFrom' => now()->subDay()->toDateString(),
            'dateTo' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $this->stripUtf8Bom($response->streamedContent());

        $this->assertStringContainsString('occurred_at;attribution_model;transaction_code;', $csv);
        $this->assertStringContainsString('last_touch;TRX-CSV-001;12345', $csv);
        $this->assertStringContainsString(';google;cpc;camp-attr;rio-de-janeiro;"Rio de Janeiro";', $csv);
        $this->assertStringContainsString(';27.00;BRL;', $csv);
    }

    public function test_student_cannot_export_tracking_csvs(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::STUDENT->value,
        ]);

        $this->actingAs($student)
            ->get(route('admin.tracking.export.sources'))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('admin.tracking.export.attributions'))
            ->assertForbidden();
    }

    private function stripUtf8Bom(string $content): string
    {
        return str_starts_with($content, "\xEF\xBB\xBF")
            ? substr($content, 3)
            : $content;
    }
}
