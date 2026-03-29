<?php

namespace App\Support\Payments;

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
use App\Models\PaymentProcessingLog;
use App\Models\PaymentWebhookLink;
use App\Models\SystemSetting;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Support\Mail\TenantMailManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentWebhookProcessor
{
    public const INITIAL_PASSWORD = 'mudar123';

    public const ENROLLMENT_CREATED = 'enrollment_created';

    public const ENROLLMENT_ACTIVATED = 'enrollment_activated';

    public const ENROLLMENT_ALREADY_ACTIVE = 'enrollment_already_active';

    public const ENROLLMENT_BLOCKED = 'enrollment_blocked';

    public const ENROLLMENT_ALREADY_BLOCKED = 'enrollment_already_blocked';

    public const ENROLLMENT_NONE = 'none';

    public function __construct(
        private readonly JsonPathExtractor $extractor,
        private readonly TenantMailManager $tenantMailManager,
    ) {}

    /**
     * @return array{
     *     processing_status:PaymentProcessingStatus,
     *     processing_reason:string,
     *     action:?PaymentInternalAction,
     *     buyer_email:?string,
     *     course_reference:?string,
     *     course_id:?int,
     *     user_id:?int,
     *     enrollment_result:string
     * }
     */
    public function process(PaymentEvent $event, bool $force = false): array
    {
        $action = null;
        $buyerEmail = null;
        $courseReference = null;

        try {
            $event->refresh();

            $currentStatus = $event->processing_status instanceof PaymentProcessingStatus
                ? $event->processing_status
                : PaymentProcessingStatus::tryFrom((string) $event->processing_status);

            if (! $force && $currentStatus && ! in_array($currentStatus, [PaymentProcessingStatus::QUEUED, PaymentProcessingStatus::FAILED], true)) {
                return $this->resultFromEvent($event);
            }

            $event->loadMissing([
                'webhookLink.fieldMappings',
            ]);

            $link = $event->webhookLink;
            if (! $link) {
                $this->markEvent($event, PaymentProcessingStatus::FAILED, 'webhook_link_not_found');

                return $this->resultFromEvent($event);
            }

            $payload = is_array($event->raw_payload) ? $event->raw_payload : [];
            $fieldMappings = $this->supportedFieldMappings($link->fieldMappings);
            $extracted = $this->extractMappedFields($payload, $fieldMappings);
            $internalAction = $this->resolveInternalAction($link);
            $action = $internalAction;
            $courseReference = trim((string) ($extracted['course_id'] ?? ''));

            $event->forceFill([
                'external_event_code' => null,
                'internal_action' => $internalAction,
                'buyer_email' => $extracted['buyer_email'],
                'external_tx_id' => null,
                'external_product_id' => $courseReference !== '' ? $courseReference : null,
                'amount' => null,
                'currency' => null,
                'occurred_at' => null,
            ])->save();

            $this->logStep($event, 'extract', 'info', 'Campos mapeados extraidos.', [
                'name' => $extracted['buyer_name'],
                'email' => $extracted['buyer_email'],
                'course_id' => $extracted['course_id'],
                'whatsapp' => $extracted['buyer_whatsapp'],
                'resolved_action' => $internalAction->value,
            ]);

            $buyerEmail = strtolower(trim((string) ($extracted['buyer_email'] ?? '')));
            if ($buyerEmail === '') {
                $this->markEvent($event, PaymentProcessingStatus::IGNORED, 'buyer_email_missing');

                return $this->buildResult(
                    $event,
                    $action,
                    $extracted['buyer_email'],
                    $courseReference,
                    null,
                    null,
                    self::ENROLLMENT_NONE,
                );
            }

            $outcome = $internalAction === PaymentInternalAction::APPROVE
                ? $this->applyApprove($event, $link, $buyerEmail, $courseReference, $extracted, 0)
                : $this->applyRevoke($event, $link, $buyerEmail, $courseReference, $extracted, 0);

            $outcomes = [[
                'index' => 0,
                'product_id' => $courseReference,
                ...$outcome,
            ]];

            $this->finalizeEventFromOutcomes($event, $outcomes);
            $this->logStep($event, 'finalize', 'info', 'Processamento finalizado.', [
                'outcomes' => $outcomes,
                'status' => $event->processing_status instanceof PaymentProcessingStatus
                    ? $event->processing_status->value
                    : $event->processing_status,
                'reason' => $event->processing_reason,
            ]);

            return $this->buildResult(
                $event,
                $action,
                $extracted['buyer_email'],
                $courseReference,
                $outcome['course_id'] ?? null,
                $outcome['user_id'] ?? null,
                $outcome['enrollment_result'] ?? self::ENROLLMENT_NONE,
            );
        } catch (\Throwable $exception) {
            Log::error('Erro ao processar evento de webhook de pagamento', [
                'payment_event_id' => $event->id,
                'error' => $exception->getMessage(),
            ]);

            $this->markEvent($event, PaymentProcessingStatus::FAILED, 'processor_exception');
            $this->logStep($event, 'exception', 'error', 'Falha no processamento.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->buildResult(
                $event,
                $action,
                $buyerEmail,
                $courseReference,
                null,
                null,
                self::ENROLLMENT_NONE,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(PaymentWebhookLink $link, array $payload): array
    {
        $link->loadMissing(['fieldMappings']);

        $fieldMappings = $this->supportedFieldMappings($link->fieldMappings);
        $base = $this->extractMappedFields($payload, $fieldMappings);
        $courseReference = trim((string) ($base['course_id'] ?? ''));
        $course = $this->resolveCourseByWebhookId($courseReference, $link->system_setting_id);
        $action = $this->resolveInternalAction($link);

        return [
            'base_fields' => [
                'name' => $base['buyer_name'],
                'email' => $base['buyer_email'],
                'course_id' => $base['course_id'],
                'whatsapp' => $base['buyer_whatsapp'],
            ],
            'resolved_action' => $action->value,
            'resolved_course' => [
                'course_id' => $course?->id,
                'course_title' => $course?->title,
            ],
            'notes' => [
                'action_mode' => $link->action_mode,
                'course_resolved' => $course !== null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  \Illuminate\Support\Collection<string, PaymentFieldMapping>  $fieldMappings
     * @return array{buyer_name:?string,buyer_email:?string,course_id:?string,buyer_whatsapp:?string}
     */
    private function extractMappedFields(array $payload, Collection $fieldMappings): array
    {
        $buyerName = $this->toString($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_BUYER_NAME));
        $buyerEmail = $this->toString($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_BUYER_EMAIL));
        $courseId = $this->toString($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_COURSE_ID));
        $buyerWhatsapp = $this->toString($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_BUYER_WHATSAPP));

        return [
            'buyer_name' => $buyerName,
            'buyer_email' => $buyerEmail,
            'course_id' => $courseId,
            'buyer_whatsapp' => $buyerWhatsapp,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PaymentFieldMapping>  $fieldMappings
     * @return \Illuminate\Support\Collection<string, PaymentFieldMapping>
     */
    private function supportedFieldMappings(Collection $fieldMappings): Collection
    {
        return $fieldMappings
            ->whereIn('field_key', array_keys(PaymentFieldMapping::configurableFields()))
            ->keyBy('field_key');
    }

    private function resolveInternalAction(PaymentWebhookLink $link): PaymentInternalAction
    {
        return ($link->action_mode ?? PaymentWebhookLink::ACTION_REGISTER) === PaymentWebhookLink::ACTION_BLOCK
            ? PaymentInternalAction::REVOKE
            : PaymentInternalAction::APPROVE;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  \Illuminate\Support\Collection<string, PaymentFieldMapping>  $fieldMappings
     */
    private function valueFromMapping(array $payload, Collection $fieldMappings, string $fieldKey): mixed
    {
        $path = $fieldMappings->get($fieldKey)?->json_path;

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return $this->extractor->get($payload, $path);
    }

    /**
     * @param  array{buyer_name:?string,buyer_email:?string,course_id:?string,buyer_whatsapp:?string}  $extracted
     * @return array{
     *     status:PaymentProcessingStatus,
     *     reason:string,
     *     course_id?:int,
     *     user_id?:int,
     *     enrollment_result:string
     * }
     */
    private function applyApprove(PaymentEvent $event, PaymentWebhookLink $link, string $buyerEmail, string $courseReference, array $extracted, int $itemIndex): array
    {
        if ($courseReference === '') {
            return [
                'status' => PaymentProcessingStatus::PENDING,
                'reason' => 'course_id_missing',
                'enrollment_result' => self::ENROLLMENT_NONE,
            ];
        }

        $course = $this->resolveCourseByWebhookId($courseReference, $link->system_setting_id);
        if (! $course) {
            return [
                'status' => PaymentProcessingStatus::PENDING,
                'reason' => 'course_unmapped',
                'enrollment_result' => self::ENROLLMENT_NONE,
            ];
        }

        $userResult = $this->resolveUser(
            $buyerEmail,
            $extracted['buyer_name'] ?? null,
            $extracted['buyer_whatsapp'] ?? null,
            $link->system_setting_id
        );
        $user = $userResult['user'];

        $entitlement = PaymentEntitlement::firstOrNew([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payment_webhook_link_id' => $link->id,
            'external_tx_id' => '',
            'external_product_id' => $courseReference,
        ]);

        $entitlement->state = PaymentEntitlementState::ACTIVE;
        $entitlement->last_event_at = now();
        $entitlement->last_payment_event_id = $event->id;
        $entitlement->save();

        $enrollmentResult = $this->syncEnrollmentAccess($user, $course, 'approve');

        if (! $userResult['was_created'] && $enrollmentResult['was_created']) {
            $this->sendEnrollmentNotification($user, $course);
        }

        $this->recordTrackingEvent($event, $itemIndex, 'PurchaseApproved', $user, $course, $extracted);

        return [
            'status' => PaymentProcessingStatus::PROCESSED,
            'reason' => 'approved',
            'course_id' => $course->id,
            'user_id' => $user->id,
            'enrollment_result' => $enrollmentResult['enrollment_result'],
        ];
    }

    /**
     * @param  array{buyer_name:?string,buyer_email:?string,course_id:?string,buyer_whatsapp:?string}  $extracted
     * @return array{
     *     status:PaymentProcessingStatus,
     *     reason:string,
     *     course_id?:int,
     *     user_id?:int,
     *     enrollment_result:string
     * }
     */
    private function applyRevoke(PaymentEvent $event, PaymentWebhookLink $link, string $buyerEmail, string $courseReference, array $extracted, int $itemIndex): array
    {
        if ($courseReference === '') {
            return [
                'status' => PaymentProcessingStatus::IGNORED,
                'reason' => 'course_id_missing',
                'enrollment_result' => self::ENROLLMENT_NONE,
            ];
        }

        $course = $this->resolveCourseByWebhookId($courseReference, $link->system_setting_id);
        if (! $course) {
            return [
                'status' => PaymentProcessingStatus::IGNORED,
                'reason' => 'course_unmapped',
                'enrollment_result' => self::ENROLLMENT_NONE,
            ];
        }

        $userResult = $this->resolveUser(
            $buyerEmail,
            $extracted['buyer_name'] ?? null,
            $extracted['buyer_whatsapp'] ?? null,
            $link->system_setting_id
        );
        $user = $userResult['user'];

        $entitlements = PaymentEntitlement::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('external_product_id', $courseReference)
            ->get();

        if ($entitlements->isEmpty()) {
            $entitlement = PaymentEntitlement::firstOrNew([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'payment_webhook_link_id' => $link->id,
                'external_tx_id' => '',
                'external_product_id' => $courseReference,
            ]);

            $entitlement->state = PaymentEntitlementState::REVOKED;
            $entitlement->last_event_at = now();
            $entitlement->last_payment_event_id = $event->id;
            $entitlement->save();

            $enrollmentResult = $this->syncEnrollmentAccess($user, $course, 'revoke');
            $this->recordTrackingEvent($event, $itemIndex, 'PurchaseRevoked', $user, $course, $extracted);

            return [
                'status' => PaymentProcessingStatus::PROCESSED,
                'reason' => 'revoked',
                'course_id' => $course->id,
                'user_id' => $user->id,
                'enrollment_result' => $enrollmentResult['enrollment_result'],
            ];
        }

        foreach ($entitlements as $entitlement) {
            $entitlement->state = PaymentEntitlementState::REVOKED;
            $entitlement->last_event_at = now();
            $entitlement->last_payment_event_id = $event->id;
            $entitlement->save();
        }

        $enrollmentResult = $this->syncEnrollmentAccess($user, $course, 'revoke');
        $this->recordTrackingEvent($event, $itemIndex, 'PurchaseRevoked', $user, $course, $extracted);

        return [
            'status' => PaymentProcessingStatus::PROCESSED,
            'reason' => 'revoked',
            'course_id' => $course->id,
            'user_id' => $user->id,
            'enrollment_result' => $enrollmentResult['enrollment_result'],
        ];
    }

    private function resolveCourseByWebhookId(string $courseReference, ?int $systemSettingId = null): ?Course
    {
        $courseReference = trim($courseReference);
        if ($courseReference === '') {
            return null;
        }

        $matches = CourseWebhookId::query()
            ->with(['course' => function ($query) use ($systemSettingId): void {
                if ($systemSettingId !== null) {
                    $query->where('system_setting_id', $systemSettingId);
                }
            }])
            ->where('webhook_id', $courseReference)
            ->when($systemSettingId !== null, fn ($query) => $query->whereHas('course', fn ($courseQuery) => $courseQuery->where('system_setting_id', $systemSettingId)))
            ->get();

        if ($matches->count() !== 1) {
            return null;
        }

        return $matches->first()?->course;
    }

    /**
     * @return array{user:User,was_created:bool}
     */
    private function resolveUser(string $email, ?string $name = null, ?string $whatsapp = null, ?int $systemSettingId = null): array
    {
        $normalized = strtolower(trim($email));
        $normalizedWhatsapp = trim((string) $whatsapp);
        $normalizedWhatsapp = $normalizedWhatsapp !== '' ? $normalizedWhatsapp : null;

        $existing = User::query()
            ->when($systemSettingId !== null, fn ($query) => $query->where('system_setting_id', $systemSettingId))
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->first();
        if ($existing) {
            $this->syncUserWhatsapp($existing, $normalizedWhatsapp);

            return [
                'user' => $existing,
                'was_created' => false,
            ];
        }

        $temporaryPassword = self::INITIAL_PASSWORD;

        $resolvedName = trim((string) $name);
        if ($resolvedName === '') {
            $resolvedName = Str::before($normalized, '@');
            $resolvedName = trim($resolvedName) !== '' ? Str::title(str_replace(['.', '_', '-'], ' ', $resolvedName)) : $normalized;
        }

        $user = User::create([
            'name' => $resolvedName,
            'display_name' => $resolvedName,
            'email' => $normalized,
            'system_setting_id' => $systemSettingId,
            'whatsapp' => $normalizedWhatsapp,
            'role' => UserRole::STUDENT,
            'password' => $temporaryPassword,
        ]);

        try {
            $this->tenantMailManager->send(
                $this->systemSettingForId($systemSettingId) ?? $user->systemSetting,
                $user->email,
                new WelcomePaymentUser(
                    $user,
                    $temporaryPassword,
                    $this->loginUrlForSystemSettingId($systemSettingId)
                )
            );
        } catch (\Throwable $exception) {
            Log::warning('Falha ao enviar e-mail de boas-vindas de pagamento', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }

        return [
            'user' => $user,
            'was_created' => true,
        ];
    }

    private function syncUserWhatsapp(User $user, ?string $whatsapp): void
    {
        $normalizedWhatsapp = trim((string) $whatsapp);

        if ($normalizedWhatsapp === '' || filled($user->whatsapp)) {
            return;
        }

        $user->forceFill([
            'whatsapp' => $normalizedWhatsapp,
        ])->save();
    }

    /**
     * @return array{enrollment:Enrollment,was_created:bool,enrollment_result:string}
     */
    private function syncEnrollmentAccess(User $user, Course $course, string $reason): array
    {
        $enrollment = Enrollment::query()->firstOrCreate(
            [
                'system_setting_id' => $course->system_setting_id ?: $user->system_setting_id,
                'course_id' => $course->id,
                'user_id' => $user->id,
            ],
            [
                'progress_percent' => 0,
                'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                'completed_at' => null,
            ]
        );
        $wasCreated = $enrollment->wasRecentlyCreated;
        $previousStatus = $wasCreated ? null : $this->enrollmentStatusValue($enrollment->access_status);

        if ($enrollment->manual_override) {
            if ($enrollment->access_status !== EnrollmentAccessStatus::ACTIVE) {
                $enrollment->forceFill([
                    'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                    'access_block_reason' => null,
                    'access_blocked_at' => null,
                ])->save();
            }

            return [
                'enrollment' => $enrollment,
                'was_created' => $wasCreated,
                'enrollment_result' => $this->resolveEnrollmentResult($previousStatus, EnrollmentAccessStatus::ACTIVE->value, $wasCreated),
            ];
        }

        $hasActive = PaymentEntitlement::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('state', PaymentEntitlementState::ACTIVE)
            ->exists();

        $hasRevoked = PaymentEntitlement::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('state', PaymentEntitlementState::REVOKED)
            ->exists();

        if ($hasActive) {
            $enrollment->forceFill([
                'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                'access_block_reason' => null,
                'access_blocked_at' => null,
            ])->save();

            return [
                'enrollment' => $enrollment,
                'was_created' => $wasCreated,
                'enrollment_result' => $this->resolveEnrollmentResult($previousStatus, EnrollmentAccessStatus::ACTIVE->value, $wasCreated),
            ];
        }

        if ($hasRevoked) {
            $enrollment->forceFill([
                'access_status' => EnrollmentAccessStatus::BLOCKED->value,
                'access_block_reason' => $reason,
                'access_blocked_at' => $enrollment->access_blocked_at ?? now(),
            ])->save();
        }

        return [
            'enrollment' => $enrollment,
            'was_created' => $wasCreated,
            'enrollment_result' => $this->resolveEnrollmentResult($previousStatus, $this->enrollmentStatusValue($enrollment->access_status), $wasCreated),
        ];
    }

    private function sendEnrollmentNotification(User $user, Course $course): void
    {
        try {
            $this->tenantMailManager->send(
                $course->systemSetting ?? $user->systemSetting,
                $user->email,
                new CourseEnrollmentNotification(
                    $user,
                    $course,
                    $this->loginUrlForCourse($course, $user)
                )
            );
        } catch (\Throwable $exception) {
            Log::warning('Falha ao enviar e-mail de matricula de pagamento', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function loginUrlForCourse(Course $course, User $user): string
    {
        return $course->systemSetting?->appUrl('/login')
            ?? $user->systemSetting?->appUrl('/login')
            ?? rtrim(config('app.url'), '/').'/login';
    }

    private function loginUrlForSystemSettingId(?int $systemSettingId): string
    {
        if ($systemSetting = $this->systemSettingForId($systemSettingId)) {
            return $systemSetting->appUrl('/login');
        }

        return rtrim(config('app.url'), '/').'/login';
    }

    private function systemSettingForId(?int $systemSettingId): ?SystemSetting
    {
        if (! $systemSettingId) {
            return null;
        }

        return SystemSetting::query()->find($systemSettingId);
    }

    /**
     * @param  array<string, mixed>  $extracted
     */
    private function recordTrackingEvent(
        PaymentEvent $paymentEvent,
        int $itemIndex,
        string $eventName,
        User $user,
        Course $course,
        array $extracted
    ): void {
        $eventUuid = 'payment_event_'.$paymentEvent->id.'_'.$itemIndex.'_'.Str::slug($eventName, '_');

        TrackingEvent::updateOrCreate(
            ['event_uuid' => $eventUuid],
            [
                'tracking_session_id' => null,
                'session_uuid' => null,
                'visitor_uuid' => null,
                'user_id' => $user->id,
                'event_name' => $eventName,
                'event_category' => 'conversion',
                'event_source' => 'payment_webhook',
                'occurred_at' => $extracted['occurred_at'] ?? now(),
                'received_at' => now(),
                'page_url' => null,
                'page_path' => null,
                'page_type' => 'payment_webhook',
                'referrer' => null,
                'course_id' => $course->id,
                'checkout_id' => null,
                'course_slug' => $course->slug,
                'city_slug' => null,
                'city_name' => null,
                'cta_source' => null,
                'value' => $extracted['amount'] ?? null,
                'currency' => $extracted['currency'] ?? 'BRL',
                'properties' => [
                    'payment_event_id' => $paymentEvent->id,
                    'webhook_link_id' => $paymentEvent->payment_webhook_link_id,
                    'transaction_code' => $extracted['external_tx_id'] ?? null,
                    'item_product_id' => $paymentEvent->external_product_id,
                ],
            ]
        );
    }

    /**
     * @param  array<int, array{status:PaymentProcessingStatus,reason:string}>  $outcomes
     */
    private function finalizeEventFromOutcomes(PaymentEvent $event, array $outcomes): void
    {
        if ($outcomes === []) {
            $this->markEvent($event, PaymentProcessingStatus::IGNORED, 'no_item_outcome');

            return;
        }

        $hasPending = collect($outcomes)->contains(fn (array $outcome) => $outcome['status'] === PaymentProcessingStatus::PENDING);
        $hasProcessed = collect($outcomes)->contains(fn (array $outcome) => $outcome['status'] === PaymentProcessingStatus::PROCESSED);

        if ($hasPending) {
            $reason = collect($outcomes)
                ->first(fn (array $outcome) => $outcome['status'] === PaymentProcessingStatus::PENDING)['reason'] ?? 'pending';

            $this->markEvent($event, PaymentProcessingStatus::PENDING, (string) $reason);

            return;
        }

        if ($hasProcessed) {
            if (count($outcomes) === 1) {
                $outcome = $outcomes[0];

                $this->markEvent($event, PaymentProcessingStatus::PROCESSED, (string) $outcome['reason']);

                return;
            }

            $reason = collect($outcomes)->contains(fn (array $outcome) => $outcome['status'] === PaymentProcessingStatus::IGNORED)
                ? 'processed_with_ignores'
                : 'processed';

            $this->markEvent($event, PaymentProcessingStatus::PROCESSED, $reason);

            return;
        }

        $reason = (string) (collect($outcomes)->first()['reason'] ?? 'ignored');
        $this->markEvent($event, PaymentProcessingStatus::IGNORED, $reason);
    }

    private function markEvent(PaymentEvent $event, PaymentProcessingStatus $status, string $reason): void
    {
        $event->forceFill([
            'processing_status' => $status,
            'processing_reason' => $reason,
            'processed_at' => now(),
        ])->save();

        $this->logStep($event, 'status', 'info', 'Status atualizado para '.$status->value.'.', [
            'reason' => $reason,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logStep(PaymentEvent $event, string $step, string $level, string $message, array $context = []): void
    {
        PaymentProcessingLog::create([
            'payment_event_id' => $event->id,
            'step' => $step,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }

    private function toString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text !== '' ? Str::limit($text, 191, '') : null;
    }

    private function toCurrency(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $currency = strtoupper(trim((string) $value));

        return $currency !== '' ? Str::limit($currency, 12, '') : null;
    }

    private function toDate(mixed $value): ?CarbonImmutable
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($text);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toMoney(mixed $value): ?float
    {
        if (is_string($value)) {
            $raw = trim($value);
            if ($raw === '') {
                return null;
            }

            $normalized = preg_replace('/[^0-9,.-]/', '', $raw) ?? $raw;
            $hasComma = str_contains($normalized, ',');
            $hasDot = str_contains($normalized, '.');

            if ($hasComma && $hasDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } elseif ($hasComma) {
                $normalized = str_replace(',', '.', $normalized);
            }

            if (is_numeric($normalized)) {
                return round((float) $normalized, 2);
            }

            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function resolveEnrollmentResult(?string $previousStatus, string $currentStatus, bool $wasCreated): string
    {
        if ($currentStatus === EnrollmentAccessStatus::ACTIVE->value) {
            if ($wasCreated) {
                return self::ENROLLMENT_CREATED;
            }

            if ($previousStatus === EnrollmentAccessStatus::BLOCKED->value) {
                return self::ENROLLMENT_ACTIVATED;
            }

            return self::ENROLLMENT_ALREADY_ACTIVE;
        }

        if ($currentStatus === EnrollmentAccessStatus::BLOCKED->value) {
            if ($previousStatus === EnrollmentAccessStatus::BLOCKED->value) {
                return self::ENROLLMENT_ALREADY_BLOCKED;
            }

            return self::ENROLLMENT_BLOCKED;
        }

        return self::ENROLLMENT_NONE;
    }

    private function enrollmentStatusValue(mixed $status): string
    {
        return $status instanceof EnrollmentAccessStatus
            ? $status->value
            : (string) $status;
    }

    /**
     * @return array{
     *     processing_status:PaymentProcessingStatus,
     *     processing_reason:string,
     *     action:?PaymentInternalAction,
     *     buyer_email:?string,
     *     course_reference:?string,
     *     course_id:?int,
     *     user_id:?int,
     *     enrollment_result:string
     * }
     */
    private function buildResult(
        PaymentEvent $event,
        ?PaymentInternalAction $action,
        ?string $buyerEmail,
        ?string $courseReference,
        ?int $courseId,
        ?int $userId,
        string $enrollmentResult,
    ): array {
        $status = $event->processing_status instanceof PaymentProcessingStatus
            ? $event->processing_status
            : PaymentProcessingStatus::tryFrom((string) $event->processing_status)
                ?? PaymentProcessingStatus::FAILED;

        return [
            'processing_status' => $status,
            'processing_reason' => (string) ($event->processing_reason ?? ''),
            'action' => $action,
            'buyer_email' => $buyerEmail !== null && trim($buyerEmail) !== '' ? $buyerEmail : null,
            'course_reference' => $courseReference !== null && trim($courseReference) !== '' ? $courseReference : null,
            'course_id' => $courseId,
            'user_id' => $userId,
            'enrollment_result' => $enrollmentResult,
        ];
    }

    /**
     * @return array{
     *     processing_status:PaymentProcessingStatus,
     *     processing_reason:string,
     *     action:?PaymentInternalAction,
     *     buyer_email:?string,
     *     course_reference:?string,
     *     course_id:?int,
     *     user_id:?int,
     *     enrollment_result:string
     * }
     */
    private function resultFromEvent(PaymentEvent $event): array
    {
        $status = $event->processing_status instanceof PaymentProcessingStatus
            ? $event->processing_status
            : PaymentProcessingStatus::tryFrom((string) $event->processing_status)
                ?? PaymentProcessingStatus::FAILED;

        $action = $event->internal_action instanceof PaymentInternalAction
            ? $event->internal_action
            : PaymentInternalAction::tryFrom((string) $event->internal_action);

        return [
            'processing_status' => $status,
            'processing_reason' => (string) ($event->processing_reason ?? ''),
            'action' => $action,
            'buyer_email' => filled($event->buyer_email) ? (string) $event->buyer_email : null,
            'course_reference' => filled($event->external_product_id) ? (string) $event->external_product_id : null,
            'course_id' => null,
            'user_id' => null,
            'enrollment_result' => self::ENROLLMENT_NONE,
        ];
    }
}
