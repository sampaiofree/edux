<?php

namespace Tests\Feature;

use App\Enums\EnrollmentAccessStatus;
use App\Models\Certificate;
use App\Models\CertificateBranding;
use App\Models\CertificatePayment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FinalTest;
use App\Models\FinalTestAnswer;
use App\Models\FinalTestAttempt;
use App\Models\FinalTestQuestion;
use App\Models\FinalTestQuestionOption;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\Module;
use App\Models\PaymentEntitlement;
use App\Models\PaymentWebhookLink;
use App\Models\SupportWhatsappNumber;
use App\Models\SystemSetting;
use App\Models\TrackingAttribution;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_super_admin_can_access_global_dashboard_and_lists_across_tenants(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-alpha.test', 'Alpha SA');
        [$adminB, $tenantB] = $this->createTenant('cursos.sa-beta.test', 'Beta SA');

        $studentA = User::factory()->student()->create([
            'system_setting_id' => $tenantA->id,
            'email' => 'aluno-alpha-sa@example.com',
            'name' => 'Aluno Alpha SA',
        ]);
        $studentB = User::factory()->student()->create([
            'system_setting_id' => $tenantB->id,
            'email' => 'aluno-beta-sa@example.com',
            'name' => 'Aluno Beta SA',
        ]);
        $courseA = $this->createCourseForTenant($adminA, 'curso-alpha-sa', 'Curso Alpha SA');
        $courseB = $this->createCourseForTenant($adminB, 'curso-beta-sa', 'Curso Beta SA');

        $this->createEnrollmentFor($courseA, $studentA);
        $this->createEnrollmentFor($courseB, $studentB);

        $superAdmin = $this->bootstrapSuperAdmin();

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->get('/sa')
            ->assertOk()
            ->assertSee('Dashboard do super admin', false)
            ->assertSee('Curso Alpha SA', false)
            ->assertSee('Curso Beta SA', false)
            ->assertSee('Aluno Alpha SA', false)
            ->assertSee('Aluno Beta SA', false);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->get('/sa/users?role=student')
            ->assertOk()
            ->assertSee('aluno-alpha-sa@example.com', false)
            ->assertSee('aluno-beta-sa@example.com', false)
            ->assertDontSee($adminA->email, false)
            ->assertDontSee($adminB->email, false)
            ->assertSee('Novo usuário', false);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->get(route('sa.users.create'))
            ->assertOk()
            ->assertSee('Cadastrar usuário', false)
            ->assertSee('Alpha SA', false)
            ->assertSee('Beta SA', false);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->get('/sa/courses')
            ->assertOk()
            ->assertSee('Curso Alpha SA', false)
            ->assertSee('Curso Beta SA', false)
            ->assertSee($tenantA->domain, false)
            ->assertSee($tenantB->domain, false);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->get('/sa/tenants')
            ->assertOk()
            ->assertSee('Escolas e tenants', false)
            ->assertSee('Alpha SA', false)
            ->assertSee('Beta SA', false)
            ->assertSee($tenantA->domain, false)
            ->assertSee($tenantB->domain, false)
            ->assertSee($adminA->email, false)
            ->assertSee($adminB->email, false);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->get(route('sa.tenants.edit', $tenantA->id))
            ->assertOk()
            ->assertSee('Editar escola', false)
            ->assertSee('Alpha SA', false)
            ->assertSee($tenantA->domain, false);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->get('/sa/enrollments')
            ->assertOk()
            ->assertSee('Curso Alpha SA', false)
            ->assertSee('Curso Beta SA', false)
            ->assertSee('Aluno Alpha SA', false)
            ->assertSee('Aluno Beta SA', false);
    }

    public function test_only_super_admin_can_access_super_admin_area(): void
    {
        [$admin, $tenant] = $this->createTenant('cursos.sa-access.test', 'Access SA');
        $student = User::factory()->student()->create([
            'system_setting_id' => $tenant->id,
            'email' => 'aluno-access@example.com',
        ]);

        $this->forceTestHost($tenant->domain)
            ->actingAs($admin)
            ->get('/sa')
            ->assertForbidden();

        $this->forceTestHost($tenant->domain)
            ->actingAs($student)
            ->get('/sa/users')
            ->assertForbidden();

        $this->forceTestHost($tenant->domain)
            ->actingAs($admin)
            ->get(route('sa.users.create'))
            ->assertForbidden();

        $this->forceTestHost($tenant->domain)
            ->actingAs($admin)
            ->get('/sa/tenants')
            ->assertForbidden();

        $this->forceTestHost($tenant->domain)
            ->actingAs($admin)
            ->get(route('sa.tenants.edit', $tenant->id))
            ->assertForbidden();

        $this->forceTestHost($tenant->domain)
            ->actingAs($admin)
            ->get(route('sa.logs.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_list_and_download_logs(): void
    {
        [, $tenant] = $this->createTenant('cursos.sa-logs.test', 'Logs SA');
        $superAdmin = $this->bootstrapSuperAdmin();
        $logsDirectory = storage_path('logs');
        $filename = 'super-admin-log-test-'.Str::random(8).'.log';
        $path = $logsDirectory.DIRECTORY_SEPARATOR.$filename;

        File::ensureDirectoryExists($logsDirectory);
        File::put($path, "linha 1\nlinha 2\n");

        try {
            $this->forceTestHost($tenant->domain)
                ->actingAs($superAdmin)
                ->get(route('sa.logs.index'))
                ->assertOk()
                ->assertSee('Logs do sistema', false)
                ->assertSee($filename, false);

            $this->forceTestHost($tenant->domain)
                ->actingAs($superAdmin)
                ->get(route('sa.logs.download', $filename))
                ->assertOk()
                ->assertDownload($filename);
        } finally {
            File::delete($path);
        }
    }

    public function test_super_admin_can_update_user_and_change_tenant_when_no_conflict(): void
    {
        [, $tenantA] = $this->createTenant('cursos.sa-user-alpha.test', 'User Alpha');
        [, $tenantB] = $this->createTenant('cursos.sa-user-beta.test', 'User Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $user = User::factory()->student()->create([
            'system_setting_id' => $tenantA->id,
            'email' => 'move-user@example.com',
            'name' => 'Mover Usuário',
        ]);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->put(route('sa.users.update', $user->id), [
                'name' => 'Mover Usuário Atualizado',
                'email' => 'move-user@example.com',
                'role' => 'student',
                'system_setting_id' => $tenantB->id,
                'whatsapp' => '5511999999999',
                'qualification' => 'Perfil movido',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('sa.users.edit', $user->id));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'system_setting_id' => $tenantB->id,
            'name' => 'Mover Usuário Atualizado',
            'whatsapp' => '5511999999999',
        ]);
    }

    public function test_super_admin_can_create_user_for_any_tenant(): void
    {
        [, $tenantA] = $this->createTenant('cursos.sa-create-user-alpha.test', 'Create User Alpha');
        [, $tenantB] = $this->createTenant('cursos.sa-create-user-beta.test', 'Create User Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $this->forceTestHost($tenantA->domain)
            ->actingAs($superAdmin)
            ->post(route('sa.users.store'), [
                'name' => 'Usuário Global Novo',
                'email' => 'usuario.global.novo@example.com',
                'role' => 'student',
                'system_setting_id' => $tenantB->id,
                'whatsapp' => '5511988887777',
                'qualification' => 'Criado pelo super admin',
                'password' => 'senha1234',
                'password_confirmation' => 'senha1234',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'usuario.global.novo@example.com',
            'system_setting_id' => $tenantB->id,
            'role' => 'student',
            'whatsapp' => '5511988887777',
            'qualification' => 'Criado pelo super admin',
        ]);
    }

    public function test_super_admin_cannot_move_user_between_tenants_when_conflicts_exist(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-conflict-alpha.test', 'Conflict Alpha');
        [, $tenantB] = $this->createTenant('cursos.sa-conflict-beta.test', 'Conflict Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $student = User::factory()->student()->create([
            'system_setting_id' => $tenantA->id,
            'email' => 'conflicted-user@example.com',
            'name' => 'Usuário Conflitante',
        ]);
        $course = $this->createCourseForTenant($adminA, 'curso-conflict-user', 'Curso Conflict User');
        $this->createEnrollmentFor($course, $student);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->from(route('sa.users.edit', $student->id))
            ->put(route('sa.users.update', $student->id), [
                'name' => 'Usuário Conflitante',
                'email' => 'conflicted-user@example.com',
                'role' => 'student',
                'system_setting_id' => $tenantB->id,
                'whatsapp' => '',
                'qualification' => '',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('sa.users.edit', $student->id))
            ->assertSessionHasErrors('system_setting_id');
    }

    public function test_super_admin_can_edit_course_basic_fields_globally(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-course-alpha.test', 'Course Alpha');
        [, $tenantB] = $this->createTenant('cursos.sa-course-beta.test', 'Course Beta');
        $superAdmin = $this->bootstrapSuperAdmin();
        $course = $this->createCourseForTenant($adminA, 'curso-edit-global', 'Curso Edit Global');

        $response = $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->get(route('sa.courses.edit', $course->id));

        $response
            ->assertOk()
            ->assertSee('Curso Edit Global', false)
            ->assertSee('name="is_global"', false)
            ->assertViewHas('initialTenantId', (string) $tenantA->id)
            ->assertViewHas('initialOwnerId', (string) $adminA->id)
            ->assertViewHas('ownersByTenant', function (array $ownersByTenant) use ($tenantA, $tenantB, $adminA): bool {
                $tenantAOwnerIds = collect($ownersByTenant[(string) $tenantA->id] ?? [])->pluck('id');
                $tenantBOwnerIds = collect($ownersByTenant[(string) $tenantB->id] ?? [])->pluck('id');

                return $tenantAOwnerIds->contains((string) $adminA->id)
                    && $tenantBOwnerIds->doesntContain((string) $adminA->id);
            });

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->put(route('sa.courses.update', $course->id), [
                'system_setting_id' => $tenantA->id,
                'owner_id' => $adminA->id,
                'title' => 'Curso Editado Globalmente',
                'summary' => 'Resumo global',
                'description' => 'Descrição global',
                'status' => 'archived',
                'duration_minutes' => 180,
                'published_at' => now()->format('Y-m-d H:i:s'),
                'promo_video_url' => 'https://example.com/video',
                'is_global' => '1',
            ])
            ->assertRedirect(route('sa.courses.edit', $course->id));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Curso Editado Globalmente',
            'summary' => 'Resumo global',
            'status' => 'archived',
            'duration_minutes' => 180,
            'promo_video_url' => 'https://example.com/video',
            'system_setting_id' => $tenantA->id,
            'owner_id' => $adminA->id,
            'is_global' => true,
        ]);
    }

    public function test_super_admin_update_preserves_existing_global_flag_when_input_is_omitted(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-course-global-preserve.test', 'Course Global Preserve');
        $superAdmin = $this->bootstrapSuperAdmin();
        $course = $this->createCourseForTenant($adminA, 'curso-global-preserve', 'Curso Global Preserve');
        $course->forceFill(['is_global' => true])->save();

        $this->forceTestHost($tenantA->domain)
            ->actingAs($superAdmin)
            ->put(route('sa.courses.update', $course->id), [
                'system_setting_id' => $tenantA->id,
                'owner_id' => $adminA->id,
                'title' => 'Curso Global Preserve Atualizado',
                'summary' => 'Resumo preservado',
                'description' => 'Descrição preservada',
                'status' => 'published',
                'duration_minutes' => 200,
                'published_at' => now()->format('Y-m-d H:i:s'),
                'promo_video_url' => 'https://example.com/preserve',
            ])
            ->assertRedirect(route('sa.courses.edit', $course->id));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Curso Global Preserve Atualizado',
            'is_global' => true,
        ]);
    }

    public function test_course_edit_view_provides_empty_owner_group_for_tenant_without_admins(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-course-empty-alpha.test', 'Course Empty Alpha');
        $tenantWithoutAdmins = SystemSetting::create([
            'domain' => 'cursos.sa-course-sem-admin.test',
            'escola_nome' => 'Sem Admin SA',
        ]);
        $superAdmin = $this->bootstrapSuperAdmin();
        $course = $this->createCourseForTenant($adminA, 'curso-empty-owner-group', 'Curso Empty Owner Group');

        $this->forceTestHost($tenantA->domain)
            ->actingAs($superAdmin)
            ->get(route('sa.courses.edit', $course->id))
            ->assertOk()
            ->assertSee('Nenhum responsável de cursos disponível para a escola selecionada.', false)
            ->assertViewHas('ownersByTenant', function (array $ownersByTenant) use ($tenantWithoutAdmins): bool {
                return ($ownersByTenant[(string) $tenantWithoutAdmins->id] ?? []) === [];
            });
    }

    public function test_super_admin_can_transfer_course_to_another_tenant_and_clear_incompatible_support_whatsapp(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-course-transfer-alpha.test', 'Course Transfer Alpha');
        [$adminB, $tenantB] = $this->createTenant('cursos.sa-course-conflict-beta.test', 'Course Conflict Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $supportNumber = SupportWhatsappNumber::create([
            'system_setting_id' => $tenantA->id,
            'label' => 'Suporte Alpha',
            'whatsapp' => '5511999990000',
            'description' => 'Atendimento Alpha',
            'is_active' => true,
            'position' => 1,
        ]);
        $course = $this->createCourseForTenant($adminA, 'curso-transfer-move', 'Curso Transfer Move');
        $course->forceFill([
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_SPECIFIC,
            'support_whatsapp_number_id' => $supportNumber->id,
            'is_global' => true,
        ])->save();
        CertificateBranding::create([
            'system_setting_id' => $tenantA->id,
            'course_id' => $course->id,
        ]);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->put(route('sa.courses.update', $course->id), [
                'system_setting_id' => $tenantB->id,
                'owner_id' => $adminB->id,
                'title' => 'Curso Transferido',
                'summary' => 'Resumo após transferência',
                'description' => 'Descrição após transferência',
                'status' => 'archived',
                'duration_minutes' => 120,
                'published_at' => now()->format('Y-m-d H:i:s'),
                'promo_video_url' => 'https://example.com/transfer',
                'is_global' => '1',
            ])
            ->assertRedirect(route('sa.courses.edit', $course->id))
            ->assertSessionHas('status', 'Curso transferido para a nova escola com matrículas e histórico educacional.');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'system_setting_id' => $tenantB->id,
            'owner_id' => $adminB->id,
            'title' => 'Curso Transferido',
            'status' => 'archived',
            'support_whatsapp_number_id' => null,
            'is_global' => true,
        ]);
        $this->assertDatabaseHas('certificate_brandings', [
            'course_id' => $course->id,
            'system_setting_id' => $tenantB->id,
        ]);
    }

    public function test_super_admin_can_transfer_course_enrollments_and_merge_history_into_existing_target_student(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-course-history-alpha.test', 'Course History Alpha');
        [$adminB, $tenantB] = $this->createTenant('cursos.sa-course-history-beta.test', 'Course History Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $course = $this->createCourseForTenant($adminA, 'curso-history-move', 'Curso History Move');
        CertificateBranding::create([
            'system_setting_id' => $tenantA->id,
            'course_id' => $course->id,
        ]);

        $module = $this->createModuleForCourse($course, 'Módulo Transferido');
        $lessonOne = $this->createLessonForModule($module, 'Aula 1', 1);
        $lessonTwo = $this->createLessonForModule($module, 'Aula 2', 2);
        $lessonOtherCourse = $this->createLessonForModule(
            $this->createModuleForCourse($this->createCourseForTenant($adminA, 'curso-nao-transferido', 'Curso Não Transferido'), 'Outro módulo'),
            'Aula Outro Curso',
            1,
        );

        $finalTest = $this->createFinalTestForCourse($course);
        $question = FinalTestQuestion::create([
            'final_test_id' => $finalTest->id,
            'title' => 'Pergunta',
            'statement' => 'Qual a resposta?',
            'position' => 1,
            'weight' => 1,
        ]);
        $option = FinalTestQuestionOption::create([
            'final_test_question_id' => $question->id,
            'label' => 'Opção A',
            'is_correct' => true,
            'position' => 1,
        ]);

        $sourceStudent = User::factory()->student()->create([
            'system_setting_id' => $tenantA->id,
            'email' => 'student-transfer-existing@example.com',
            'name' => 'Aluno Origem',
        ]);
        $targetStudent = User::factory()->student()->create([
            'system_setting_id' => $tenantB->id,
            'email' => 'student-transfer-existing@example.com',
            'name' => 'Aluno Destino',
        ]);

        $sourceEnrollment = $this->createEnrollmentFor($course, $sourceStudent);
        $sourceEnrollment->forceFill([
            'progress_percent' => 90,
            'completed_at' => Carbon::parse('2026-03-01 10:00:00'),
            'access_status' => EnrollmentAccessStatus::BLOCKED->value,
            'access_block_reason' => 'inadimplente',
            'access_blocked_at' => Carbon::parse('2026-03-04 09:00:00'),
            'manual_override' => true,
            'manual_override_by' => $adminA->id,
            'manual_override_at' => Carbon::parse('2026-03-05 10:00:00'),
        ])->save();

        $existingTargetEnrollment = $this->createEnrollmentFor($course, $targetStudent);
        $existingTargetEnrollment->forceFill([
            'system_setting_id' => $tenantB->id,
            'progress_percent' => 45,
            'completed_at' => Carbon::parse('2026-03-10 10:00:00'),
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
            'access_block_reason' => null,
            'access_blocked_at' => null,
            'manual_override' => false,
            'manual_override_by' => null,
            'manual_override_at' => null,
        ])->save();

        LessonCompletion::create([
            'lesson_id' => $lessonOne->id,
            'user_id' => $sourceStudent->id,
            'completed_at' => Carbon::parse('2026-03-02 09:00:00'),
        ]);
        LessonCompletion::create([
            'lesson_id' => $lessonTwo->id,
            'user_id' => $sourceStudent->id,
            'completed_at' => Carbon::parse('2026-03-03 09:00:00'),
        ]);
        LessonCompletion::create([
            'lesson_id' => $lessonTwo->id,
            'user_id' => $targetStudent->id,
            'completed_at' => Carbon::parse('2026-03-04 09:00:00'),
        ]);
        LessonCompletion::create([
            'lesson_id' => $lessonOtherCourse->id,
            'user_id' => $sourceStudent->id,
            'completed_at' => Carbon::parse('2026-03-06 09:00:00'),
        ]);

        $duplicateAttemptTime = Carbon::parse('2026-03-07 12:00:00');
        $sourceDuplicateAttempt = FinalTestAttempt::create([
            'final_test_id' => $finalTest->id,
            'user_id' => $sourceStudent->id,
            'score' => 80,
            'passed' => true,
            'started_at' => $duplicateAttemptTime->copy()->subMinutes(20),
            'submitted_at' => $duplicateAttemptTime,
            'attempted_at' => $duplicateAttemptTime,
        ]);
        FinalTestAnswer::create([
            'final_test_attempt_id' => $sourceDuplicateAttempt->id,
            'final_test_question_id' => $question->id,
            'final_test_question_option_id' => $option->id,
            'is_correct' => true,
        ]);
        $targetDuplicateAttempt = FinalTestAttempt::create([
            'final_test_id' => $finalTest->id,
            'user_id' => $targetStudent->id,
            'score' => 80,
            'passed' => true,
            'started_at' => $duplicateAttemptTime->copy()->subMinutes(20),
            'submitted_at' => $duplicateAttemptTime,
            'attempted_at' => $duplicateAttemptTime,
        ]);
        FinalTestAnswer::create([
            'final_test_attempt_id' => $targetDuplicateAttempt->id,
            'final_test_question_id' => $question->id,
            'final_test_question_option_id' => $option->id,
            'is_correct' => true,
        ]);
        $sourceUniqueAttempt = FinalTestAttempt::create([
            'final_test_id' => $finalTest->id,
            'user_id' => $sourceStudent->id,
            'score' => 95,
            'passed' => true,
            'started_at' => Carbon::parse('2026-03-08 08:00:00'),
            'submitted_at' => Carbon::parse('2026-03-08 08:20:00'),
            'attempted_at' => Carbon::parse('2026-03-08 08:20:00'),
        ]);
        FinalTestAnswer::create([
            'final_test_attempt_id' => $sourceUniqueAttempt->id,
            'final_test_question_id' => $question->id,
            'final_test_question_option_id' => $option->id,
            'is_correct' => true,
        ]);

        Certificate::create([
            'course_id' => $course->id,
            'user_id' => $sourceStudent->id,
            'number' => 'CERT-SOURCE-TRANSFER',
            'issued_at' => Carbon::parse('2026-03-09 09:00:00'),
            'front_content' => 'Frente origem',
            'back_content' => 'Verso origem',
        ]);
        Certificate::create([
            'course_id' => $course->id,
            'user_id' => $targetStudent->id,
            'number' => 'CERT-TARGET-TRANSFER',
            'issued_at' => Carbon::parse('2026-03-10 09:00:00'),
            'front_content' => 'Frente destino',
            'back_content' => 'Verso destino',
        ]);

        $webhookLink = PaymentWebhookLink::create([
            'system_setting_id' => $tenantA->id,
            'name' => 'Link Alpha',
            'endpoint_uuid' => (string) Str::uuid(),
            'is_active' => true,
            'action_mode' => PaymentWebhookLink::ACTION_REGISTER,
            'created_by' => $adminA->id,
        ]);
        $paymentEntitlement = PaymentEntitlement::create([
            'user_id' => $sourceStudent->id,
            'course_id' => $course->id,
            'payment_webhook_link_id' => $webhookLink->id,
            'external_tx_id' => 'TX-123',
            'external_product_id' => 'PROD-123',
            'state' => 'active',
            'last_event_at' => now(),
        ]);
        $certificatePayment = CertificatePayment::create([
            'user_id' => $sourceStudent->id,
            'course_id' => $course->id,
            'status' => 'paid',
            'amount' => 199.90,
            'currency' => 'BRL',
            'transaction_reference' => 'CERT-123',
            'paid_at' => now(),
        ]);
        $trackingEvent = TrackingEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'user_id' => $sourceStudent->id,
            'course_id' => $course->id,
            'event_name' => 'course_viewed',
            'occurred_at' => now(),
        ]);
        $trackingAttribution = TrackingAttribution::create([
            'user_id' => $sourceStudent->id,
            'course_id' => $course->id,
            'attribution_model' => 'last_touch',
        ]);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->put(route('sa.courses.update', $course->id), [
                'system_setting_id' => $tenantB->id,
                'owner_id' => $adminB->id,
                'title' => 'Curso History Move',
                'summary' => 'Resumo history move',
                'description' => 'Descrição history move',
                'status' => 'published',
                'duration_minutes' => 90,
                'published_at' => now()->format('Y-m-d H:i:s'),
                'promo_video_url' => 'https://example.com/history',
            ])
            ->assertRedirect(route('sa.courses.edit', $course->id));

        $this->assertDatabaseMissing('enrollments', ['id' => $sourceEnrollment->id]);
        $this->assertDatabaseHas('enrollments', [
            'id' => $existingTargetEnrollment->id,
            'course_id' => $course->id,
            'user_id' => $targetStudent->id,
            'system_setting_id' => $tenantB->id,
            'progress_percent' => 90,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
            'manual_override' => true,
            'manual_override_by' => $adminA->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'id' => $existingTargetEnrollment->id,
            'completed_at' => '2026-03-01 10:00:00',
            'manual_override_at' => '2026-03-05 10:00:00',
            'access_block_reason' => null,
            'access_blocked_at' => null,
        ]);

        $this->assertDatabaseHas('lesson_completions', [
            'lesson_id' => $lessonOne->id,
            'user_id' => $targetStudent->id,
        ]);
        $this->assertDatabaseHas('lesson_completions', [
            'lesson_id' => $lessonTwo->id,
            'user_id' => $targetStudent->id,
        ]);
        $this->assertDatabaseMissing('lesson_completions', [
            'lesson_id' => $lessonOne->id,
            'user_id' => $sourceStudent->id,
        ]);
        $this->assertDatabaseHas('lesson_completions', [
            'lesson_id' => $lessonOtherCourse->id,
            'user_id' => $sourceStudent->id,
        ]);

        $this->assertDatabaseMissing('final_test_attempts', ['id' => $sourceDuplicateAttempt->id]);
        $this->assertDatabaseHas('final_test_attempts', [
            'id' => $sourceUniqueAttempt->id,
            'user_id' => $targetStudent->id,
        ]);
        $this->assertSame(
            2,
            FinalTestAttempt::query()
                ->where('final_test_id', $finalTest->id)
                ->where('user_id', $targetStudent->id)
                ->count()
        );

        $this->assertDatabaseMissing('certificates', ['number' => 'CERT-SOURCE-TRANSFER']);
        $this->assertDatabaseHas('certificates', [
            'number' => 'CERT-TARGET-TRANSFER',
            'user_id' => $targetStudent->id,
        ]);

        $this->assertDatabaseHas('payment_entitlements', [
            'id' => $paymentEntitlement->id,
            'user_id' => $sourceStudent->id,
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('certificate_payments', [
            'id' => $certificatePayment->id,
            'user_id' => $sourceStudent->id,
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('tracking_events', [
            'id' => $trackingEvent->id,
            'user_id' => $sourceStudent->id,
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('tracking_attributions', [
            'id' => $trackingAttribution->id,
            'user_id' => $sourceStudent->id,
            'course_id' => $course->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $sourceStudent->id,
            'system_setting_id' => $tenantA->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $targetStudent->id,
            'system_setting_id' => $tenantB->id,
        ]);
    }

    public function test_super_admin_can_transfer_course_by_cloning_student_when_target_tenant_has_no_matching_email(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-course-clone-alpha.test', 'Course Clone Alpha');
        [$adminB, $tenantB] = $this->createTenant('cursos.sa-course-clone-beta.test', 'Course Clone Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $course = $this->createCourseForTenant($adminA, 'curso-clone-transfer', 'Curso Clone Transfer');
        $module = $this->createModuleForCourse($course, 'Módulo Clone');
        $lesson = $this->createLessonForModule($module, 'Aula Clone', 1);

        $sourceStudent = User::factory()->student()->create([
            'system_setting_id' => $tenantA->id,
            'email' => 'clone-transfer-student@example.com',
            'name' => 'Aluno Clone',
            'display_name' => 'Clone Display',
            'whatsapp' => '5511988880000',
            'qualification' => 'Qualificado',
            'profile_photo_path' => 'profile-photos/aluno-clone.jpg',
            'email_verified_at' => Carbon::parse('2026-03-01 09:00:00'),
        ]);
        $enrollment = $this->createEnrollmentFor($course, $sourceStudent);
        LessonCompletion::create([
            'lesson_id' => $lesson->id,
            'user_id' => $sourceStudent->id,
            'completed_at' => Carbon::parse('2026-03-02 10:00:00'),
        ]);
        Certificate::create([
            'course_id' => $course->id,
            'user_id' => $sourceStudent->id,
            'number' => 'CERT-CLONE-TRANSFER',
            'issued_at' => Carbon::parse('2026-03-03 10:00:00'),
            'front_content' => 'Frente clone',
            'back_content' => 'Verso clone',
        ]);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->put(route('sa.courses.update', $course->id), [
                'system_setting_id' => $tenantB->id,
                'owner_id' => $adminB->id,
                'title' => 'Curso Clone Transfer',
                'summary' => 'Resumo clone',
                'description' => 'Descrição clone',
                'status' => 'published',
                'duration_minutes' => 75,
                'published_at' => now()->format('Y-m-d H:i:s'),
                'promo_video_url' => '',
            ])
            ->assertRedirect(route('sa.courses.edit', $course->id));

        $clonedStudent = User::withoutGlobalScopes()
            ->where('system_setting_id', $tenantB->id)
            ->whereRaw('LOWER(email) = ?', ['clone-transfer-student@example.com'])
            ->first();

        $this->assertNotNull($clonedStudent);
        $this->assertNotSame($sourceStudent->id, $clonedStudent->id);
        $this->assertSame('Aluno Clone', $clonedStudent->name);
        $this->assertSame('Clone Display', $clonedStudent->display_name);
        $this->assertSame('5511988880000', $clonedStudent->whatsapp);
        $this->assertSame('Qualificado', $clonedStudent->qualification);
        $this->assertSame('profile-photos/aluno-clone.jpg', $clonedStudent->profile_photo_path);
        $this->assertSame('student', $clonedStudent->role->value ?? $clonedStudent->role);
        $this->assertSame($sourceStudent->getAuthPassword(), $clonedStudent->getAuthPassword());

        $this->assertDatabaseHas('users', [
            'id' => $sourceStudent->id,
            'system_setting_id' => $tenantA->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'user_id' => $clonedStudent->id,
            'system_setting_id' => $tenantB->id,
        ]);
        $this->assertDatabaseHas('lesson_completions', [
            'lesson_id' => $lesson->id,
            'user_id' => $clonedStudent->id,
        ]);
        $this->assertDatabaseMissing('lesson_completions', [
            'lesson_id' => $lesson->id,
            'user_id' => $sourceStudent->id,
        ]);
        $this->assertDatabaseHas('certificates', [
            'number' => 'CERT-CLONE-TRANSFER',
            'user_id' => $clonedStudent->id,
        ]);
    }

    public function test_super_admin_can_update_enrollment_when_tenant_user_and_course_are_consistent(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-enroll-alpha.test', 'Enroll Alpha');
        [, $tenantB] = $this->createTenant('cursos.sa-enroll-beta.test', 'Enroll Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $student = User::factory()->student()->create([
            'system_setting_id' => $tenantA->id,
            'email' => 'student-enroll-update@example.com',
        ]);
        $course = $this->createCourseForTenant($adminA, 'curso-enroll-update', 'Curso Enroll Update');
        $enrollment = $this->createEnrollmentFor($course, $student);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->put(route('sa.enrollments.update', $enrollment->id), [
                'system_setting_id' => $tenantA->id,
                'course_id' => $course->id,
                'user_id' => $student->id,
                'progress_percent' => 55,
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'access_status' => EnrollmentAccessStatus::BLOCKED->value,
                'access_block_reason' => 'manual-check',
                'access_blocked_at' => now()->format('Y-m-d H:i:s'),
                'manual_override' => '1',
            ])
            ->assertRedirect(route('sa.enrollments.edit', $enrollment->id));

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'system_setting_id' => $tenantA->id,
            'progress_percent' => 55,
            'access_status' => EnrollmentAccessStatus::BLOCKED->value,
            'access_block_reason' => 'manual-check',
            'manual_override' => true,
        ]);
    }

    public function test_super_admin_cannot_update_enrollment_when_selected_tenant_conflicts(): void
    {
        [$adminA, ] = $this->createTenant('cursos.sa-enroll-conflict-alpha.test', 'Enroll Conflict Alpha');
        [, $tenantB] = $this->createTenant('cursos.sa-enroll-conflict-beta.test', 'Enroll Conflict Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $student = User::factory()->student()->create([
            'system_setting_id' => $adminA->system_setting_id,
            'email' => 'student-enroll-conflict@example.com',
        ]);
        $course = $this->createCourseForTenant($adminA, 'curso-enroll-conflict', 'Curso Enroll Conflict');
        $enrollment = $this->createEnrollmentFor($course, $student);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->from(route('sa.enrollments.edit', $enrollment->id))
            ->put(route('sa.enrollments.update', $enrollment->id), [
                'system_setting_id' => $tenantB->id,
                'course_id' => $course->id,
                'user_id' => $student->id,
                'progress_percent' => 10,
                'completed_at' => '',
                'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                'access_block_reason' => '',
                'access_blocked_at' => '',
                'manual_override' => '',
            ])
            ->assertRedirect(route('sa.enrollments.edit', $enrollment->id))
            ->assertSessionHasErrors(['course_id', 'user_id']);
    }

    public function test_super_admin_can_delete_user_course_and_enrollment(): void
    {
        [$adminA, $tenantA] = $this->createTenant('cursos.sa-delete-alpha.test', 'Delete Alpha');
        [, $tenantB] = $this->createTenant('cursos.sa-delete-beta.test', 'Delete Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $userToDelete = User::factory()->student()->create([
            'system_setting_id' => $tenantA->id,
            'email' => 'delete-user@example.com',
        ]);
        $courseToDelete = $this->createCourseForTenant($adminA, 'curso-delete-sa', 'Curso Delete SA');
        $studentForEnrollment = User::factory()->student()->create([
            'system_setting_id' => $tenantA->id,
            'email' => 'delete-enrollment-student@example.com',
        ]);
        $courseForEnrollment = $this->createCourseForTenant($adminA, 'curso-enrollment-delete-sa', 'Curso Enrollment Delete SA');
        $enrollmentToDelete = $this->createEnrollmentFor($courseForEnrollment, $studentForEnrollment);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->delete(route('sa.users.destroy', $userToDelete->id))
            ->assertRedirect(route('sa.users.index'));

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->delete(route('sa.courses.destroy', $courseToDelete->id))
            ->assertRedirect(route('sa.courses.index'));

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->delete(route('sa.enrollments.destroy', $enrollmentToDelete->id))
            ->assertRedirect(route('sa.enrollments.index'));

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
        $this->assertDatabaseMissing('courses', ['id' => $courseToDelete->id]);
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollmentToDelete->id]);
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

    private function createCourseForTenant(User $owner, string $slug, string $title): Course
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

    private function createEnrollmentFor(Course $course, User $user): Enrollment
    {
        return Enrollment::create([
            'system_setting_id' => $course->system_setting_id,
            'course_id' => $course->id,
            'user_id' => $user->id,
            'progress_percent' => 0,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);
    }

    private function createModuleForCourse(Course $course, string $title, int $position = 1): Module
    {
        return Module::create([
            'course_id' => $course->id,
            'title' => $title,
            'description' => 'Descrição '.$title,
            'position' => $position,
        ]);
    }

    private function createLessonForModule(Module $module, string $title, int $position = 1): Lesson
    {
        return Lesson::create([
            'module_id' => $module->id,
            'title' => $title,
            'content' => 'Conteúdo '.$title,
            'video_url' => 'https://example.com/'.Str::slug($title),
            'duration_minutes' => 10,
            'position' => $position,
        ]);
    }

    private function createFinalTestForCourse(Course $course): FinalTest
    {
        return FinalTest::create([
            'course_id' => $course->id,
            'title' => 'Teste final '.$course->title,
            'instructions' => 'Responda tudo.',
            'passing_score' => 70,
            'max_attempts' => 3,
            'duration_minutes' => 30,
        ]);
    }

    private function bootstrapSuperAdmin(): User
    {
        return User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', ['sampaio.free@gmail.com'])
            ->firstOrFail();
    }
}
