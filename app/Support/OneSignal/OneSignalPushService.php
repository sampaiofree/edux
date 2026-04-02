<?php

namespace App\Support\OneSignal;

use App\Models\Notification as EduNotification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OneSignalPushService
{
    public function __construct(private readonly HttpFactory $http) {}

    public function isConfiguredFor(SystemSetting $settings): bool
    {
        return filled($settings->onesignal_app_id)
            && filled($settings->onesignal_rest_api_key);
    }

    /**
     * @param  iterable<User>  $users
     */
    public function sendNotification(SystemSetting $settings, EduNotification $notification, iterable $users): void
    {
        $externalIds = $this->externalIdsForUsers($users, $settings->id);

        if ($externalIds === []) {
            return;
        }

        $payload = [
            'app_id' => $settings->onesignal_app_id,
            'target_channel' => 'push',
            'include_aliases' => [
                'external_id' => $externalIds,
            ],
            'headings' => $this->localizedText($notification->title),
            'contents' => $this->localizedText($this->notificationBody($notification)),
            'data' => [
                'notification_id' => $notification->id,
                'system_setting_id' => $settings->id,
                'url' => $this->notificationTargetUrl($settings, $notification),
            ],
            'app_url' => $this->notificationTargetUrl($settings, $notification),
            'url' => $this->notificationTargetUrl($settings, $notification),
            'web_url' => $this->notificationTargetUrl($settings, $notification),
        ];

        if ($image = $this->notificationImageUrl($notification)) {
            $payload['big_picture'] = $image;
            $payload['ios_attachments'] = [
                'image' => $image,
            ];
        }

        $this->send($settings, $payload, [
            'notification_id' => $notification->id,
            'target_count' => count($externalIds),
        ]);
    }

    public function sendTestPush(SystemSetting $settings, User $user): void
    {
        $title = 'Teste de notificações';
        $body = 'Se você recebeu esta mensagem, o OneSignal desta escola está configurado corretamente.';
        $targetUrl = $settings->appUrl(route('learning.notifications.index', absolute: false));

        $payload = [
            'app_id' => $settings->onesignal_app_id,
            'target_channel' => 'push',
            'include_aliases' => [
                'external_id' => [$user->oneSignalExternalId()],
            ],
            'headings' => $this->localizedText($title),
            'contents' => $this->localizedText($body),
            'data' => [
                'is_test' => true,
                'system_setting_id' => $settings->id,
                'url' => $targetUrl,
            ],
            'app_url' => $targetUrl,
            'url' => $targetUrl,
            'web_url' => $targetUrl,
        ];

        $this->send($settings, $payload, [
            'user_id' => $user->id,
            'is_test' => true,
        ]);
    }

    /**
     * @param  iterable<User>  $users
     * @return list<string>
     */
    private function externalIdsForUsers(iterable $users, int $systemSettingId): array
    {
        return Collection::make($users)
            ->filter(fn (User $user): bool => (int) $user->system_setting_id === $systemSettingId)
            ->map(fn (User $user): string => $user->oneSignalExternalId())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function localizedText(string $value): array
    {
        return [
            'pt' => $value,
            'en' => $value,
        ];
    }

    private function notificationBody(EduNotification $notification): string
    {
        $body = trim((string) $notification->body);

        if ($body !== '') {
            return Str::of($body)->limit(160)->toString();
        }

        return 'Você recebeu uma nova notificação no EduX.';
    }

    private function notificationTargetUrl(SystemSetting $settings, EduNotification $notification): string
    {
        $buttonUrl = trim((string) ($notification->button_url ?? ''));

        if ($buttonUrl !== '') {
            return $buttonUrl;
        }

        return $settings->appUrl(route('learning.notifications.index', absolute: false));
    }

    private function notificationImageUrl(EduNotification $notification): ?string
    {
        if (! $notification->image_path) {
            return null;
        }

        return asset('storage/'.$notification->image_path);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     */
    private function send(SystemSetting $settings, array $payload, array $context = []): void
    {
        $this->ensureConfigured($settings);
        $diagnosticContext = $this->credentialDiagnostics($settings);
        $apiUrl = rtrim((string) config('services.onesignal.api_url', 'https://api.onesignal.com'), '/');

        try {
            Log::info('onesignal.push_request_started', array_merge($context, $diagnosticContext, [
                'system_setting_id' => $settings->id,
                'api_url' => $apiUrl,
            ]));

            $response = $this->http
                ->baseUrl($apiUrl)
                ->acceptJson()
                ->withHeaders([
                    'Authorization' => 'Key '.$settings->onesignal_rest_api_key,
                ])
                ->post('/notifications?c=push', $payload)
                ->throw();

            Log::info('onesignal.push_sent', array_merge($context, $diagnosticContext, [
                'system_setting_id' => $settings->id,
                'response_status' => $response->status(),
                'response_id' => $response->json('id'),
            ]));
        } catch (RequestException $exception) {
            Log::error('onesignal.push_failed', array_merge($context, $diagnosticContext, [
                'system_setting_id' => $settings->id,
                'response_status' => $exception->response?->status(),
                'response_body' => $exception->response?->body(),
            ]));

            throw $exception;
        }
    }

    private function ensureConfigured(SystemSetting $settings): void
    {
        if ($this->isConfiguredFor($settings)) {
            return;
        }

        throw new \RuntimeException('O OneSignal não está configurado para esta escola.');
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function credentialDiagnostics(SystemSetting $settings): array
    {
        $restApiKey = trim((string) ($settings->onesignal_rest_api_key ?? ''));

        return [
            'onesignal_app_id' => $settings->onesignal_app_id,
            'onesignal_rest_api_key_present' => $restApiKey !== '',
            'onesignal_rest_api_key_length' => $restApiKey !== '' ? strlen($restApiKey) : 0,
            'onesignal_rest_api_key_sha256' => $restApiKey !== '' ? hash('sha256', $restApiKey) : null,
            'onesignal_rest_api_key_format' => $this->detectRestKeyFormat($restApiKey),
        ];
    }

    private function detectRestKeyFormat(string $restApiKey): ?string
    {
        if ($restApiKey === '') {
            return null;
        }

        return match (true) {
            str_starts_with($restApiKey, 'os_v2_app_') => 'os_v2_app',
            str_starts_with($restApiKey, 'Key ') => 'prefixed_with_key',
            default => 'custom',
        };
    }
}
