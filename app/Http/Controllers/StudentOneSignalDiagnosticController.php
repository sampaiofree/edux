<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentOneSignalDiagnosticController extends Controller
{
    /**
     * @var list<string>
     */
    private const ALLOWED_EVENTS = [
        'onesignal.web_sdk_ready',
        'onesignal.web_contacts_synced',
        'onesignal.web_contact_sync_failed',
        'onesignal.web_modal_shown',
        'onesignal.web_modal_dismissed',
        'onesignal.web_modal_manual_opened',
        'onesignal.web_prompt_displayed',
        'onesignal.web_permission_changed',
        'onesignal.web_subscription_changed',
        'onesignal.web_login_state_changed',
        'onesignal.web_subscription_missing_after_grant',
        'onesignal.web_service_worker_mismatch',
    ];

    /**
     * @var list<string>
     */
    private const WARNING_EVENTS = [
        'onesignal.web_contact_sync_failed',
        'onesignal.web_subscription_missing_after_grant',
        'onesignal.web_service_worker_mismatch',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event' => ['required', 'string', Rule::in(self::ALLOWED_EVENTS)],
            'permission' => ['nullable', 'string', Rule::in(['default', 'granted', 'denied'])],
            'opted_in' => ['nullable', 'boolean'],
            'external_id_matches' => ['nullable', 'boolean'],
            'email_present' => ['nullable', 'boolean'],
            'subscription_id_present' => ['nullable', 'boolean'],
            'subscription_id_hash' => ['nullable', 'string', 'max:64'],
            'sms_phone_present' => ['nullable', 'boolean'],
            'sms_phone_hash' => ['nullable', 'string', 'max:64'],
            'token_present' => ['nullable', 'boolean'],
            'onesignal_id_present' => ['nullable', 'boolean'],
            'sdk_ready' => ['nullable', 'boolean'],
            'service_worker_scope' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2000'],
            'user_agent' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $message = $data['event'];

        $context = array_filter([
            'system_setting_id' => $user?->system_setting_id,
            'user_id' => $user?->id,
            'permission' => $data['permission'] ?? null,
            'opted_in' => $data['opted_in'] ?? null,
            'external_id_matches' => $data['external_id_matches'] ?? null,
            'email_present' => $data['email_present'] ?? null,
            'subscription_id_present' => $data['subscription_id_present'] ?? null,
            'subscription_id_hash' => $this->sanitizeHash($data['subscription_id_hash'] ?? null),
            'sms_phone_present' => $data['sms_phone_present'] ?? null,
            'sms_phone_hash' => $this->sanitizeHash($data['sms_phone_hash'] ?? null),
            'token_present' => $data['token_present'] ?? null,
            'onesignal_id_present' => $data['onesignal_id_present'] ?? null,
            'sdk_ready' => $data['sdk_ready'] ?? null,
            'service_worker_scope' => $this->limitString($data['service_worker_scope'] ?? null, 255),
            'url' => $this->limitString($data['url'] ?? null, 2000),
            'reported_user_agent' => $this->limitString($data['user_agent'] ?? null, 1000),
            'request_user_agent' => $this->limitString($request->userAgent(), 1000),
        ], static fn ($value): bool => $value !== null);

        $logMethod = in_array($message, self::WARNING_EVENTS, true) ? 'warning' : 'info';
        Log::{$logMethod}($message, $context);

        return response()->json(['status' => 'ok'], 202);
    }

    private function sanitizeHash(?string $value): ?string
    {
        $candidate = strtolower(trim((string) $value));

        if ($candidate === '' || ! preg_match('/\A[a-f0-9]{8,64}\z/', $candidate)) {
            return null;
        }

        return Str::limit($candidate, 16, '');
    }

    private function limitString(?string $value, int $limit): ?string
    {
        $candidate = trim((string) $value);

        if ($candidate === '') {
            return null;
        }

        return Str::limit($candidate, $limit, '');
    }
}
