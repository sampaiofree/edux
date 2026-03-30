<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentInternalAction;
use App\Enums\PaymentProcessingStatus;
use App\Models\PaymentEvent;
use App\Models\PaymentProcessingLog;
use App\Models\PaymentWebhookLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookEventsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_webhook_event_and_its_logs(): void
    {
        $admin = $this->defaultTenantAdmin();
        $this->actingAs($admin);

        $link = PaymentWebhookLink::create([
            'system_setting_id' => $admin->system_setting_id,
            'name' => 'Webhook Delete Event',
            'endpoint_uuid' => 'webhook-delete-event',
            'is_active' => true,
            'action_mode' => PaymentWebhookLink::ACTION_REGISTER,
            'created_by' => $admin->id,
        ]);

        $event = PaymentEvent::create([
            'payment_webhook_link_id' => $link->id,
            'payload_hash' => hash('sha256', 'event-delete-1'),
            'raw_payload' => ['email' => 'delete-event@example.com'],
            'raw_headers' => ['x-test' => '1'],
            'external_event_code' => 'approved',
            'internal_action' => PaymentInternalAction::APPROVE->value,
            'buyer_email' => 'delete-event@example.com',
            'processing_status' => PaymentProcessingStatus::PROCESSED->value,
            'received_at' => now(),
        ]);

        $log = PaymentProcessingLog::create([
            'payment_event_id' => $event->id,
            'step' => 'delete-test',
            'level' => 'info',
            'message' => 'Evento criado para teste de exclusao.',
        ]);

        $response = $this->delete(route('admin.webhooks.events.destroy', [$link, $event]));

        $response->assertRedirect(route('admin.webhooks.events.index', $link));
        $response->assertSessionHas('status', 'Evento removido.');

        $this->assertDatabaseMissing('payment_events', [
            'id' => $event->id,
        ]);
        $this->assertDatabaseMissing('payment_processing_logs', [
            'id' => $log->id,
        ]);
    }
}
