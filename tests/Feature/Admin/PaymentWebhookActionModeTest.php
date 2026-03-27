<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentWebhookLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentWebhookActionModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_create_page_renders_action_mode_select(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.webhooks.create'));

        $response->assertOk();
        $response->assertSee('Acao do webhook', false);
        $response->assertSee('name="action_mode"', false);
        $response->assertSee('Cadastrar', false);
        $response->assertSee('Bloquear', false);
        $response->assertSee('value="register" selected', false);
    }

    public function test_admin_store_persists_selected_action_mode(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.webhooks.store'), [
            'name' => 'Webhook Bloqueio',
            'action_mode' => PaymentWebhookLink::ACTION_BLOCK,
            'is_active' => '1',
            'security_mode' => '',
            'signature_header' => '',
            'secret' => '',
        ]);

        $link = PaymentWebhookLink::query()->where('name', 'Webhook Bloqueio')->firstOrFail();

        $response->assertRedirect(route('admin.webhooks.edit', $link));
        $response->assertSessionHasNoErrors();
        $this->assertSame(PaymentWebhookLink::ACTION_BLOCK, $link->action_mode);
    }

    public function test_admin_edit_page_reflects_saved_action_mode(): void
    {
        $admin = User::factory()->admin()->create();
        $link = PaymentWebhookLink::create([
            'name' => 'Webhook Salvo',
            'endpoint_uuid' => (string) Str::uuid(),
            'is_active' => true,
            'action_mode' => PaymentWebhookLink::ACTION_BLOCK,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.webhooks.edit', $link));

        $response->assertOk();
        $response->assertSee('name="action_mode"', false);
        $response->assertSee('value="block" selected', false);
    }

    public function test_admin_update_changes_action_mode(): void
    {
        $admin = User::factory()->admin()->create();
        $link = PaymentWebhookLink::create([
            'name' => 'Webhook Atualizar',
            'endpoint_uuid' => (string) Str::uuid(),
            'is_active' => true,
            'action_mode' => PaymentWebhookLink::ACTION_REGISTER,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.webhooks.update', $link), [
            'name' => 'Webhook Atualizar',
            'action_mode' => PaymentWebhookLink::ACTION_BLOCK,
            'is_active' => '1',
            'security_mode' => '',
            'signature_header' => '',
            'secret' => '',
        ]);

        $response->assertRedirect(route('admin.webhooks.edit', $link));
        $response->assertSessionHasNoErrors();
        $this->assertSame(PaymentWebhookLink::ACTION_BLOCK, $link->fresh()->action_mode);
    }
}
