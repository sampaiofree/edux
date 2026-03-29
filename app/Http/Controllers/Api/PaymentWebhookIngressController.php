<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentInternalAction;
use App\Enums\PaymentProcessingStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use App\Models\PaymentWebhookLink;
use App\Support\Payments\PayloadHasher;
use App\Support\Payments\PaymentWebhookProcessor;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookIngressController extends Controller
{
    public function __invoke(Request $request, string $endpoint_uuid, PaymentWebhookProcessor $processor): JsonResponse
    {
        $link = PaymentWebhookLink::query()->where('endpoint_uuid', $endpoint_uuid)->first();

        if (! $link) {
            return $this->errorResponse('not_found', 404);
        }

        $rawContent = (string) $request->getContent();

        if (! $this->passesSecurity($request, $link, $rawContent)) {
            return $this->errorResponse('invalid_signature', 403);
        }

        if (! $link->is_active) {
            return $this->errorResponse('webhook_inactive', 409);
        }

        $payload = $this->extractPayload($request, $rawContent);
        if ($payload === []) {
            return $this->errorResponse('invalid_payload', 400);
        }

        $headers = collect($request->headers->all())
            ->map(static fn ($value) => is_array($value) ? implode(', ', $value) : (string) $value)
            ->all();

        $payloadHash = PayloadHasher::hashForLink($payload, $link->id);

        try {
            $event = PaymentEvent::create([
                'payment_webhook_link_id' => $link->id,
                'payload_hash' => $payloadHash,
                'raw_payload' => $payload,
                'raw_headers' => $headers,
                'processing_status' => PaymentProcessingStatus::QUEUED,
                'received_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                $existingEvent = PaymentEvent::query()
                    ->where('payment_webhook_link_id', $link->id)
                    ->where('payload_hash', $payloadHash)
                    ->latest('id')
                    ->first();

                return $this->duplicateResponse($existingEvent);
            }

            throw $exception;
        }

        try {
            $result = $processor->process($event);
        } catch (\Throwable $exception) {
            Log::error('Erro nao tratado no ingress do webhook de pagamento', [
                'payment_event_id' => $event->id,
                'error' => $exception->getMessage(),
            ]);

            $event->forceFill([
                'processing_status' => PaymentProcessingStatus::FAILED,
                'processing_reason' => 'processor_exception',
                'processed_at' => now(),
            ])->save();

            $result = [
                'processing_status' => PaymentProcessingStatus::FAILED,
                'processing_reason' => 'processor_exception',
                'action' => null,
                'buyer_email' => null,
                'course_reference' => null,
                'course_id' => null,
                'user_id' => null,
                'enrollment_result' => PaymentWebhookProcessor::ENROLLMENT_NONE,
            ];
        }

        return $this->processingResponse($event, $result);
    }

    private function passesSecurity(Request $request, PaymentWebhookLink $link, string $rawContent): bool
    {
        $mode = trim((string) ($link->security_mode ?? ''));

        if ($mode === '') {
            return true;
        }

        $secret = (string) ($link->secret ?? '');
        $headerName = trim((string) ($link->signature_header ?? ''));

        if ($secret === '' || $headerName === '') {
            return true;
        }

        $headerValue = trim((string) $request->header($headerName, ''));

        if ($mode === 'header_secret') {
            return hash_equals($secret, $headerValue);
        }

        if ($mode === 'hmac_sha256') {
            $signature = hash_hmac('sha256', $this->signaturePayload($request, $rawContent), $secret);

            return hash_equals($signature, $headerValue);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPayload(Request $request, string $rawContent): array
    {
        $payload = $request->json()->all();
        if (is_array($payload) && $payload !== []) {
            return $payload;
        }

        $decoded = json_decode($rawContent, true);
        if (is_array($decoded) && $decoded !== []) {
            return $decoded;
        }

        $queryPayload = $request->query();

        return is_array($queryPayload) ? $queryPayload : [];
    }

    private function signaturePayload(Request $request, string $rawContent): string
    {
        if ($rawContent !== '') {
            return $rawContent;
        }

        return (string) $request->server('QUERY_STRING', '');
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }

    private function processingResponse(PaymentEvent $event, array $result): JsonResponse
    {
        $status = $result['processing_status'];
        $reason = (string) ($result['processing_reason'] ?? '');
        $action = $result['action'] instanceof PaymentInternalAction ? $result['action']->value : null;

        return response()->json([
            'status' => $status->value,
            'reason' => $reason,
            'message' => $this->messageForReason($reason),
            'event_id' => $event->id,
            'action' => $action,
            'details' => [
                'buyer_email' => $result['buyer_email'] ?? null,
                'course_reference' => $result['course_reference'] ?? null,
                'course_id' => $result['course_id'] ?? null,
                'user_id' => $result['user_id'] ?? null,
                'enrollment_result' => $result['enrollment_result'] ?? PaymentWebhookProcessor::ENROLLMENT_NONE,
            ],
        ], $this->httpStatusForProcessingResult($status));
    }

    private function duplicateResponse(?PaymentEvent $event): JsonResponse
    {
        $action = $event?->internal_action instanceof PaymentInternalAction
            ? $event->internal_action->value
            : PaymentInternalAction::tryFrom((string) $event?->internal_action)?->value;

        return response()->json([
            'status' => 'duplicate',
            'reason' => 'duplicate_payload',
            'message' => $this->messageForReason('duplicate_payload'),
            'event_id' => $event?->id,
            'action' => $action,
            'details' => [
                'buyer_email' => $event?->buyer_email,
                'course_reference' => $event?->external_product_id,
                'course_id' => null,
                'user_id' => null,
                'enrollment_result' => PaymentWebhookProcessor::ENROLLMENT_NONE,
            ],
        ], 200);
    }

    private function errorResponse(string $reason, int $statusCode): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'reason' => $reason,
            'message' => $this->messageForReason($reason),
            'event_id' => null,
            'action' => null,
            'details' => [
                'buyer_email' => null,
                'course_reference' => null,
                'course_id' => null,
                'user_id' => null,
                'enrollment_result' => PaymentWebhookProcessor::ENROLLMENT_NONE,
            ],
        ], $statusCode);
    }

    private function httpStatusForProcessingResult(PaymentProcessingStatus $status): int
    {
        return match ($status) {
            PaymentProcessingStatus::PROCESSED => 200,
            PaymentProcessingStatus::PENDING, PaymentProcessingStatus::IGNORED => 422,
            PaymentProcessingStatus::FAILED => 500,
            PaymentProcessingStatus::QUEUED => 202,
        };
    }

    private function messageForReason(string $reason): string
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
            'processed_with_ignores' => 'Webhook processado com ressalvas.',
            'processed' => 'Webhook processado com sucesso.',
            'webhook_link_not_found' => 'Link de webhook não encontrado durante o processamento.',
            'no_item_outcome' => 'Webhook recebido sem dados suficientes para gerar resultado.',
            default => 'Webhook processado.',
        };
    }
}
