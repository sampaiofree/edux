<?php

namespace Tests\Feature\Admin;

use App\Enums\EnrollmentAccessStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIndexAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_can_view_user_filters_and_export_link(): void
    {
        $admin = $this->defaultTenantAdmin();
        $this->createCourseForTenant($admin, 'curso-base', 'Curso Base');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Cadastro inicial');
        $response->assertSee('Cadastro final');
        $response->assertSee('Curso matriculado');
        $response->assertSee('Todos os cursos');
        $response->assertSee('Exportar CSV');
    }

    public function test_admin_can_filter_users_by_created_from(): void
    {
        $admin = $this->defaultTenantAdmin();
        $olderUser = $this->createStudentForTenant($admin, [
            'name' => 'Older User',
            'email' => 'older@example.com',
            'created_at' => Carbon::parse('2026-01-10 08:00:00'),
            'updated_at' => Carbon::parse('2026-01-10 08:00:00'),
        ]);
        $recentUser = $this->createStudentForTenant($admin, [
            'name' => 'Recent User',
            'email' => 'recent@example.com',
            'created_at' => Carbon::parse('2026-02-10 12:00:00'),
            'updated_at' => Carbon::parse('2026-02-10 12:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'created_from' => '2026-02-01',
        ]));

        $response->assertOk();
        $response->assertSee($recentUser->name);
        $response->assertDontSee($olderUser->name);
    }

    public function test_admin_can_filter_users_by_created_to(): void
    {
        $admin = $this->defaultTenantAdmin();
        $olderUser = $this->createStudentForTenant($admin, [
            'name' => 'Before Limit',
            'email' => 'before-limit@example.com',
            'created_at' => Carbon::parse('2026-01-15 09:00:00'),
            'updated_at' => Carbon::parse('2026-01-15 09:00:00'),
        ]);
        $recentUser = $this->createStudentForTenant($admin, [
            'name' => 'After Limit',
            'email' => 'after-limit@example.com',
            'created_at' => Carbon::parse('2026-03-15 09:00:00'),
            'updated_at' => Carbon::parse('2026-03-15 09:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'created_to' => '2026-01-31',
        ]));

        $response->assertOk();
        $response->assertSee($olderUser->name);
        $response->assertDontSee($recentUser->name);
    }

    public function test_admin_can_filter_users_by_created_range_including_boundaries(): void
    {
        $admin = $this->defaultTenantAdmin();
        $boundaryStart = $this->createStudentForTenant($admin, [
            'name' => 'Boundary Start',
            'email' => 'boundary-start@example.com',
            'created_at' => Carbon::parse('2026-03-01 00:00:00'),
            'updated_at' => Carbon::parse('2026-03-01 00:00:00'),
        ]);
        $boundaryEnd = $this->createStudentForTenant($admin, [
            'name' => 'Boundary End',
            'email' => 'boundary-end@example.com',
            'created_at' => Carbon::parse('2026-03-02 23:59:59'),
            'updated_at' => Carbon::parse('2026-03-02 23:59:59'),
        ]);
        $outsideBefore = $this->createStudentForTenant($admin, [
            'name' => 'Outside Before',
            'email' => 'outside-before@example.com',
            'created_at' => Carbon::parse('2026-02-28 23:59:59'),
            'updated_at' => Carbon::parse('2026-02-28 23:59:59'),
        ]);
        $outsideAfter = $this->createStudentForTenant($admin, [
            'name' => 'Outside After',
            'email' => 'outside-after@example.com',
            'created_at' => Carbon::parse('2026-03-03 00:00:00'),
            'updated_at' => Carbon::parse('2026-03-03 00:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'created_from' => '2026-03-01',
            'created_to' => '2026-03-02',
        ]));

        $response->assertOk();
        $response->assertSee($boundaryStart->name);
        $response->assertSee($boundaryEnd->name);
        $response->assertDontSee($outsideBefore->name);
        $response->assertDontSee($outsideAfter->name);
    }

    public function test_admin_can_filter_users_by_course_without_duplicates(): void
    {
        $admin = $this->defaultTenantAdmin();
        $courseA = $this->createCourseForTenant($admin, 'curso-a', 'Curso A');
        $courseB = $this->createCourseForTenant($admin, 'curso-b', 'Curso B');
        $multiCourseUser = $this->createStudentForTenant($admin, [
            'name' => 'Multi Course User',
            'email' => 'multi-course@example.com',
        ]);
        $otherCourseUser = $this->createStudentForTenant($admin, [
            'name' => 'Other Course User',
            'email' => 'other-course@example.com',
        ]);

        $this->enrollUserInCourse($multiCourseUser, $courseA);
        $this->enrollUserInCourse($multiCourseUser, $courseB);
        $this->enrollUserInCourse($otherCourseUser, $courseB);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'course_id' => $courseA->id,
        ]));

        $response->assertOk();
        $response->assertSee($multiCourseUser->name);
        $response->assertDontSee($otherCourseUser->name);
        $this->assertSame(1, substr_count($response->getContent(), $multiCourseUser->name));
    }

    public function test_admin_can_combine_search_date_and_course_filters(): void
    {
        $admin = $this->defaultTenantAdmin();
        $courseA = $this->createCourseForTenant($admin, 'curso-filtro', 'Curso Filtro');
        $courseB = $this->createCourseForTenant($admin, 'curso-errado', 'Curso Errado');

        $matchingUser = $this->createStudentForTenant($admin, [
            'name' => 'Exact Filter Match',
            'email' => 'exact-filter-match@example.com',
            'created_at' => Carbon::parse('2026-04-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-04-10 10:00:00'),
        ]);
        $wrongSearch = $this->createStudentForTenant($admin, [
            'name' => 'Different Search',
            'email' => 'different-search@example.com',
            'created_at' => Carbon::parse('2026-04-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-04-10 10:00:00'),
        ]);
        $wrongDate = $this->createStudentForTenant($admin, [
            'name' => 'Exact Old Date',
            'email' => 'exact-old-date@example.com',
            'created_at' => Carbon::parse('2026-03-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-10 10:00:00'),
        ]);
        $wrongCourse = $this->createStudentForTenant($admin, [
            'name' => 'Exact Wrong Course',
            'email' => 'exact-wrong-course@example.com',
            'created_at' => Carbon::parse('2026-04-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-04-10 10:00:00'),
        ]);

        $this->enrollUserInCourse($matchingUser, $courseA);
        $this->enrollUserInCourse($wrongSearch, $courseA);
        $this->enrollUserInCourse($wrongDate, $courseA);
        $this->enrollUserInCourse($wrongCourse, $courseB);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Exact',
            'created_from' => '2026-04-01',
            'created_to' => '2026-04-30',
            'course_id' => $courseA->id,
        ]));

        $response->assertOk();
        $response->assertSee($matchingUser->name);
        $response->assertDontSee($wrongSearch->name);
        $response->assertDontSee($wrongDate->name);
        $response->assertDontSee($wrongCourse->name);
    }

    public function test_admin_can_export_filtered_users_csv(): void
    {
        $admin = $this->defaultTenantAdmin();
        $course = $this->createCourseForTenant($admin, 'curso-export', 'Curso Export');

        foreach (range(1, 21) as $index) {
            $user = $this->createStudentForTenant($admin, [
                'name' => sprintf('CSV User %02d', $index),
                'email' => sprintf('csv-user-%02d@example.com', $index),
                'created_at' => Carbon::parse('2026-04-12 10:00:00'),
                'updated_at' => Carbon::parse('2026-04-12 10:00:00'),
            ]);

            $this->enrollUserInCourse($user, $course);
        }

        $excludedUser = $this->createStudentForTenant($admin, [
            'name' => 'CSV Excluded User',
            'email' => 'csv-excluded@example.com',
            'created_at' => Carbon::parse('2026-04-12 10:00:00'),
            'updated_at' => Carbon::parse('2026-04-12 10:00:00'),
        ]);
        $otherCourse = $this->createCourseForTenant($admin, 'curso-outro-export', 'Curso Outro Export');
        $this->enrollUserInCourse($excludedUser, $otherCourse);

        $response = $this->actingAs($admin)->get(route('admin.users.export', [
            'course_id' => $course->id,
            'created_from' => '2026-04-01',
            'created_to' => '2026-04-30',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $this->stripUtf8Bom($response->streamedContent());

        $this->assertStringContainsString("id;nome;email;papel;whatsapp;data_cadastro", $csv);
        $this->assertStringContainsString('CSV User 01', $csv);
        $this->assertStringContainsString('CSV User 21', $csv);
        $this->assertStringNotContainsString($excludedUser->name, $csv);
    }

    public function test_teacher_cannot_access_user_list_or_export(): void
    {
        $admin = $this->defaultTenantAdmin();
        $teacher = $this->createTeacherForTenant($admin, [
            'email' => 'teacher-users-export@example.com',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.users.export'))
            ->assertForbidden();
    }

    private function createCourseForTenant(User $owner, string $slug, string $title): Course
    {
        return Course::create([
            'system_setting_id' => $owner->system_setting_id,
            'owner_id' => $owner->id,
            'title' => $title,
            'slug' => $slug,
            'summary' => 'Resumo do curso',
            'description' => 'Descricao do curso',
            'status' => 'published',
        ]);
    }

    private function enrollUserInCourse(User $user, Course $course): Enrollment
    {
        return Enrollment::create([
            'system_setting_id' => $user->system_setting_id,
            'course_id' => $course->id,
            'user_id' => $user->id,
            'progress_percent' => 0,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);
    }

    private function stripUtf8Bom(string $content): string
    {
        return str_starts_with($content, "\xEF\xBB\xBF")
            ? substr($content, 3)
            : $content;
    }
}
