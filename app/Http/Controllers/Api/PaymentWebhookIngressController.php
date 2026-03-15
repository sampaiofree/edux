<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentProcessingStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhookEvent;
use App\Models\PaymentEvent;
use App\Models\PaymentWebhookLink;
use App\Support\Payments\PayloadHasher;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookIngressController extends Controller
{
    public function __invoke(Request $request, string $endpoint_uuid): JsonResponse
    {
        $link = PaymentWebhookLink::query()->where('endpoint_uuid', $endpoint_uuid)->first();

        if (! $link) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $rawContent = (string) $request->getContent();

        if (! $this->passesSecurity($request, $link, $rawContent)) {
            return response()->json(['status' => 'ok'], 200);
        }

        if (! $link->is_active) {
            return response()->json(['status' => 'ok'], 200);
        }

        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            $decoded = json_decode($rawContent, true);
            $payload = is_array($decoded) ? $decoded : [];
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
                return response()->json(['status' => 'ok'], 200);
            }

            throw $exception;
        }

        ProcessPaymentWebhookEvent::dispatch($event->id);

        return response()->json(['status' => 'ok'], 200);
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
            $signature = hash_hmac('sha256', $rawContent, $secret);

            return hash_equals($signature, $headerValue);
        }

        return true;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
