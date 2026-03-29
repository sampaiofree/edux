<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentAccessStatus;
use App\Enums\PaymentEntitlementState;
use App\Enums\PaymentInternalAction;
use App\Enums\PaymentProcessingStatus;
use App\Enums\UserRole;
use App\Mail\CourseEnrollmentNotification;
use App\Mail\WelcomePaymentUser;
use App\Models\Course;
use App\Models\CourseWebhookId;
use App\Models\Enrollment;
use App\Models\PaymentEntitlement;
use App\Models\PaymentEvent;
use App\Models\PaymentFieldMapping;
use App\Models\PaymentWebhookLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;
use App\Support\Payments\PaymentWebhookProcessor;

class PaymentWebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_register_webhook_creates_user_entitlement_enrollment_and_tracking(): void
    {
        [$link, $course, $courseWebhookId] = $this->buildWebhookContext();

        $response = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'student1@example.com',
            $courseWebhookId,
            'Aluno Um'
        ));

        $event = PaymentEvent::query()->firstOrFail();
        $user = User::query()->where('email', 'student1@example.com')->firstOrFail();

        $this->assertWebhookResponse($response, 200, 'processed', 'approved', 'approve');
        $response->assertJsonPath('event_id', $event->id);
        $response->assertJsonPath('details.buyer_email', 'student1@example.com');
        $response->assertJsonPath('details.course_reference', $courseWebhookId);
        $response->assertJsonPath('details.course_id', $course->id);
        $response->assertJsonPath('details.user_id', $user->id);
        $response->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_CREATED);

        $this->assertSame(PaymentProcessingStatus::PROCESSED, $event->processing_status);
        $this->assertSame(PaymentInternalAction::APPROVE, $event->internal_action);
        $this->assertSame('student1@example.com', $event->buyer_email);
        $this->assertSame($courseWebhookId, $event->external_product_id);

        $this->assertSame('Aluno Um', $user->name);
        $this->assertSame('5511999999999', $user->whatsapp);
        $this->assertTrue(Hash::check(PaymentWebhookProcessor::INITIAL_PASSWORD, $user->getAuthPassword()));
        $this->assertDatabaseHas('payment_entitlements', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payment_webhook_link_id' => $link->id,
            'external_tx_id' => '',
            'external_product_id' => $courseWebhookId,
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
        Mail::assertSent(WelcomePaymentUser::class, function (WelcomePaymentUser $mail) use ($user): bool {
            return $mail->user->is($user)
                && $mail->plainPassword === PaymentWebhookProcessor::INITIAL_PASSWORD;
        });
        Mail::assertNotSent(CourseEnrollmentNotification::class);
    }

    public function test_register_webhook_accepts_get_query_payload(): void
    {
        [$link, $course, $courseWebhookId] = $this->buildWebhookContext();

        $payload = $this->webhookPayload(
            'student-get@example.com',
            $courseWebhookId,
            'Aluno Via Get',
            '5511955554444'
        );

        $response = $this->get('/api/webhooks/in/'.$link->endpoint_uuid.'?'.http_build_query($payload, '', '&', PHP_QUERY_RFC3986));

        $user = User::query()->where('email', 'student-get@example.com')->firstOrFail();
        $event = PaymentEvent::query()->firstOrFail();

        $this->assertWebhookResponse($response, 200, 'processed', 'approved', 'approve');
        $response->assertJsonPath('event_id', $event->id);
        $response->assertJsonPath('details.buyer_email', 'student-get@example.com');
        $response->assertJsonPath('details.course_reference', $courseWebhookId);
        $response->assertJsonPath('details.course_id', $course->id);
        $response->assertJsonPath('details.user_id', $user->id);
        $response->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_CREATED);

        $this->assertSame('Aluno Via Get', $user->name);
        $this->assertSame('5511955554444', $user->whatsapp);
        $this->assertDatabaseHas('payment_entitlements', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payment_webhook_link_id' => $link->id,
            'external_tx_id' => '',
            'external_product_id' => $courseWebhookId,
            'state' => PaymentEntitlementState::ACTIVE->value,
        ]);
        Mail::assertSent(WelcomePaymentUser::class, 1);
        Mail::assertNotSent(CourseEnrollmentNotification::class);
    }

    public function test_get_webhook_hmac_uses_query_string_as_signature_payload(): void
    {
        [$link, $course, $courseWebhookId] = $this->buildWebhookContext();

        $link->forceFill([
            'security_mode' => 'hmac_sha256',
            'signature_header' => 'X-Signature',
            'secret' => 'segredo-teste',
        ])->save();

        $payload = $this->webhookPayload(
            'student-get-hmac@example.com',
            $courseWebhookId,
            'Aluno Get Hmac',
            '5511966665555'
        );

        $query = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $query, 'segredo-teste');

        $response = $this
            ->withHeaders(['X-Signature' => $signature])
            ->get('/api/webhooks/in/'.$link->endpoint_uuid.'?'.$query);

        $user = User::query()->where('email', 'student-get-hmac@example.com')->firstOrFail();
        $event = PaymentEvent::query()->firstOrFail();

        $this->assertWebhookResponse($response, 200, 'processed', 'approved', 'approve');
        $response->assertJsonPath('event_id', $event->id);
        $response->assertJsonPath('details.user_id', $user->id);
        $response->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_CREATED);

        $this->assertSame('Aluno Get Hmac', $user->name);
        $this->assertSame('5511966665555', $user->whatsapp);
        $this->assertDatabaseHas('payment_entitlements', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payment_webhook_link_id' => $link->id,
            'external_tx_id' => '',
            'external_product_id' => $courseWebhookId,
            'state' => PaymentEntitlementState::ACTIVE->value,
        ]);
        Mail::assertSent(WelcomePaymentUser::class, 1);
        Mail::assertNotSent(CourseEnrollmentNotification::class);
    }

    public function test_register_with_unmapped_course_sets_pending(): void
    {
        [$link] = $this->buildWebhookContext(withCourseWebhookId: false);

        $response = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'pending@example.com',
            'CURSO-INEXISTENTE',
            'Aluno Pendente'
        ));

        $event = PaymentEvent::query()->firstOrFail();

        $this->assertWebhookResponse($response, 422, 'pending', 'course_unmapped', 'approve');
        $response->assertJsonPath('event_id', $event->id);
        $response->assertJsonPath('details.buyer_email', 'pending@example.com');
        $response->assertJsonPath('details.course_reference', 'CURSO-INEXISTENTE');
        $response->assertJsonPath('details.course_id', null);
        $response->assertJsonPath('details.user_id', null);
        $response->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_NONE);

        $this->assertSame(PaymentProcessingStatus::PENDING, $event->processing_status);
        $this->assertSame('course_unmapped', $event->processing_reason);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payment_entitlements', 0);
    }

    public function test_duplicate_payload_is_idempotent_by_hash(): void
    {
        [$link, , $courseWebhookId] = $this->buildWebhookContext();

        $payload = $this->webhookPayload('dupe@example.com', $courseWebhookId, 'Aluno Duplicado');

        $firstResponse = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $payload);
        $firstEvent = PaymentEvent::query()->firstOrFail();

        $this->assertWebhookResponse($firstResponse, 200, 'processed', 'approved', 'approve');
        $firstResponse->assertJsonPath('event_id', $firstEvent->id);

        $secondResponse = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $payload);

        $secondResponse->assertOk();
        $secondResponse->assertJsonPath('status', 'duplicate');
        $secondResponse->assertJsonPath('reason', 'duplicate_payload');
        $secondResponse->assertJsonPath('message', $this->webhookMessageForReason('duplicate_payload'));
        $secondResponse->assertJsonPath('event_id', $firstEvent->id);
        $secondResponse->assertJsonPath('action', 'approve');
        $secondResponse->assertJsonPath('details.buyer_email', 'dupe@example.com');
        $secondResponse->assertJsonPath('details.course_reference', $courseWebhookId);
        $secondResponse->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_NONE);

        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_block_webhook_blocks_course_access_and_creates_tracking(): void
    {
        [$course, $courseWebhookId] = $this->makeCourseWithWebhookId();
        $registerLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_REGISTER);
        $blockLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_BLOCK);

        $this->attachFieldMappings($registerLink);
        $this->attachFieldMappings($blockLink);

        $email = 'student-block@example.com';

        $this->postJson('/api/webhooks/in/'.$registerLink->endpoint_uuid, $this->webhookPayload(
            $email,
            $courseWebhookId,
            'Aluno Bloqueado'
        ))->assertOk();

        $blockResponse = $this->postJson('/api/webhooks/in/'.$blockLink->endpoint_uuid, $this->webhookPayload(
            $email,
            $courseWebhookId,
            'Aluno Bloqueado'
        ));

        $user = User::query()->where('email', $email)->firstOrFail();
        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();
        $blockEvent = PaymentEvent::query()
            ->where('payment_webhook_link_id', $blockLink->id)
            ->firstOrFail();

        $this->assertWebhookResponse($blockResponse, 200, 'processed', 'revoked', 'revoke');
        $blockResponse->assertJsonPath('event_id', $blockEvent->id);
        $blockResponse->assertJsonPath('details.buyer_email', $email);
        $blockResponse->assertJsonPath('details.course_reference', $courseWebhookId);
        $blockResponse->assertJsonPath('details.course_id', $course->id);
        $blockResponse->assertJsonPath('details.user_id', $user->id);
        $blockResponse->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_BLOCKED);

        $this->assertSame(EnrollmentAccessStatus::BLOCKED, $enrollment->access_status);
        $this->assertDatabaseHas('payment_entitlements', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'external_product_id' => $courseWebhookId,
            'state' => PaymentEntitlementState::REVOKED->value,
        ]);
        $this->assertDatabaseHas('tracking_events', [
            'event_name' => 'PurchaseRevoked',
            'event_source' => 'payment_webhook',
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame(PaymentInternalAction::REVOKE, $blockEvent->internal_action);
        $this->assertSame(PaymentProcessingStatus::PROCESSED, $blockEvent->processing_status);
    }

    public function test_block_webhook_creates_user_and_blocks_course_without_prior_purchase(): void
    {
        [$course, $courseWebhookId] = $this->makeCourseWithWebhookId();
        $blockLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_BLOCK);

        $this->attachFieldMappings($blockLink);

        $email = 'student-block-only@example.com';

        $this->postJson('/api/webhooks/in/'.$blockLink->endpoint_uuid, $this->webhookPayload(
            $email,
            $courseWebhookId,
            'Aluno So Bloqueio',
            '5511944443333'
        ))->assertOk();

        $user = User::query()->where('email', $email)->firstOrFail();
        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->assertSame('Aluno So Bloqueio', $user->name);
        $this->assertSame('5511944443333', $user->whatsapp);
        $this->assertSame(EnrollmentAccessStatus::BLOCKED, $enrollment->access_status);
        $this->assertDatabaseHas('payment_entitlements', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payment_webhook_link_id' => $blockLink->id,
            'external_tx_id' => '',
            'external_product_id' => $courseWebhookId,
            'state' => PaymentEntitlementState::REVOKED->value,
        ]);
    }

    public function test_manual_override_keeps_access_active_even_after_new_block(): void
    {
        [$course, $courseWebhookId] = $this->makeCourseWithWebhookId();
        $registerLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_REGISTER);
        $blockLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_BLOCK);

        $this->attachFieldMappings($registerLink);
        $this->attachFieldMappings($blockLink);

        $email = 'student-override@example.com';

        $this->postJson('/api/webhooks/in/'.$registerLink->endpoint_uuid, $this->webhookPayload(
            $email,
            $courseWebhookId,
            'Aluno Override'
        ))->assertOk();

        $this->postJson('/api/webhooks/in/'.$blockLink->endpoint_uuid, $this->webhookPayload(
            $email,
            $courseWebhookId,
            'Aluno Override'
        ))->assertOk();

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

        $this->postJson('/api/webhooks/in/'.$blockLink->endpoint_uuid, $this->webhookPayload(
            $email,
            $courseWebhookId,
            'Aluno Override'
        ))->assertOk();

        $enrollment->refresh();

        $this->assertTrue($enrollment->manual_override);
        $this->assertSame(EnrollmentAccessStatus::ACTIVE, $enrollment->access_status);
    }

    public function test_register_falls_back_to_email_prefix_when_name_missing(): void
    {
        [$link, , $courseWebhookId] = $this->buildWebhookContext();

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'fallback.user@example.com',
            $courseWebhookId,
            null
        ))->assertOk();

        $user = User::query()->where('email', 'fallback.user@example.com')->firstOrFail();

        $this->assertSame('Fallback User', $user->name);
    }

    public function test_register_fills_whatsapp_for_existing_user_when_empty(): void
    {
        [$link, $course, $courseWebhookId] = $this->buildWebhookContext();

        $user = User::factory()->create([
            'system_setting_id' => $course->system_setting_id,
            'email' => 'existing-empty@example.com',
            'whatsapp' => null,
        ]);

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'existing-empty@example.com',
            $courseWebhookId,
            'Aluno Existente',
            '5511977776666'
        ))->assertOk();

        $this->assertSame('5511977776666', $user->fresh()->whatsapp);
        Mail::assertNotSent(WelcomePaymentUser::class);
        Mail::assertSent(CourseEnrollmentNotification::class, function (CourseEnrollmentNotification $mail) use ($user, $course): bool {
            $html = $mail->render();

            return $mail->user->is($user)
                && $mail->course->is($course)
                && str_contains($html, $course->title)
                && str_contains($html, rtrim(config('app.url'), '/').'/login');
        });
    }

    public function test_register_does_not_overwrite_existing_user_whatsapp(): void
    {
        [$link, $course, $courseWebhookId] = $this->buildWebhookContext();

        $user = User::factory()->create([
            'system_setting_id' => $course->system_setting_id,
            'email' => 'existing-phone@example.com',
            'whatsapp' => '5511911111111',
        ]);

        $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'existing-phone@example.com',
            $courseWebhookId,
            'Aluno Existente',
            '5511922222222'
        ))->assertOk();

        $this->assertSame('5511911111111', $user->fresh()->whatsapp);
        Mail::assertSent(CourseEnrollmentNotification::class, 1);
        Mail::assertNotSent(WelcomePaymentUser::class);
    }

    public function test_register_does_not_send_course_email_when_enrollment_already_exists(): void
    {
        [$link, $course, $courseWebhookId] = $this->buildWebhookContext();

        $user = User::factory()->create([
            'system_setting_id' => $course->system_setting_id,
            'email' => 'existing-enrollment@example.com',
        ]);

        Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'progress_percent' => 0,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
            'completed_at' => null,
        ]);

        $response = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'existing-enrollment@example.com',
            $courseWebhookId,
            'Aluno Ja Matriculado'
        ));

        $this->assertWebhookResponse($response, 200, 'processed', 'approved', 'approve');
        $response->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_ALREADY_ACTIVE);

        Mail::assertNothingSent();
    }

    public function test_register_does_not_send_course_email_when_reactivating_existing_enrollment(): void
    {
        [$course, $courseWebhookId] = $this->makeCourseWithWebhookId();
        $registerLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_REGISTER);
        $blockLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_BLOCK);

        $this->attachFieldMappings($registerLink);
        $this->attachFieldMappings($blockLink);

        $user = User::factory()->create([
            'system_setting_id' => $course->system_setting_id,
            'email' => 'reactivate@example.com',
        ]);

        Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'progress_percent' => 0,
            'access_status' => EnrollmentAccessStatus::BLOCKED->value,
            'access_block_reason' => 'revoke',
            'access_blocked_at' => now(),
            'completed_at' => null,
        ]);

        PaymentEntitlement::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payment_webhook_link_id' => $blockLink->id,
            'external_tx_id' => '',
            'external_product_id' => $courseWebhookId,
            'state' => PaymentEntitlementState::REVOKED->value,
            'last_event_at' => now(),
        ]);

        $response = $this->postJson('/api/webhooks/in/'.$registerLink->endpoint_uuid, $this->webhookPayload(
            'reactivate@example.com',
            $courseWebhookId,
            'Aluno Reativado'
        ));

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->assertWebhookResponse($response, 200, 'processed', 'approved', 'approve');
        $response->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_ACTIVATED);

        $this->assertSame(EnrollmentAccessStatus::ACTIVE, $enrollment->access_status);
        Mail::assertNothingSent();
    }

    public function test_block_fills_whatsapp_for_existing_user_when_empty(): void
    {
        [, $courseWebhookId] = $this->makeCourseWithWebhookId();
        $registerLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_REGISTER);
        $blockLink = $this->makeWebhookLink(PaymentWebhookLink::ACTION_BLOCK);

        $this->attachFieldMappings($registerLink);
        $this->attachFieldMappings($blockLink);

        $email = 'student-block-phone@example.com';

        $this->postJson('/api/webhooks/in/'.$registerLink->endpoint_uuid, $this->webhookPayload(
            $email,
            $courseWebhookId,
            'Aluno Bloqueio',
            null
        ))->assertOk();

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->forceFill(['whatsapp' => null])->save();

        $this->postJson('/api/webhooks/in/'.$blockLink->endpoint_uuid, $this->webhookPayload(
            $email,
            $courseWebhookId,
            'Aluno Bloqueio',
            '5511933332222'
        ))->assertOk();

        $this->assertSame('5511933332222', $user->fresh()->whatsapp);
        Mail::assertNotSent(CourseEnrollmentNotification::class);
    }

    public function test_block_without_course_id_is_ignored(): void
    {
        [$link] = $this->buildWebhookContext(actionMode: PaymentWebhookLink::ACTION_BLOCK);

        $response = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'ignored@example.com',
            null,
            'Aluno Ignorado'
        ));

        $event = PaymentEvent::query()->firstOrFail();

        $this->assertWebhookResponse($response, 422, 'ignored', 'course_id_missing', 'revoke');
        $response->assertJsonPath('event_id', $event->id);
        $response->assertJsonPath('details.buyer_email', 'ignored@example.com');
        $response->assertJsonPath('details.course_reference', null);
        $response->assertJsonPath('details.enrollment_result', PaymentWebhookProcessor::ENROLLMENT_NONE);

        $this->assertSame(PaymentProcessingStatus::IGNORED, $event->processing_status);
        $this->assertSame('course_id_missing', $event->processing_reason);
    }

    public function test_webhook_returns_forbidden_when_signature_is_invalid(): void
    {
        [$link] = $this->buildWebhookContext();

        $link->forceFill([
            'security_mode' => 'header_secret',
            'signature_header' => 'X-Webhook-Secret',
            'secret' => 'segredo-correto',
        ])->save();

        $response = $this
            ->withHeaders(['X-Webhook-Secret' => 'segredo-incorreto'])
            ->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
                'blocked@example.com',
                'CURSO-INVALIDO',
                'Aluno Bloqueado'
            ));

        $this->assertWebhookResponse($response, 403, 'error', 'invalid_signature');
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_webhook_returns_conflict_when_link_is_inactive(): void
    {
        [$link] = $this->buildWebhookContext();

        $link->forceFill([
            'is_active' => false,
        ])->save();

        $response = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'inactive@example.com',
            'CURSO-INVALIDO',
            'Aluno Inativo'
        ));

        $this->assertWebhookResponse($response, 409, 'error', 'webhook_inactive');
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_webhook_returns_not_found_when_endpoint_does_not_exist(): void
    {
        $response = $this->postJson('/api/webhooks/in/'.Str::uuid(), $this->webhookPayload(
            'missing@example.com',
            'CURSO-INVALIDO',
            'Aluno Missing'
        ));

        $this->assertWebhookResponse($response, 404, 'error', 'not_found');
    }

    public function test_webhook_returns_bad_request_when_payload_is_empty(): void
    {
        [$link] = $this->buildWebhookContext();

        $response = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, []);

        $this->assertWebhookResponse($response, 400, 'error', 'invalid_payload');
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_webhook_returns_internal_error_when_processor_throws(): void
    {
        [$link] = $this->buildWebhookContext();

        $this->mock(PaymentWebhookProcessor::class, function (MockInterface $mock): void {
            $mock->shouldReceive('process')
                ->once()
                ->andThrow(new \RuntimeException('falha simulada'));
        });

        $response = $this->postJson('/api/webhooks/in/'.$link->endpoint_uuid, $this->webhookPayload(
            'broken@example.com',
            'CURSO-QUEBRADO',
            'Aluno Quebrado'
        ));

        $event = PaymentEvent::query()->firstOrFail();

        $this->assertWebhookResponse($response, 500, 'failed', 'processor_exception');
        $response->assertJsonPath('event_id', $event->id);
        $this->assertSame(PaymentProcessingStatus::FAILED, $event->processing_status);
        $this->assertSame('processor_exception', $event->processing_reason);
    }

    public function test_course_enrollment_notification_renders_course_title_and_login_link(): void
    {
        $admin = $this->defaultTenantAdmin();
        $user = User::factory()->create([
            'system_setting_id' => $admin->system_setting_id,
            'email' => 'mail-render@example.com',
        ]);
        [$course] = $this->makeCourseWithWebhookId();

        $mail = new CourseEnrollmentNotification(
            $user,
            $course,
            rtrim(config('app.url'), '/').'/login'
        );

        $html = $mail->render();

        $this->assertStringContainsString($course->title, $html);
        $this->assertStringContainsString(rtrim(config('app.url'), '/').'/login', $html);
    }

    public function test_student_self_enroll_route_is_disabled(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = User::factory()->create([
            'system_setting_id' => $admin->system_setting_id,
            'role' => UserRole::STUDENT->value,
        ]);

        [$course] = $this->makeCourseWithWebhookId();

        $this->actingAs($student)
            ->post('/learning/courses/'.$course->slug.'/enroll')
            ->assertNotFound();
    }

    private function assertWebhookResponse(
        TestResponse $response,
        int $statusCode,
        string $status,
        string $reason,
        ?string $action = null,
    ): void {
        $response->assertStatus($statusCode);
        $response->assertJsonPath('status', $status);
        $response->assertJsonPath('reason', $reason);
        $response->assertJsonPath('message', $this->webhookMessageForReason($reason));
        $response->assertJsonPath('action', $action);
    }

    private function webhookMessageForReason(string $reason): string
    {
        return match ($reason) {
            'approved' => 'Matrícula realizada com sucesso.',
            'revoked' => 'Acesso do aluno bloqueado com sucesso.',
            'course_unmapped' => 'Curso não encontrado para o identificador informado.',
            'course_id_missing' => 'Campo de curso não encontrado no payload do webhook.',
            'buyer_email_missing' => 'Campo de e-mail não encontrado no payload do webhook.',
            'duplicate_payload' => 'Evento duplicado já recebido anteriormente.',
            'invalid_signature' => 'Assinatura do webhook é inválida.',
            'webhook_inactive' => 'Este webhook está inativo.',
            'not_found' => 'Webhook não encontrado.',
            'invalid_payload' => 'Payload do webhook está vazio ou inválido.',
            'processor_exception' => 'Falha interna ao processar o webhook.',
            default => 'Webhook processado.',
        };
    }

    /**
     * @return array{0:PaymentWebhookLink,1:Course,2:string}
     */
    private function buildWebhookContext(
        string $actionMode = PaymentWebhookLink::ACTION_REGISTER,
        bool $withCourseWebhookId = true
    ): array {
        [$course, $courseWebhookId] = $this->makeCourseWithWebhookId();
        $link = $this->makeWebhookLink($actionMode);

        $this->attachFieldMappings($link);

        if (! $withCourseWebhookId) {
            CourseWebhookId::query()->delete();
        }

        return [$link, $course, $courseWebhookId];
    }

    /**
     * @return array{0:Course,1:string}
     */
    private function makeCourseWithWebhookId(): array
    {
        $owner = $this->defaultTenantAdmin();

        $course = Course::create([
            'system_setting_id' => $owner->system_setting_id,
            'owner_id' => $owner->id,
            'title' => 'Auxiliar Administrativo',
            'slug' => 'auxiliar-administrativo-'.Str::lower(Str::random(5)),
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'published',
            'duration_minutes' => 180,
            'published_at' => now(),
        ]);

        $courseWebhookId = 'CURSO-'.Str::upper(Str::random(6));

        $course->courseWebhookIds()->create([
            'webhook_id' => $courseWebhookId,
            'platform' => 'Gateway',
        ]);

        return [$course, $courseWebhookId];
    }

    private function makeWebhookLink(string $actionMode): PaymentWebhookLink
    {
        $admin = $this->defaultTenantAdmin();

        return PaymentWebhookLink::create([
            'system_setting_id' => $admin->system_setting_id,
            'name' => 'Gateway '.Str::upper(Str::random(4)),
            'endpoint_uuid' => (string) Str::uuid(),
            'is_active' => true,
            'action_mode' => $actionMode,
            'created_by' => $admin->id,
        ]);
    }

    private function attachFieldMappings(PaymentWebhookLink $link): void
    {
        $mappings = [
            [PaymentFieldMapping::FIELD_BUYER_NAME, 'customer.name'],
            [PaymentFieldMapping::FIELD_BUYER_EMAIL, 'customer.email'],
            [PaymentFieldMapping::FIELD_COURSE_ID, 'course.id'],
            [PaymentFieldMapping::FIELD_BUYER_WHATSAPP, 'customer.whatsapp'],
        ];

        foreach ($mappings as [$fieldKey, $jsonPath]) {
            PaymentFieldMapping::create([
                'payment_webhook_link_id' => $link->id,
                'field_key' => $fieldKey,
                'json_path' => $jsonPath,
                'is_required' => false,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function webhookPayload(string $email, ?string $courseWebhookId, ?string $name, ?string $whatsapp = '5511999999999'): array
    {
        $payload = [
            'customer' => [
                'email' => $email,
            ],
            'course' => [],
        ];

        if ($name !== null) {
            $payload['customer']['name'] = $name;
        }

        if ($whatsapp !== null) {
            $payload['customer']['whatsapp'] = $whatsapp;
        }

        if ($courseWebhookId !== null) {
            $payload['course']['id'] = $courseWebhookId;
        }

        return $payload;
    }
}
