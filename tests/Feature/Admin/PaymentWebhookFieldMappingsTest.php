<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\PaymentFieldMapping;
use App\Models\PaymentWebhookLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentWebhookFieldMappingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_edit_page_renders_single_fixed_field_mapping_card(): void
    {
        $admin = $this->defaultTenantAdmin();
        $link = $this->makeWebhookLink($admin);

        $response = $this->actingAs($admin)->get(route('admin.webhooks.edit', $link));

        $response->assertOk();
        $response->assertSee('Mapeamento de campos', false);
        $response->assertSee('Nome', false);
        $response->assertSee('Email', false);
        $response->assertSee('curso_id', false);
        $response->assertSee('WhatsApp', false);
        $response->assertSee('name="field_mappings[buyer_name][json_path]"', false);
        $response->assertSee('name="field_mappings[buyer_email][json_path]"', false);
        $response->assertSee('name="field_mappings[course_id][json_path]"', false);
        $response->assertSee('name="field_mappings[buyer_whatsapp][json_path]"', false);
        $response->assertDontSee('Mapeamento de eventos', false);
        $response->assertDontSee('Mapeamento produto -> curso', false);
    }

    public function test_admin_field_mapping_sync_updates_supported_fields_and_keeps_legacy_rows(): void
    {
        $admin = $this->defaultTenantAdmin();
        $link = $this->makeWebhookLink($admin);

        PaymentFieldMapping::create([
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_NAME,
            'json_path' => 'old.name',
            'is_required' => false,
        ]);
        PaymentFieldMapping::create([
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_EMAIL,
            'json_path' => 'old.email',
            'is_required' => false,
        ]);
        PaymentFieldMapping::create([
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_WHATSAPP,
            'json_path' => 'old.whatsapp',
            'is_required' => false,
        ]);
        PaymentFieldMapping::create([
            'payment_webhook_link_id' => $link->id,
            'field_key' => 'event_code',
            'json_path' => 'legacy.code',
            'is_required' => false,
        ]);

        $payload = [
            'customer' => [
                'name' => 'Aluno Teste',
                'email' => 'aluno@teste.com',
                'whatsapp' => '5511999999999',
            ],
            'course' => [
                'id' => 'CURSO-123',
            ],
        ];

        $response = $this
            ->withSession([$this->simulationPayloadKey($link) => json_encode($payload)])
            ->actingAs($admin)
            ->post(route('admin.webhooks.field-mappings.upsert', $link), [
                'field_mappings' => [
                    'buyer_name' => ['json_path' => 'customer.name'],
                    'buyer_email' => ['json_path' => ''],
                    'course_id' => ['json_path' => 'course.id'],
                    'buyer_whatsapp' => ['json_path' => 'customer.whatsapp'],
                ],
            ]);

        $response->assertRedirect(route('admin.webhooks.edit', $link));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payment_field_mappings', [
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_NAME,
            'json_path' => 'customer.name',
        ]);
        $this->assertDatabaseHas('payment_field_mappings', [
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_COURSE_ID,
            'json_path' => 'course.id',
        ]);
        $this->assertDatabaseHas('payment_field_mappings', [
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_WHATSAPP,
            'json_path' => 'customer.whatsapp',
        ]);
        $this->assertDatabaseMissing('payment_field_mappings', [
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_EMAIL,
        ]);
        $this->assertDatabaseHas('payment_field_mappings', [
            'payment_webhook_link_id' => $link->id,
            'field_key' => 'event_code',
            'json_path' => 'legacy.code',
        ]);
    }

    public function test_simulation_preview_uses_fixed_fields_and_resolves_course(): void
    {
        $admin = $this->defaultTenantAdmin();
        $owner = $admin;
        $course = Course::create([
            'system_setting_id' => $owner->system_setting_id,
            'owner_id' => $owner->id,
            'title' => 'Curso Preview',
            'slug' => 'curso-preview',
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'published',
            'duration_minutes' => 60,
            'published_at' => now(),
        ]);
        $course->courseWebhookIds()->create([
            'webhook_id' => 'CURSO-PREVIEW',
            'platform' => 'Gateway',
        ]);

        $link = $this->makeWebhookLink($admin);

        PaymentFieldMapping::create([
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_NAME,
            'json_path' => 'customer.name',
            'is_required' => false,
        ]);
        PaymentFieldMapping::create([
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_EMAIL,
            'json_path' => 'customer.email',
            'is_required' => false,
        ]);
        PaymentFieldMapping::create([
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_COURSE_ID,
            'json_path' => 'course.id',
            'is_required' => false,
        ]);
        PaymentFieldMapping::create([
            'payment_webhook_link_id' => $link->id,
            'field_key' => PaymentFieldMapping::FIELD_BUYER_WHATSAPP,
            'json_path' => 'customer.whatsapp',
            'is_required' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.webhooks.simulate', $link), [
                'payload_json' => json_encode([
                    'customer' => [
                        'name' => 'Aluno Preview',
                        'email' => 'preview@example.com',
                        'whatsapp' => '5511988887777',
                    ],
                    'course' => [
                        'id' => 'CURSO-PREVIEW',
                    ],
                ], JSON_THROW_ON_ERROR),
            ]);

        $response->assertOk();
        $response->assertSee('Preview da extracao', false);
        $response->assertSee('Aluno Preview', false);
        $response->assertSee('preview@example.com', false);
        $response->assertSee('CURSO-PREVIEW', false);
        $response->assertSee('5511988887777', false);
        $response->assertSee('resolved_action', false);
        $response->assertSee('approve', false);
        $response->assertSee((string) $course->id, false);
        $response->assertSee('Curso Preview', false);
    }

    private function makeWebhookLink(User $admin): PaymentWebhookLink
    {
        return PaymentWebhookLink::create([
            'system_setting_id' => $admin->system_setting_id,
            'name' => 'Webhook '.Str::upper(Str::random(4)),
            'endpoint_uuid' => (string) Str::uuid(),
            'is_active' => true,
            'action_mode' => PaymentWebhookLink::ACTION_REGISTER,
            'created_by' => $admin->id,
        ]);
    }

    private function simulationPayloadKey(PaymentWebhookLink $link): string
    {
        return 'admin.webhooks.simulation_payload.'.$link->id;
    }
}
