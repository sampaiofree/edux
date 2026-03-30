<?php

namespace Tests\Feature;

use App\Enums\EnrollmentAccessStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Nenhum administrador disponível para a escola selecionada.', false)
            ->assertViewHas('ownersByTenant', function (array $ownersByTenant) use ($tenantWithoutAdmins): bool {
                return ($ownersByTenant[(string) $tenantWithoutAdmins->id] ?? []) === [];
            });
    }

    public function test_super_admin_cannot_move_course_to_incompatible_tenant(): void
    {
        [$adminA, ] = $this->createTenant('cursos.sa-course-conflict-alpha.test', 'Course Conflict Alpha');
        [$adminB, $tenantB] = $this->createTenant('cursos.sa-course-conflict-beta.test', 'Course Conflict Beta');
        $superAdmin = $this->bootstrapSuperAdmin();

        $course = $this->createCourseForTenant($adminA, 'curso-conflict-move', 'Curso Conflict Move');
        $student = User::factory()->student()->create([
            'system_setting_id' => $adminA->system_setting_id,
            'email' => 'student-course-conflict@example.com',
        ]);
        $this->createEnrollmentFor($course, $student);

        $this->forceTestHost($tenantB->domain)
            ->actingAs($superAdmin)
            ->from(route('sa.courses.edit', $course->id))
            ->put(route('sa.courses.update', $course->id), [
                'system_setting_id' => $tenantB->id,
                'owner_id' => $adminB->id,
                'title' => 'Curso Conflict Move',
                'summary' => '',
                'description' => '',
                'status' => 'published',
                'duration_minutes' => 60,
                'published_at' => now()->format('Y-m-d H:i:s'),
                'promo_video_url' => '',
            ])
            ->assertRedirect(route('sa.courses.edit', $course->id))
            ->assertSessionHasErrors('system_setting_id');
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

    private function bootstrapSuperAdmin(): User
    {
        return User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', ['sampaio.free@gmail.com'])
            ->firstOrFail();
    }
}
