<?php

namespace Tests\Feature;

use App\Http\Middleware\PrepareStudentOneSignalPrompt;
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
            'whatsapp' => '(11) 95555-4444',
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
        $response->assertSee($student->oneSignalEmail(), false);
        $response->assertSee($student->oneSignalSmsPhone(), false);
        $response->assertSee('autoShowModal: true', false);
        $response->assertSee('data-onesignal-prompt-trigger="1"', false);
        $response->assertSee('data-onesignal-prompt-root', false);
        $response->assertSee('Quer receber avisos no navegador?', false);

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
        $response->assertSee('data-onesignal-manual-trigger="1"', false);
        $response->assertSee('Receba avisos no navegador', false);
        $response->assertSee('data-onesignal-prompt-root', false);
    }

    public function test_student_dashboard_omits_sms_phone_when_whatsapp_is_not_valid_for_onesignal(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-onesignal-invalido@example.com',
            'whatsapp' => '12345',
        ]);

        $admin->systemSetting()->withoutGlobalScopes()->firstOrFail()->update([
            'onesignal_app_id' => '7d9cb45e-fb6d-4c01-a962-e31ea5936eca',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard', ['tab' => 'cursos']));

        $response->assertOk();
        $response->assertSee('smsPhone: null', false);
    }

    public function test_student_prompt_is_auto_shown_only_on_first_authenticated_page_of_session(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-onesignal-first-session@example.com',
        ]);

        $admin->systemSetting()->withoutGlobalScopes()->firstOrFail()->update([
            'onesignal_app_id' => '7d9cb45e-fb6d-4c01-a962-e31ea5936eca',
        ]);

        $this->actingAs($student);

        $firstResponse = $this->get(route('dashboard', ['tab' => 'cursos']));
        $secondResponse = $this->get(route('learning.notifications.index'));

        $firstResponse->assertOk();
        $firstResponse->assertSee('autoShowModal: true', false);

        $secondResponse->assertOk();
        $secondResponse->assertSee('autoShowModal: false', false);
    }

    public function test_first_page_after_login_exposes_redirect_to_notifications_when_push_onboarding_is_pending(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-onesignal-after-login@example.com',
        ]);

        $admin->systemSetting()->withoutGlobalScopes()->firstOrFail()->update([
            'onesignal_app_id' => '7d9cb45e-fb6d-4c01-a962-e31ea5936eca',
        ]);

        $response = $this
            ->actingAs($student)
            ->withSession([
                PrepareStudentOneSignalPrompt::POST_LOGIN_REDIRECT_SESSION_KEY => true,
            ])
            ->get(route('dashboard', ['tab' => 'cursos']));

        $response->assertOk();
        $response->assertSee('postLoginRedirectUrl', false);
        $response->assertSee('push_onboarding=1', false);
        $response->assertSee('autoShowModal: false', false);
    }

    public function test_notifications_page_can_force_modal_after_login_redirect(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-onesignal-force-modal@example.com',
        ]);

        $admin->systemSetting()->withoutGlobalScopes()->firstOrFail()->update([
            'onesignal_app_id' => '7d9cb45e-fb6d-4c01-a962-e31ea5936eca',
        ]);

        $response = $this
            ->actingAs($student)
            ->get(route('learning.notifications.index', ['push_onboarding' => 1]));

        $response->assertOk();
        $response->assertSee('forceModalOnPage: true', false);
        $response->assertSee('data-onesignal-manual-trigger="1"', false);
    }

    public function test_manifest_route_returns_web_app_configuration_for_current_tenant(): void
    {
        $admin = $this->defaultTenantAdmin();

        $admin->systemSetting()->withoutGlobalScopes()->firstOrFail()->update([
            'escola_nome' => 'Escola Push EduX',
        ]);

        $response = $this->get(route('web.manifest'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');
        $response->assertJsonPath('name', 'Escola Push EduX');
        $response->assertJsonPath('display', 'standalone');
        $response->assertJsonPath('start_url', route('dashboard'));
    }
}
