<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\LessonsManager;
use App\Livewire\Admin\ModulesManager;
use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\SupportWhatsappNumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherBackofficeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_teacher_dashboard_shows_restricted_menu_and_blocks_global_import(): void
    {
        $admin = $this->defaultTenantAdmin();
        $teacher = $this->createTeacherForTenant($admin, [
            'email' => 'teacher-dashboard@example.com',
        ]);

        $this->createCourse($admin);

        $response = $this->actingAs($teacher)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Cursos cadastrados', false);
        $response->assertDontSee('Importar curso', false);
        $response->assertDontSee('Usuarios', false);
        $response->assertDontSee('Sistema', false);
        $response->assertDontSee('Tracking', false);

        Livewire::test(Dashboard::class)
            ->call('openImportModal')
            ->assertForbidden();
    }

    public function test_teacher_can_create_course_and_it_is_owned_by_the_teacher(): void
    {
        $admin = $this->defaultTenantAdmin();
        $teacher = $this->createTeacherForTenant($admin, [
            'email' => 'teacher-create-course@example.com',
        ]);
        $supportWhatsappNumber = SupportWhatsappNumber::create([
            'system_setting_id' => $admin->system_setting_id,
            'label' => 'Atendimento principal',
            'whatsapp' => '(11) 99999-9999',
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)->post(route('courses.store'), [
            'title' => 'Curso criado pelo professor',
            'summary' => 'Resumo novo',
            'description' => 'Descrição nova',
            'atuacao' => 'Escolas;Secretarias',
            'oquefaz' => 'Atende;Organiza',
            'status' => 'draft',
            'access_mode' => Course::ACCESS_MODE_FREE,
            'duration_minutes' => 75,
            'published_at' => '2026-04-09 10:00:00',
            'promo_video_url' => 'https://example.com/video-professor',
            'owner_id' => $admin->id,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_SPECIFIC,
            'support_whatsapp_number_id' => $supportWhatsappNumber->id,
            'curso_webhook_ids' => [
                [
                    'webhook_id' => 'forged-webhook-id',
                    'platform' => 'Hotmart',
                ],
            ],
        ]);

        $course = Course::query()->where('title', 'Curso criado pelo professor')->firstOrFail();

        $response->assertRedirect(route('courses.edit', $course));

        $this->assertSame($teacher->id, $course->owner_id);
        $this->assertSame($teacher->system_setting_id, $course->system_setting_id);
        $this->assertSame(Course::ACCESS_MODE_FREE, $course->access_mode);
        $this->assertSame(Course::SUPPORT_WHATSAPP_MODE_ALL, $course->support_whatsapp_mode);
        $this->assertNull($course->support_whatsapp_number_id);
        $this->assertCount(0, $course->courseWebhookIds);
    }

    public function test_teacher_can_update_same_tenant_course_but_operational_fields_remain_unchanged(): void
    {
        $admin = $this->defaultTenantAdmin();
        $teacher = $this->createTeacherForTenant($admin, [
            'email' => 'teacher-update-course@example.com',
        ]);
        $supportWhatsappNumber = SupportWhatsappNumber::create([
            'system_setting_id' => $admin->system_setting_id,
            'label' => 'Atendimento secundário',
            'whatsapp' => '(11) 98888-8888',
            'position' => 1,
            'is_active' => true,
        ]);
        $course = $this->createCourse($admin, [
            'title' => 'Curso do admin',
            'slug' => 'curso-do-admin',
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_SPECIFIC,
            'support_whatsapp_number_id' => $supportWhatsappNumber->id,
            'summary' => 'Resumo antigo',
            'promo_video_url' => 'https://example.com/video-antigo',
        ]);
        $course->courseWebhookIds()->create([
            'webhook_id' => 'webhook-antigo',
            'platform' => 'Eduzz',
        ]);
        $course->certificateBranding()->create([
            'system_setting_id' => $course->system_setting_id,
            'front_background_path' => 'certificate-backgrounds/front-antigo.png',
            'back_background_path' => 'certificate-backgrounds/back-antigo.png',
        ]);

        $response = $this->actingAs($teacher)->get(route('courses.edit', $course));

        $response->assertOk();
        $response->assertSee('name="access_mode"', false);
        $response->assertDontSee('Atendimento via WhatsApp', false);
        $response->assertDontSee('IDs de webhook', false);
        $response->assertDontSee('Fundos personalizados do certificado', false);
        $response->assertDontSee('Gerenciar teste final', false);

        $updateResponse = $this->actingAs($teacher)->post(route('courses.update.post', $course), [
            'title' => 'Curso do admin atualizado',
            'summary' => 'Resumo atualizado pelo professor',
            'description' => 'Descrição atualizada pelo professor',
            'atuacao' => 'Empresas;Escolas',
            'oquefaz' => 'Organiza;Executa',
            'status' => 'published',
            'access_mode' => Course::ACCESS_MODE_FREE,
            'duration_minutes' => 120,
            'published_at' => '2026-04-10 11:00:00',
            'promo_video_url' => 'https://example.com/video-novo',
            'owner_id' => $teacher->id,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
            'support_whatsapp_number_id' => null,
            'curso_webhook_ids' => [
                [
                    'webhook_id' => 'webhook-forjado',
                    'platform' => 'Hotmart',
                ],
            ],
            'remove_certificate_front_background' => 1,
            'remove_certificate_back_background' => 1,
        ]);

        $course = Course::query()
            ->with(['courseWebhookIds', 'certificateBranding'])
            ->findOrFail($course->id);

        $updateResponse->assertRedirect(route('courses.edit', $course));

        $this->assertSame('Curso do admin atualizado', $course->title);
        $this->assertSame('Resumo atualizado pelo professor', $course->summary);
        $this->assertSame('https://example.com/video-novo', $course->promo_video_url);
        $this->assertSame('published', $course->status);
        $this->assertSame(Course::ACCESS_MODE_FREE, $course->access_mode);
        $this->assertSame($admin->id, $course->owner_id);
        $this->assertSame(Course::SUPPORT_WHATSAPP_MODE_SPECIFIC, $course->support_whatsapp_mode);
        $this->assertSame($supportWhatsappNumber->id, $course->support_whatsapp_number_id);
        $this->assertCount(1, $course->courseWebhookIds);
        $this->assertSame('webhook-antigo', $course->courseWebhookIds->first()->webhook_id);
        $this->assertInstanceOf(CertificateBranding::class, $course->certificateBranding);
        $this->assertSame('certificate-backgrounds/front-antigo.png', $course->certificateBranding->front_background_path);
        $this->assertSame('certificate-backgrounds/back-antigo.png', $course->certificateBranding->back_background_path);
    }

    public function test_teacher_can_manage_modules_lessons_and_lesson_import_for_same_tenant_course(): void
    {
        $admin = $this->defaultTenantAdmin();
        $teacher = $this->createTeacherForTenant($admin, [
            'email' => 'teacher-modules@example.com',
        ]);
        $course = $this->createCourse($admin, [
            'title' => 'Curso com modulos',
            'slug' => 'curso-com-modulos',
        ]);

        $this->actingAs($teacher)->get(route('courses.modules.edit', $course))->assertOk();

        Livewire::test(ModulesManager::class, ['courseId' => $course->id])
            ->set('form.title', 'Módulo 1')
            ->set('form.description', 'Descrição do módulo')
            ->call('saveModule')
            ->assertHasNoErrors();

        $module = $course->fresh()->modules()->firstOrFail();

        Livewire::test(LessonsManager::class, ['moduleId' => $module->id])
            ->set('form.title', 'Aula 1')
            ->set('form.content', 'Conteúdo inicial')
            ->call('saveLesson')
            ->assertHasNoErrors()
            ->set('importSource', 'text')
            ->set('csvText', "titulo,conteudo,duracao\nAula importada,Conteúdo CSV,15")
            ->call('analyzeCsv')
            ->call('importLessons')
            ->assertHasNoErrors();

        $module->refresh()->load('lessons');

        $this->assertCount(2, $module->lessons);
        $this->assertTrue($module->lessons->contains(fn ($lesson) => $lesson->title === 'Aula 1'));
        $this->assertTrue($module->lessons->contains(fn ($lesson) => $lesson->title === 'Aula importada'));
    }

    public function test_teacher_can_delete_course_from_the_same_tenant(): void
    {
        $admin = $this->defaultTenantAdmin();
        $teacher = $this->createTeacherForTenant($admin, [
            'email' => 'teacher-delete@example.com',
        ]);
        $course = $this->createCourse($admin, [
            'title' => 'Curso para excluir',
            'slug' => 'curso-para-excluir',
        ]);

        $response = $this->actingAs($teacher)->delete(route('courses.destroy', $course));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertNull(Course::query()->find($course->id));
    }

    public function test_teacher_cannot_access_admin_only_pages(): void
    {
        $admin = $this->defaultTenantAdmin();
        $teacher = $this->createTeacherForTenant($admin, [
            'email' => 'teacher-forbidden@example.com',
        ]);
        $course = $this->createCourse($admin, [
            'title' => 'Curso bloqueado',
            'slug' => 'curso-bloqueado',
        ]);

        $routes = [
            route('admin.users.index'),
            route('admin.system.edit'),
            route('admin.categories.index'),
            route('certificates.branding.edit'),
            route('admin.certificates.generated.index'),
            route('admin.notifications.index'),
            route('admin.email.create'),
            route('admin.webhooks.index'),
            route('admin.support-whatsapp.index'),
            route('admin.enroll.index'),
            route('admin.tracking.index'),
            route('courses.final-test.edit', $course),
        ];

        foreach ($routes as $path) {
            $this->actingAs($teacher)->get($path)->assertForbidden();
        }
    }

    public function test_teacher_cannot_access_backoffice_of_another_tenant(): void
    {
        $adminA = $this->defaultTenantAdmin();
        $teacherA = $this->createTeacherForTenant($adminA, [
            'email' => 'teacher-cross-tenant@example.com',
        ]);

        $adminB = $this->createAdminForTenant(
            ['email' => 'tenant-b-admin@example.com'],
            [
                'domain' => 'cursos.tenant-b.example.test',
                'escola_nome' => 'Escola Tenant B',
            ]
        );
        $response = $this->actingAs($teacherA)
            ->forceTestHost('cursos.tenant-b.example.test')
            ->get('http://cursos.tenant-b.example.test/admin/dashboard');

        $response->assertForbidden();
    }

    private function createCourse(User $owner, array $attributes = []): Course
    {
        $suffix = bin2hex(random_bytes(4));

        return Course::create(array_merge([
            'system_setting_id' => $owner->system_setting_id,
            'owner_id' => $owner->id,
            'title' => 'Curso '.$suffix,
            'slug' => 'curso-'.$suffix,
            'summary' => 'Resumo '.$suffix,
            'description' => 'Descrição '.$suffix,
            'status' => 'draft',
            'access_mode' => Course::ACCESS_MODE_PAID,
            'duration_minutes' => 60,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ], $attributes));
    }
}
