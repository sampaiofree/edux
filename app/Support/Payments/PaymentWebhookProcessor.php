<?php

namespace App\Support\Payments;

use App\Enums\EnrollmentAccessStatus;
use App\Enums\PaymentEntitlementState;
use App\Enums\PaymentInternalAction;
use App\Enums\PaymentProcessingStatus;
use App\Enums\UserRole;
use App\Mail\WelcomePaymentUser;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PaymentEntitlement;
use App\Models\PaymentEvent;
use App\Models\PaymentEventMapping;
use App\Models\PaymentFieldMapping;
use App\Models\PaymentProcessingLog;
use App\Models\PaymentProductMapping;
use App\Models\PaymentWebhookLink;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentWebhookProcessor
{
    public function __construct(
        private readonly JsonPathExtractor $extractor,
    ) {
    }

    public function process(PaymentEvent $event, bool $force = false): void
    {
        try {
            $event->refresh();

            $currentStatus = $event->processing_status instanceof PaymentProcessingStatus
                ? $event->processing_status
                : PaymentProcessingStatus::tryFrom((string) $event->processing_status);

            if (! $force && $currentStatus && ! in_array($currentStatus, [PaymentProcessingStatus::QUEUED, PaymentProcessingStatus::FAILED], true)) {
                return;
            }

            $event->loadMissing([
                'webhookLink.fieldMappings',
                'webhookLink.eventMappings',
                'webhookLink.productMappings.course',
            ]);

            $link = $event->webhookLink;
            if (! $link) {
                $this->markEvent($event, PaymentProcessingStatus::FAILED, 'webhook_link_not_found');

                return;
            }

            $payload = is_array($event->raw_payload) ? $event->raw_payload : [];
            $fieldMappings = $link->fieldMappings->keyBy('field_key');

            $extracted = $this->extractBaseFields($payload, $fieldMappings);

            $event->forceFill([
                'external_event_code' => $extracted['event_code'],
                'buyer_email' => $extracted['buyer_email'],
                'external_tx_id' => $extracted['external_tx_id'],
                'amount' => $extracted['amount'],
                'currency' => $extracted['currency'],
                'occurred_at' => $extracted['occurred_at'],
            ])->save();

            $this->logStep($event, 'extract', 'info', 'Campos base extraidos.', $extracted);

            $eventCode = (string) ($extracted['event_code'] ?? '');
            if ($eventCode === '') {
                $this->markEvent($event, PaymentProcessingStatus::IGNORED, 'event_code_missing');

                return;
            }

            $eventMapping = $this->resolveEventMapping($link->eventMappings, $eventCode);
            if (! $eventMapping) {
                $this->markEvent($event, PaymentProcessingStatus::IGNORED, 'event_unmapped');

                return;
            }

            $internalAction = $eventMapping->internal_action instanceof PaymentInternalAction
                ? $eventMapping->internal_action
                : PaymentInternalAction::tryFrom((string) $eventMapping->internal_action);

            if (! $internalAction) {
                $this->markEvent($event, PaymentProcessingStatus::FAILED, 'internal_action_invalid');

                return;
            }

            $event->forceFill([
                'internal_action' => $internalAction,
            ])->save();

            if ($internalAction === PaymentInternalAction::IGNORE) {
                $this->markEvent($event, PaymentProcessingStatus::IGNORED, 'mapped_to_ignore');

                return;
            }

            $buyerEmail = strtolower(trim((string) ($extracted['buyer_email'] ?? '')));
            if ($buyerEmail === '') {
                $this->markEvent($event, PaymentProcessingStatus::IGNORED, 'buyer_email_missing');

                return;
            }

            $items = $this->resolveItems($payload, $fieldMappings);
            if ($items === []) {
                $this->markEvent($event, PaymentProcessingStatus::IGNORED, 'items_missing');

                return;
            }

            $outcomes = [];
            foreach ($items as $index => $itemContext) {
                $productId = $this->resolveProductId((array) $itemContext, $payload, $fieldMappings);

                if ($internalAction === PaymentInternalAction::APPROVE) {
                    $outcome = $this->applyApprove($event, $link, $buyerEmail, $productId, $extracted, $index);
                } else {
                    $outcome = $this->applyRevoke($event, $link, $buyerEmail, $productId, $extracted, $index);
                }

                $outcomes[] = [
                    'index' => $index,
                    'product_id' => $productId,
                    ...$outcome,
                ];
            }

            $this->finalizeEventFromOutcomes($event, $outcomes);
            $this->logStep($event, 'finalize', 'info', 'Processamento finalizado.', [
                'outcomes' => $outcomes,
                'status' => $event->processing_status instanceof PaymentProcessingStatus
                    ? $event->processing_status->value
                    : $event->processing_status,
                'reason' => $event->processing_reason,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Erro ao processar evento de webhook de pagamento', [
                'payment_event_id' => $event->id,
                'error' => $exception->getMessage(),
            ]);

            $this->markEvent($event, PaymentProcessingStatus::FAILED, 'processor_exception');
            $this->logStep($event, 'exception', 'error', 'Falha no processamento.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(PaymentWebhookLink $link, array $payload): array
    {
        $link->loadMissing(['fieldMappings', 'eventMappings', 'productMappings']);

        $fieldMappings = $link->fieldMappings->keyBy('field_key');
        $base = $this->extractBaseFields($payload, $fieldMappings);

        $eventCode = (string) ($base['event_code'] ?? '');
        $eventMapping = $eventCode !== '' ? $this->resolveEventMapping($link->eventMappings, $eventCode) : null;
        $action = $eventMapping?->internal_action instanceof PaymentInternalAction
            ? $eventMapping->internal_action->value
            : (string) ($eventMapping->internal_action ?? '');

        $items = $this->resolveItems($payload, $fieldMappings);
        $itemsPreview = [];

        foreach ($items as $index => $itemContext) {
            $productId = $this->resolveProductId((array) $itemContext, $payload, $fieldMappings);
            $mapping = $link->productMappings
                ->first(fn (PaymentProductMapping $productMapping) => (string) $productMapping->external_product_id === $productId);

            $itemsPreview[] = [
                'index' => $index,
                'product_id' => $productId,
                'mapped_course_id' => $mapping?->course_id,
                'mapped_course_title' => $mapping?->course?->title,
            ];
        }

        return [
            'base_fields' => $base,
            'resolved_action' => $action !== '' ? $action : null,
            'items' => $itemsPreview,
            'notes' => [
                'event_mapped' => $eventMapping !== null,
                'item_count' => count($itemsPreview),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  \Illuminate\Support\Collection<string, PaymentFieldMapping>  $fieldMappings
     * @return array{event_code:?string,buyer_email:?string,external_tx_id:?string,amount:?float,currency:?string,occurred_at:?CarbonImmutable}
     */
    private function extractBaseFields(array $payload, Collection $fieldMappings): array
    {
        $eventCode = $this->toString($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_EVENT_CODE));
        $buyerEmail = $this->toString($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_BUYER_EMAIL));
        $externalTxId = $this->toString($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_EXTERNAL_TX_ID));
        $amount = $this->toMoney($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_AMOUNT));
        $currency = $this->toCurrency($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_CURRENCY));
        $occurredAt = $this->toDate($this->valueFromMapping($payload, $fieldMappings, PaymentFieldMapping::FIELD_OCCURRED_AT));

        return [
            'event_code' => $eventCode,
            'buyer_email' => $buyerEmail,
            'external_tx_id' => $externalTxId,
            'amount' => $amount,
            'currency' => $currency,
            'occurred_at' => $occurredAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  \Illuminate\Support\Collection<string, PaymentFieldMapping>  $fieldMappings
     * @return array<int, array<string, mixed>>
     */
    private function resolveItems(array $payload, Collection $fieldMappings): array
    {
        $itemsPath = $fieldMappings->get(PaymentFieldMapping::FIELD_ITEMS)?->json_path;

        if ($itemsPath === null || trim((string) $itemsPath) === '') {
            return [$payload];
        }

        $items = $this->extractor->get($payload, $itemsPath);

        if (! is_array($items)) {
            return [$payload];
        }

        if ($items === []) {
            return [];
        }

        if (array_is_list($items)) {
            return array_values(array_filter($items, static fn ($item) => is_array($item)));
        }

        return [$items];
    }

    /**
     * @param  array<string, mixed>  $itemContext
     * @param  array<string, mixed>  $payload
     * @param  \Illuminate\Support\Collection<string, PaymentFieldMapping>  $fieldMappings
     */
    private function resolveProductId(array $itemContext, array $payload, Collection $fieldMappings): string
    {
        $itemPath = $fieldMappings->get(PaymentFieldMapping::FIELD_ITEM_PRODUCT_ID)?->json_path;
        if (! $itemPath) {
            return '';
        }

        $fromItem = $this->toString($this->extractor->get($itemContext, $itemPath));
        if ($fromItem !== null && $fromItem !== '') {
            return $fromItem;
        }

        return $this->toString($this->extractor->get($payload, $itemPath)) ?? '';
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

    private function resolveEventMapping(Collection $mappings, string $eventCode): ?PaymentEventMapping
    {
        $eventCode = trim($eventCode);

        return $mappings->first(function (PaymentEventMapping $mapping) use ($eventCode): bool {
            return strcasecmp(trim((string) $mapping->external_event_code), $eventCode) === 0;
        });
    }

    /**
     * @param  array{external_tx_id:?string,amount:?float,currency:?string,occurred_at:?CarbonImmutable}  $extracted
     * @return array{status:PaymentProcessingStatus,reason:string,course_id?:int,user_id?:int}
     */
    private function applyApprove(PaymentEvent $event, PaymentWebhookLink $link, string $buyerEmail, string $productId, array $extracted, int $itemIndex): array
    {
        if ($productId === '') {
            return [
                'status' => PaymentProcessingStatus::PENDING,
                'reason' => 'product_id_missing',
            ];
        }

        $mapping = $this->resolveProductMapping($link, $productId);
        if (! $mapping || ! $mapping->course) {
            return [
                'status' => PaymentProcessingStatus::PENDING,
                'reason' => 'product_unmapped',
            ];
        }

        $user = $this->resolveUser($buyerEmail);
        $course = $mapping->course;

        $entitlement = PaymentEntitlement::firstOrNew([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payment_webhook_link_id' => $link->id,
            'external_tx_id' => (string) ($extracted['external_tx_id'] ?? ''),
            'external_product_id' => $productId,
        ]);

        $entitlement->state = PaymentEntitlementState::ACTIVE;
        $entitlement->last_event_at = $extracted['occurred_at'] ?? now();
        $entitlement->last_payment_event_id = $event->id;
        $entitlement->save();

        $this->syncEnrollmentAccess($user, $course, 'approve');
        $this->recordTrackingEvent($event, $itemIndex, 'PurchaseApproved', $user, $course, $extracted);

        return [
            'status' => PaymentProcessingStatus::PROCESSED,
            'reason' => 'approved',
            'course_id' => $course->id,
            'user_id' => $user->id,
        ];
    }

    /**
     * @param  array{external_tx_id:?string,amount:?float,currency:?string,occurred_at:?CarbonImmutable}  $extracted
     * @return array{status:PaymentProcessingStatus,reason:string,course_id?:int,user_id?:int}
     */
    private function applyRevoke(PaymentEvent $event, PaymentWebhookLink $link, string $buyerEmail, string $productId, array $extracted, int $itemIndex): array
    {
        if ($productId === '') {
            return [
                'status' => PaymentProcessingStatus::IGNORED,
                'reason' => 'product_id_missing',
            ];
        }

        $mapping = $this->resolveProductMapping($link, $productId);
        if (! $mapping || ! $mapping->course) {
            return [
                'status' => PaymentProcessingStatus::IGNORED,
                'reason' => 'product_unmapped',
            ];
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($buyerEmail)])->first();
        if (! $user) {
            return [
                'status' => PaymentProcessingStatus::IGNORED,
                'reason' => 'buyer_not_found',
            ];
        }

        $course = $mapping->course;
        $entitlements = PaymentEntitlement::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('payment_webhook_link_id', $link->id)
            ->where('external_product_id', $productId)
            ->when(
                (string) ($extracted['external_tx_id'] ?? '') !== '',
                fn ($query) => $query->where('external_tx_id', (string) $extracted['external_tx_id'])
            )
            ->get();

        if ($entitlements->isEmpty()) {
            return [
                'status' => PaymentProcessingStatus::IGNORED,
                'reason' => 'revoke_without_active_purchase',
            ];
        }

        foreach ($entitlements as $entitlement) {
            $entitlement->state = PaymentEntitlementState::REVOKED;
            $entitlement->last_event_at = $extracted['occurred_at'] ?? now();
            $entitlement->last_payment_event_id = $event->id;
            $entitlement->save();
        }

        $this->syncEnrollmentAccess($user, $course, 'revoke');
        $this->recordTrackingEvent($event, $itemIndex, 'PurchaseRevoked', $user, $course, $extracted);

        return [
            'status' => PaymentProcessingStatus::PROCESSED,
            'reason' => 'revoked',
            'course_id' => $course->id,
            'user_id' => $user->id,
        ];
    }

    private function resolveProductMapping(PaymentWebhookLink $link, string $productId): ?PaymentProductMapping
    {
        return $link->productMappings
            ->first(fn (PaymentProductMapping $mapping) => $mapping->is_active && (string) $mapping->external_product_id === $productId);
    }

    private function resolveUser(string $email): User
    {
        $normalized = strtolower(trim($email));

        $existing = User::query()->whereRaw('LOWER(email) = ?', [$normalized])->first();
        if ($existing) {
            return $existing;
        }

        $temporaryPassword = Str::random(10);

        $name = Str::before($normalized, '@');
        $name = trim($name) !== '' ? Str::title(str_replace(['.', '_', '-'], ' ', $name)) : $normalized;

        $user = User::create([
            'name' => $name,
            'display_name' => $name,
            'email' => $normalized,
            'role' => UserRole::STUDENT,
            'password' => $temporaryPassword,
        ]);

        try {
            Mail::to($user->email)->send(new WelcomePaymentUser($user, $temporaryPassword));
        } catch (\Throwable $exception) {
            Log::warning('Falha ao enviar e-mail de boas-vindas de pagamento', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }

        return $user;
    }

    private function syncEnrollmentAccess(User $user, Course $course, string $reason): void
    {
        $enrollment = Enrollment::query()->firstOrCreate(
            [
                'course_id' => $course->id,
                'user_id' => $user->id,
            ],
            [
                'progress_percent' => 0,
                'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                'completed_at' => null,
            ]
        );

        if ($enrollment->manual_override) {
            if ($enrollment->access_status !== EnrollmentAccessStatus::ACTIVE) {
                $enrollment->forceFill([
                    'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                    'access_block_reason' => null,
                    'access_blocked_at' => null,
                ])->save();
            }

            return;
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

            return;
        }

        if ($hasRevoked) {
            $enrollment->forceFill([
                'access_status' => EnrollmentAccessStatus::BLOCKED->value,
                'access_block_reason' => $reason,
                'access_blocked_at' => $enrollment->access_blocked_at ?? now(),
            ])->save();
        }
    }

    /**
     * @param  array{external_tx_id:?string,amount:?float,currency:?string,occurred_at:?CarbonImmutable}  $extracted
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
                'value' => $extracted['amount'],
                'currency' => $extracted['currency'] ?? 'BRL',
                'properties' => [
                    'payment_event_id' => $paymentEvent->id,
                    'webhook_link_id' => $paymentEvent->payment_webhook_link_id,
                    'transaction_code' => $extracted['external_tx_id'],
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
}
