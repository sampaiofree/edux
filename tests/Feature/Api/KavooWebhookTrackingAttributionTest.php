<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\TrackingAttribution;
use App\Models\TrackingEvent;
use App\Models\TrackingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KavooWebhookTrackingAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_purchase_approved_and_attribution_from_webhook_tracking(): void
    {
        $buyer = User::factory()->create([
            'role' => UserRole::STUDENT->value,
            'email' => 'buyer@example.com',
        ]);

        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $course = Course::create([
            'owner_id' => $owner->id,
            'title' => 'Informática Básica',
            'slug' => 'informatica-basica',
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'published',
            'duration_minutes' => 240,
            'published_at' => now(),
            'kavoo_id' => 123456,
        ]);

        $session = TrackingSession::create([
            'session_uuid' => 'sess_kavoo_test_001',
            'visitor_uuid' => 'vis_kavoo_test_001',
            'user_id' => null,
            'started_at' => now()->subHour(),
            'last_seen_at' => now()->subMinutes(5),
            'landing_url' => 'https://edux.test/cidade/sao-paulo?utm_source=meta&utm_medium=cpc&utm_campaign=campanha-conv',
            'landing_path' => '/cidade/sao-paulo',
            'first_page_type' => 'city_campaign_catalog',
            'referrer' => 'https://l.facebook.com/',
            'referrer_host' => 'l.facebook.com',
            'utm_source' => 'meta',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'campanha-conv',
            'city_slug' => 'sao-paulo',
            'city_name' => 'Sao Paulo',
        ]);

        $payload = [
            'customer' => [
                'email' => $buyer->email,
                'name' => 'Comprador Teste',
                'phone' => '11999999999',
            ],
            'status' => [
                'code' => 'SALE_APPROVED',
            ],
            'transaction' => [
                'code' => 'TX-APPROVED-001',
                'amount' => '27.00',
                'currency' => 'BRL',
                'approved_at' => now()->toIso8601String(),
            ],
            'items' => [[
                'product_id' => 123456,
                'product_name' => 'Informatica Basica',
                'amount' => '27.00',
                'currency' => 'BRL',
            ]],
            'tracking' => [
                'query' => 'utm_source=meta&utm_medium=cpc&utm_campaign=campanha-conv&edux_sid='.$session->session_uuid.'&edux_vid='.$session->visitor_uuid.'&edux_city_slug=sao-paulo&edux_city_name=Sao+Paulo',
            ],
        ];

        $response = $this->postJson('/api/kavoo/webhook', $payload);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('tracking_events', [
            'event_name' => 'PurchaseApproved',
            'event_source' => 'kavoo_webhook',
            'tracking_session_id' => $session->id,
            'course_id' => $course->id,
            'course_slug' => $course->slug,
        ]);

        $event = TrackingEvent::where('event_name', 'PurchaseApproved')->firstOrFail();
        $this->assertSame('27.00', (string) $event->value);
        $this->assertSame('BRL', $event->currency);
        $this->assertSame('TX-APPROVED-001', $event->properties['transaction_code'] ?? null);

        $this->assertSame(3, TrackingAttribution::count());
        $this->assertDatabaseHas('tracking_attributions', [
            'kavoo_id' => 1,
            'attribution_model' => 'first_touch',
            'tracking_session_id' => $session->id,
            'source' => 'meta',
            'campaign' => 'campanha-conv',
        ]);
        $this->assertDatabaseHas('tracking_attributions', [
            'kavoo_id' => 1,
            'attribution_model' => 'last_touch',
            'tracking_session_id' => $session->id,
        ]);
        $this->assertDatabaseHas('tracking_attributions', [
            'kavoo_id' => 1,
            'attribution_model' => 'webhook_tracking',
            'source' => 'meta',
            'campaign' => 'campanha-conv',
        ]);
    }
}
