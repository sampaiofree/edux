<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\NotificationsManager;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationPushDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_notification_is_saved_as_internal_feed_without_push_side_effects(): void
    {
        $admin = $this->defaultTenantAdmin();

        $this->actingAs($admin);

        Livewire::test(NotificationsManager::class)
            ->set('title', 'Nova aula liberada')
            ->set('body', 'Confira o novo conteudo publicado hoje.')
            ->set('published_at', now()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $notification = Notification::query()->firstOrFail();

        $this->assertSame($admin->system_setting_id, $notification->system_setting_id);
        $this->assertSame('Nova aula liberada', $notification->title);
        $this->assertNotNull($notification->published_at);
        $this->assertNull($notification->pushed_at);
    }

    public function test_notifications_page_keeps_only_the_internal_feed(): void
    {
        $student = $this->defaultTenantStudent([
            'email' => 'student-notifications-page@example.com',
        ]);

        $response = $this->actingAs($student)->get(route('learning.notifications.index'));

        $response->assertOk();
        $response->assertSee('Mensagens do EduX');
        $response->assertDontSee('Ativar notificacoes');
    }
}
