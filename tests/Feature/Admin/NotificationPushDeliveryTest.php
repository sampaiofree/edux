<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Jobs\SendNotificationPush;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationPushDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_notification_is_sent_through_onesignal_for_students_of_the_same_tenant(): void
    {
        Http::fake([
            'https://api.onesignal.com/*' => Http::response(['id' => 'push-live-1'], 200),
        ]);

        $admin = $this->defaultTenantAdmin();
        $admin->systemSetting->update([
            'onesignal_app_id' => '44444444-4444-4444-4444-444444444444',
            'onesignal_rest_api_key' => 'tenant-rest-key',
        ]);

        $studentA = $this->defaultTenantStudent(['email' => 'student-a@example.com']);
        $studentB = $this->defaultTenantStudent(['email' => 'student-b@example.com']);

        $otherAdmin = $this->createAdminForTenant(
            ['email' => 'other-admin-notification@example.com'],
            ['domain' => 'cursos.outro-tenant-notification.test', 'escola_nome' => 'Outra Escola']
        );
        $otherStudent = $this->createStudentForTenant($otherAdmin, ['email' => 'student-other@example.com']);

        $notification = Notification::create([
            'system_setting_id' => $admin->system_setting_id,
            'title' => 'Nova aula liberada',
            'body' => 'Confira o novo conteudo publicado hoje.',
            'published_at' => now(),
        ]);

        (new SendNotificationPush($notification->id))->handle();

        $notification->refresh();

        $this->assertNotNull($notification->pushed_at);

        Http::assertSent(function ($request) use ($studentA, $studentB, $otherStudent, $notification): bool {
            $data = $request->data();
            $externalIds = $data['include_aliases']['external_id'] ?? [];
            sort($externalIds);

            $expected = [
                $studentA->oneSignalExternalId(),
                $studentB->oneSignalExternalId(),
            ];
            sort($expected);

            return $request->url() === 'https://api.onesignal.com/notifications?c=push'
                && $data['app_id'] === '44444444-4444-4444-4444-444444444444'
                && $externalIds === $expected
                && ! in_array($otherStudent->oneSignalExternalId(), $externalIds, true)
                && $data['data']['notification_id'] === $notification->id;
        });
    }

    public function test_published_notification_without_onesignal_configuration_is_not_marked_as_pushed(): void
    {
        Http::fake();

        $admin = $this->defaultTenantAdmin();

        $notification = Notification::create([
            'system_setting_id' => $admin->system_setting_id,
            'title' => 'Aviso sem push',
            'published_at' => now(),
        ]);

        (new SendNotificationPush($notification->id))->handle();

        $this->assertNull($notification->fresh()->pushed_at);
        Http::assertNothingSent();
    }

    public function test_failed_onesignal_request_keeps_notification_unpushed(): void
    {
        Http::fake([
            'https://api.onesignal.com/*' => Http::response(['errors' => ['invalid api key']], 400),
        ]);

        $admin = $this->defaultTenantAdmin();
        $admin->systemSetting->update([
            'onesignal_app_id' => '55555555-5555-5555-5555-555555555555',
            'onesignal_rest_api_key' => 'broken-rest-key',
        ]);

        $this->defaultTenantStudent(['email' => 'student-failed-push@example.com']);

        $notification = Notification::create([
            'system_setting_id' => $admin->system_setting_id,
            'title' => 'Push com falha',
            'published_at' => now(),
        ]);

        try {
            (new SendNotificationPush($notification->id))->handle();
            $this->fail('Expected OneSignal request to fail.');
        } catch (RequestException) {
            $this->assertNull($notification->fresh()->pushed_at);
        }
    }

    public function test_notifications_page_keeps_internal_feed_without_browser_push_controls(): void
    {
        $student = $this->defaultTenantStudent([
            'email' => 'student-notifications-page@example.com',
            'role' => UserRole::STUDENT->value,
        ]);

        $response = $this->actingAs($student)->get(route('learning.notifications.index'));

        $response->assertOk();
        $response->assertSee('Receba avisos pelo app da sua escola');
        $response->assertDontSee('data-push-manager', false);
        $response->assertDontSee('Ativar notificacoes');
    }
}
