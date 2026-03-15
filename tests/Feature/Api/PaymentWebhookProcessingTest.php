<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentAccessStatus;
use App\Enums\PaymentEntitlementState;
use App\Enums\PaymentInternalAction;
use App\Enums\PaymentProcessingStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PaymentEntitlement;
use App\Models\PaymentEvent;
use App\Models\PaymentEventMapping;
use App\Models\PaymentFieldMapping;
use App\Models\PaymentProductMapping;
use App\Models\PaymentWebhookLink;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_event_creates_user_entitlement_enrollment_and_tracking(): void
    {
        [$link, $course] = $this->buildBasicWebhookContext();

        $payload = $this->approvedPayload('student1@example.com', 'PROD-001', 'TX-001');

        $response = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $payload);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $event = PaymentEvent::query()->firstOrFail();
        $this->assertSame(PaymentProcessingStatus::PROCESSED, $event->processing_status);
        $this->assertSame('student1@example.com', $event->buyer_email);

        $user = User::query()->where('email', 'student1@example.com')->firstOrFail();

        $this->assertDatabaseHas('payment_entitlements', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'external_tx_id' => 'TX-001',
            'external_product_id' => 'PROD-001',
            'state' => PaymentEntitlementState::ACTIVE->value,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);

        $this->assertDatabaseHas('tracking_events', [
            'event_name' => 'PurchaseApproved',
            'event_source' => 'payment_webhook',
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_approve_with_unmapped_product_sets_pending(): void
    {
        [$link] = $this->buildBasicWebhookContext(withProductMapping: false);

        $payload = $this->approvedPayload('pending@example.com', 'UNMAPPED-999', 'TX-999');

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $payload)
            ->assertOk();

        $event = PaymentEvent::query()->firstOrFail();

        $this->assertSame(PaymentProcessingStatus::PENDING, $event->processing_status);
        $this->assertSame('product_unmapped', $event->processing_reason);

        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payment_entitlements', 0);
    }

    public function test_duplicate_payload_is_idempotent_by_hash(): void
    {
        [$link] = $this->buildBasicWebhookContext();

        $payload = $this->approvedPayload('dupe@example.com', 'PROD-001', 'TX-DUPE-001');

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $payload)->assertOk();
        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $payload)->assertOk();

        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_revoke_event_blocks_course_access_and_creates_tracking(): void
    {
        [$link, $course] = $this->buildBasicWebhookContext(withRevokeMapping: true);

        $email = 'student-revoke@example.com';

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->approvedPayload($email, 'PROD-001', 'TX-REVOKE-001'))
            ->assertOk();

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->revokedPayload($email, 'PROD-001', 'TX-REVOKE-001'))
            ->assertOk();

        $user = User::query()->where('email', $email)->firstOrFail();
        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->assertSame(EnrollmentAccessStatus::BLOCKED, $enrollment->access_status);

        $entitlement = PaymentEntitlement::query()->firstOrFail();
        $this->assertSame(PaymentEntitlementState::REVOKED, $entitlement->state);

        $this->assertDatabaseHas('tracking_events', [
            'event_name' => 'PurchaseRevoked',
            'event_source' => 'payment_webhook',
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_manual_override_keeps_access_active_even_after_new_revoke(): void
    {
        [$link, $course] = $this->buildBasicWebhookContext(withRevokeMapping: true);

        $email = 'student-override@example.com';

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->approvedPayload($email, 'PROD-001', 'TX-MANUAL-001'))
            ->assertOk();

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->revokedPayload($email, 'PROD-001', 'TX-MANUAL-001', 'seq_1'))
            ->assertOk();

        $user = User::query()->where('email', $email)->firstOrFail();

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $enrollment->forceFill([
            'manual_override' => true,
            'access_status' => EnrollmentAccessStatus::ACTIVE,
            'access_block_reason' => null,
            'access_blocked_at' => null,
        ])->save();

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->revokedPayload($email, 'PROD-001', 'TX-MANUAL-001', 'seq_2'))
            ->assertOk();

        $enrollment->refresh();

        $this->assertTrue($enrollment->manual_override);
        $this->assertSame(EnrollmentAccessStatus::ACTIVE, $enrollment->access_status);
    }

    public function test_student_self_enroll_route_is_disabled(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::STUDENT->value,
        ]);

        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $course = Course::create([
            'owner_id' => $owner->id,
            'title' => 'Curso Bloqueado',
            'slug' => 'curso-bloqueado',
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'published',
            'duration_minutes' => 120,
            'published_at' => now(),
        ]);

        $this->actingAs($student)
            ->post('/learning/courses/'.$course->slug.'/enroll')
            ->assertNotFound();
    }

    /**
     * @return array{0:PaymentWebhookLink,1:Course}
     */
    private function buildBasicWebhookContext(bool $withProductMapping = true, bool $withRevokeMapping = false): array
    {
        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $course = Course::create([
            'owner_id' => $owner->id,
            'title' => 'Auxiliar Administrativo',
            'slug' => 'auxiliar-administrativo',
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'published',
            'duration_minutes' => 180,
            'published_at' => now(),
        ]);

        $link = PaymentWebhookLink::create([
            'name' => 'Gateway Custom 1',
            'endpoint_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'is_active' => true,
        ]);

        $mappings = [
            [PaymentFieldMapping::FIELD_BUYER_EMAIL, 'customer.email'],
            [PaymentFieldMapping::FIELD_EVENT_CODE, 'status.code'],
            [PaymentFieldMapping::FIELD_EXTERNAL_TX_ID, 'transaction.code'],
            [PaymentFieldMapping::FIELD_AMOUNT, 'transaction.amount'],
            [PaymentFieldMapping::FIELD_CURRENCY, 'transaction.currency'],
            [PaymentFieldMapping::FIELD_OCCURRED_AT, 'transaction.approved_at'],
            [PaymentFieldMapping::FIELD_ITEMS, 'items'],
            [PaymentFieldMapping::FIELD_ITEM_PRODUCT_ID, 'product_id'],
        ];

        foreach ($mappings as [$fieldKey, $jsonPath]) {
            PaymentFieldMapping::create([
                'payment_webhook_link_id' => $link->id,
                'field_key' => $fieldKey,
                'json_path' => $jsonPath,
                'is_required' => false,
            ]);
        }

        PaymentEventMapping::create([
            'payment_webhook_link_id' => $link->id,
            'external_event_code' => 'approved',
            'internal_action' => PaymentInternalAction::APPROVE,
        ]);

        if ($withRevokeMapping) {
            PaymentEventMapping::create([
                'payment_webhook_link_id' => $link->id,
                'external_event_code' => 'refunded',
                'internal_action' => PaymentInternalAction::REVOKE,
            ]);
        }

        if ($withProductMapping) {
            PaymentProductMapping::create([
                'payment_webhook_link_id' => $link->id,
                'external_product_id' => 'PROD-001',
                'course_id' => $course->id,
                'is_active' => true,
            ]);
        }

        return [$link, $course];
    }

    /**
     * @return array<string, mixed>
     */
    private function approvedPayload(string $email, string $productId, string $tx): array
    {
        return [
            'customer' => [
                'email' => $email,
            ],
            'status' => [
                'code' => 'approved',
            ],
            'transaction' => [
                'code' => $tx,
                'amount' => '89.90',
                'currency' => 'BRL',
                'approved_at' => now()->toIso8601String(),
            ],
            'items' => [
                [
                    'product_id' => $productId,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function revokedPayload(string $email, string $productId, string $tx, string $sequence = 'seq_0'): array
    {
        return [
            'customer' => [
                'email' => $email,
            ],
            'status' => [
                'code' => 'refunded',
            ],
            'transaction' => [
                'code' => $tx,
                'amount' => '89.90',
                'currency' => 'BRL',
                'approved_at' => now()->toIso8601String(),
            ],
            'items' => [
                [
                    'product_id' => $productId,
                ],
            ],
            'meta' => [
                'sequence' => $sequence,
            ],
        ];
    }
}
