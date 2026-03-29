<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PaymentFieldMapping;
use App\Models\PaymentWebhookLink;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemSettingTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_public_home_resolves_branding_and_courses_by_domain(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.alpha.test', 'Escola Alpha');
        [$adminB, $tenantB] = $this->createTenant('cursos.beta.test', 'Escola Beta');

        $this->createPublishedCourseForTenant($adminA, 'curso-alpha', 'Curso Alpha');
        $this->createPublishedCourseForTenant($adminB, 'curso-beta', 'Curso Beta');

        $responseA = $this
            ->get('http://'.$tenantA->domain.'/');

        $responseA->assertOk();
        $responseA->assertSee('Escola Alpha', false);
        $responseA->assertSee('Curso Alpha', false);
        $responseA->assertDontSee('Escola Beta', false);
        $responseA->assertDontSee('Curso Beta', false);

        $responseB = $this
            ->get('http://'.$tenantB->domain.'/');

        $responseB->assertOk();
        $responseB->assertSee('Escola Beta', false);
        $responseB->assertSee('Curso Beta', false);
        $responseB->assertDontSee('Escola Alpha', false);
        $responseB->assertDontSee('Curso Alpha', false);
    }

    public function test_admin_requests_are_scoped_to_their_own_system_setting(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.alpha-admin.test', 'Admin Alpha');
        [$adminB] = $this->createTenant('cursos.beta-admin.test', 'Admin Beta');
        $studentB = User::factory()->student()->create([
            'system_setting_id' => $adminB->system_setting_id,
            'role' => UserRole::STUDENT,
            'email' => 'student-beta@example.com',
        ]);
        $courseB = $this->createPublishedCourseForTenant($adminB, 'curso-beta-admin', 'Curso Beta Admin');
        $linkB = PaymentWebhookLink::create([
            'system_setting_id' => $adminB->system_setting_id,
            'name' => 'Webhook Beta',
            'endpoint_uuid' => (string) Str::uuid(),
            'is_active' => true,
            'action_mode' => PaymentWebhookLink::ACTION_REGISTER,
            'created_by' => $adminB->id,
        ]);
        Enrollment::create([
            'system_setting_id' => $adminB->system_setting_id,
            'course_id' => $courseB->id,
            'user_id' => $studentB->id,
            'progress_percent' => 0,
            'access_status' => 'active',
        ]);

        $webhooksResponse = $this
            ->withServerVariables(['HTTP_HOST' => $tenantA->domain])
            ->actingAs($adminA)
            ->get('/admin/webhooks');

        $webhooksResponse->assertOk();
        $webhooksResponse->assertDontSee('Webhook Beta', false);

        $enrollmentsResponse = $this
            ->withServerVariables(['HTTP_HOST' => $tenantA->domain])
            ->actingAs($adminA)
            ->get('/admin/enroll');

        $enrollmentsResponse->assertOk();
        $enrollmentsResponse->assertDontSee('Curso Beta Admin', false);
        $enrollmentsResponse->assertDontSee('student-beta@example.com', false);

        $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
            ->actingAs($adminA)
            ->get("/admin/courses/{$courseB->slug}/edit")
            ->assertNotFound();
    }

    public function test_same_email_can_exist_in_different_tenants_and_login_is_domain_scoped(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.login-alpha.test', 'Login Alpha');
        [$adminB, $tenantB] = $this->createTenant('cursos.login-beta.test', 'Login Beta');

        $studentA = User::factory()->student()->create([
            'system_setting_id' => $adminA->system_setting_id,
            'email' => 'aluno@example.com',
            'password' => 'senha-alpha',
        ]);
        $studentB = User::factory()->student()->create([
            'system_setting_id' => $adminB->system_setting_id,
            'email' => 'aluno@example.com',
            'password' => 'senha-beta',
        ]);

        $this->forceTestHost($tenantA->domain)
            ->post('http://'.$tenantA->domain.'/login', [
                'email' => 'aluno@example.com',
                'password' => 'senha-alpha',
            ])
            ->assertRedirect('http://'.$tenantA->domain.'/dashboard');

        $this->assertAuthenticatedAs($studentA);

        $this->forceTestHost($tenantA->domain)
            ->post('http://'.$tenantA->domain.'/logout')
            ->assertRedirect('http://'.$tenantA->domain.'/login');

        $this->forceTestHost($tenantB->domain)
            ->post('http://'.$tenantB->domain.'/login', [
                'email' => 'aluno@example.com',
                'password' => 'senha-beta',
            ])
            ->assertRedirect('http://'.$tenantB->domain.'/dashboard');

        $this->assertAuthenticatedAs($studentB);
    }

    public function test_configured_super_admin_can_login_on_another_tenant_domain_and_manage_courses(): void
    {
        $this->createTenant('cursos.super-home.test', 'Super Home');
        [$adminB, $tenantB] = $this->createTenant('cursos.super-target.test', 'Super Target');
        $superAdmin = $this->bootstrapSuperAdmin();

        $courseB = $this->createPublishedCourseForTenant($adminB, 'curso-super-target', 'Curso Super Target');

        $this->forceTestHost($tenantB->domain)
            ->post('http://'.$tenantB->domain.'/login', [
                'email' => 'sampaio.free@gmail.com',
                'password' => 'admin123',
            ])
            ->assertRedirect('http://'.$tenantB->domain.'/admin/dashboard');

        $this->assertAuthenticatedAs($superAdmin->fresh());

        $this->forceTestHost($tenantB->domain)
            ->get('http://'.$tenantB->domain.'/admin/dashboard')
            ->assertOk();

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin->fresh())
            ->get("/admin/courses/{$courseB->slug}/edit")
            ->assertOk()
            ->assertSee('Curso Super Target', false);

        $this->forceTestHost($tenantB->domain)
            ->post('http://'.$tenantB->domain.'/logout')
            ->assertRedirect('http://'.$tenantB->domain.'/login');
    }

    public function test_bootstrap_super_admin_still_logs_in_when_configured_list_does_not_include_email(): void
    {
        config()->set('auth.super_admin_emails', ['outro-admin@example.com']);

        $this->createTenant('cursos.super-fallback-home.test', 'Super Fallback Home');
        [$adminB, $tenantB] = $this->createTenant('cursos.super-fallback-target.test', 'Super Fallback Target');
        $superAdmin = $this->bootstrapSuperAdmin();

        $courseB = $this->createPublishedCourseForTenant($adminB, 'curso-super-fallback', 'Curso Super Fallback');

        $this->forceTestHost($tenantB->domain)
            ->post('http://'.$tenantB->domain.'/login', [
                'email' => 'sampaio.free@gmail.com',
                'password' => 'admin123',
            ])
            ->assertRedirect('http://'.$tenantB->domain.'/admin/dashboard');

        $this->assertAuthenticatedAs($superAdmin->fresh());

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin->fresh())
            ->get("/admin/courses/{$courseB->slug}/edit")
            ->assertOk()
            ->assertSee('Curso Super Fallback', false);
    }

    public function test_super_admin_creates_webhook_in_current_tenant_context(): void
    {
        $this->createTenant('cursos.super-webhook-home.test', 'Super Webhook Home');
        [$adminB, $tenantB] = $this->createTenant('cursos.super-webhook-target.test', 'Super Webhook Target');
        $superAdmin = $this->bootstrapSuperAdmin();

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin->fresh())
            ->post('/admin/webhooks', [
                'name' => 'Webhook Super Admin',
                'is_active' => '1',
                'action_mode' => PaymentWebhookLink::ACTION_REGISTER,
                'security_mode' => '',
                'secret' => '',
                'signature_header' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_webhook_links', [
            'name' => 'Webhook Super Admin',
            'system_setting_id' => $adminB->system_setting_id,
            'created_by' => $superAdmin->id,
        ]);
    }

    public function test_webhook_uses_link_tenant_to_resolve_duplicate_email_and_course_ids(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.webhook-alpha.test', 'Webhook Alpha');
        [$adminB] = $this->createTenant('cursos.webhook-beta.test', 'Webhook Beta');

        $courseA = $this->createPublishedCourseForTenant($adminA, 'curso-webhook-alpha', 'Curso Webhook Alpha');
        $courseB = $this->createPublishedCourseForTenant($adminB, 'curso-webhook-beta', 'Curso Webhook Beta');

        $courseA->courseWebhookIds()->create(['webhook_id' => 'CURSO-EXT-001', 'platform' => 'Gateway A']);
        $courseB->courseWebhookIds()->create(['webhook_id' => 'CURSO-EXT-001', 'platform' => 'Gateway B']);

        $userA = User::factory()->student()->create([
            'system_setting_id' => $adminA->system_setting_id,
            'email' => 'duplicado@example.com',
            'password' => 'senha-a',
        ]);
        $userB = User::factory()->student()->create([
            'system_setting_id' => $adminB->system_setting_id,
            'email' => 'duplicado@example.com',
            'password' => 'senha-b',
        ]);

        $linkA = $this->makeWebhookLinkForTenant($adminA);

        $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
            ->postJson('/api/webhooks/in/'.$linkA->endpoint_uuid, [
                'customer' => [
                    'name' => 'Aluno Tenant A',
                    'email' => 'duplicado@example.com',
                    'whatsapp' => '5511999990001',
                ],
                'course' => [
                    'id' => 'CURSO-EXT-001',
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('enrollments', [
            'system_setting_id' => $adminA->system_setting_id,
            'course_id' => $courseA->id,
            'user_id' => $userA->id,
        ]);
        $this->assertDatabaseMissing('enrollments', [
            'system_setting_id' => $adminB->system_setting_id,
            'course_id' => $courseB->id,
            'user_id' => $userB->id,
        ]);
    }

    public function test_legacy_domain_is_not_resolved_publicly(): void
    {
        [$admin, $tenant] = $this->createTenant('cursos.legacy-block.test', 'Legacy Block');
        $tenant->forceFill([
            'domain' => 'legacy-block.test',
        ])->save();

        $this->withServerVariables(['HTTP_HOST' => 'legacy-block.test'])
            ->get('http://legacy-block.test/')
            ->assertNotFound();

        $this->withServerVariables(['HTTP_HOST' => 'legacy-block.test'])
            ->post('http://legacy-block.test/login', [
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->assertNotFound();
    }

    public function test_admin_without_domain_still_resolves_current_system_by_authenticated_user(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'sem-dominio-admin@example.com',
        ]);

        $admin->refresh();

        $this->assertNull($admin->systemSetting->domain);

        $this->actingAs($admin)
            ->get(route('admin.system.edit'))
            ->assertOk();
    }

    public function test_local_platform_host_can_resolve_single_tenant_without_custom_domain(): void
    {
        config()->set('app.url', 'http://edux.test');

        $admin = User::factory()->admin()->create([
            'email' => 'local-admin@example.com',
            'name' => 'Local Admin',
        ]);

        $admin->refresh();
        $admin->systemSetting->update([
            'escola_nome' => 'Escola Local',
        ]);

        $this->createPublishedCourseForTenant($admin, 'curso-local', 'Curso Local');

        $this->forceTestHost('edux.test')
            ->get('http://edux.test/')
            ->assertOk()
            ->assertSee('Escola Local', false)
            ->assertSee('Curso Local', false);
    }

    public function test_local_platform_host_can_resolve_explicit_edux_test_domain(): void
    {
        config()->set('app.url', 'http://edux.test');

        [$adminA, $tenantA] = $this->createTenant('cursos.alpha-local.test', 'Alpha Local');
        [$adminB] = $this->createTenant('cursos.beta-local.test', 'Beta Local');

        $tenantA->update([
            'domain' => 'edux.test',
        ]);

        $this->createPublishedCourseForTenant($adminA, 'curso-alpha-local', 'Curso Alpha Local');
        $this->createPublishedCourseForTenant($adminB, 'curso-beta-local', 'Curso Beta Local');

        $this->forceTestHost('edux.test')
            ->get('http://edux.test/')
            ->assertOk()
            ->assertSee('Alpha Local', false)
            ->assertSee('Curso Alpha Local', false)
            ->assertDontSee('Beta Local', false)
            ->assertDontSee('Curso Beta Local', false);
    }

    /**
     * @return array{0: User, 1: SystemSetting}
     */
    private function createTenant(string $domain, string $schoolName): array
    {
        $admin = User::factory()->admin()->create([
            'email' => Str::slug($schoolName).'-admin@example.com',
            'name' => $schoolName.' Admin',
        ]);

        $admin->refresh();
        $admin->systemSetting->update([
            'domain' => $domain,
            'escola_nome' => $schoolName,
        ]);

        return [$admin->fresh(), $admin->systemSetting->fresh()];
    }

    private function createPublishedCourseForTenant(User $owner, string $slug, string $title): Course
    {
        return Course::create([
            'system_setting_id' => $owner->system_setting_id,
            'owner_id' => $owner->id,
            'title' => $title,
            'slug' => $slug,
            'summary' => 'Resumo '.$title,
            'description' => 'Descrição '.$title,
            'status' => 'published',
            'duration_minutes' => 60,
            'published_at' => now(),
        ]);
    }

    private function makeWebhookLinkForTenant(User $admin): PaymentWebhookLink
    {
        $link = PaymentWebhookLink::create([
            'system_setting_id' => $admin->system_setting_id,
            'name' => 'Webhook '.$admin->id,
            'endpoint_uuid' => (string) Str::uuid(),
            'is_active' => true,
            'action_mode' => PaymentWebhookLink::ACTION_REGISTER,
            'created_by' => $admin->id,
        ]);

        foreach ([
            PaymentFieldMapping::FIELD_BUYER_NAME => 'customer.name',
            PaymentFieldMapping::FIELD_BUYER_EMAIL => 'customer.email',
            PaymentFieldMapping::FIELD_COURSE_ID => 'course.id',
            PaymentFieldMapping::FIELD_BUYER_WHATSAPP => 'customer.whatsapp',
        ] as $fieldKey => $jsonPath) {
            PaymentFieldMapping::create([
                'payment_webhook_link_id' => $link->id,
                'field_key' => $fieldKey,
                'json_path' => $jsonPath,
                'is_required' => false,
            ]);
        }

        return $link;
    }

    private function bootstrapSuperAdmin(): User
    {
        return User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', ['sampaio.free@gmail.com'])
            ->firstOrFail();
    }
}
