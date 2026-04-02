<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StudentOneSignalWebSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_student_dashboard_renders_onesignal_web_setup_when_tenant_has_app_id(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-onesignal-web@example.com',
        ]);

        $admin->systemSetting()->withoutGlobalScopes()->firstOrFail()->update([
            'onesignal_app_id' => 'f3fa34b8-50d2-4176-ba8f-94dd9ab70290',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard', ['tab' => 'cursos']));

        $response->assertOk();
        $response->assertSee('https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js', false);
        $response->assertSee('OneSignalSDKWorker.js', false);
        $response->assertSee('serviceWorkerScope', false);
        $response->assertSee('diagnosticsUrl', false);
        $response->assertSee($student->oneSignalExternalId(), false);
        $response->assertSee('data-onesignal-prompt-trigger="1"', false);
        $response->assertSee('data-onesignal-prompt-root', false);

        $workerPath = public_path('push/onesignal/OneSignalSDKWorker.js');

        $this->assertTrue(File::exists($workerPath));
        $this->assertStringContainsString(
            'importScripts("https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js");',
            File::get($workerPath)
        );
    }

    public function test_student_dashboard_does_not_render_onesignal_web_setup_without_tenant_app_id(): void
    {
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-sem-onesignal@example.com',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard', ['tab' => 'cursos']));

        $response->assertOk();
        $response->assertDontSee('https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js', false);
        $response->assertDontSee('data-onesignal-prompt-trigger="1"', false);
    }

    public function test_student_notification_page_also_renders_onesignal_prompt_when_enabled(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-onesignal-notifications@example.com',
        ]);

        $admin->systemSetting()->withoutGlobalScopes()->firstOrFail()->update([
            'onesignal_app_id' => '7d9cb45e-fb6d-4c01-a962-e31ea5936eca',
        ]);

        $response = $this->actingAs($student)->get(route('learning.notifications.index'));

        $response->assertOk();
        $response->assertSee('data-onesignal-prompt-trigger="1"', false);
        $response->assertSee('Receba avisos importantes', false);
    }
}
