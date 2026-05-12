<?php

namespace Tests\Feature\Admin;

use App\Enums\EnrollmentAccessStatus;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnrollmentCertificateIssuanceBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_enrollment_certificate_workload_column_and_helper_use_course_default_or_override(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'student-workload-helper@example.com',
        ]);
        $course = Course::create([
            'system_setting_id' => $admin->system_setting_id,
            'owner_id' => $admin->id,
            'title' => 'Curso Workload Helper',
            'slug' => 'curso-workload-helper',
            'summary' => 'Resumo Curso Workload Helper',
            'description' => 'Descricao Curso Workload Helper',
            'status' => 'published',
            'duration_minutes' => 90,
            'published_at' => now(),
        ]);

        $this->assertTrue(Schema::hasColumn('enrollments', 'certificate_workload_minutes'));

        $enrollment = Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'progress_percent' => 0,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);

        $this->assertNull($enrollment->certificate_workload_minutes);
        $this->assertSame(90, $enrollment->effectiveCertificateWorkloadMinutes($course));

        $enrollment->forceFill(['certificate_workload_minutes' => 240])->save();

        $this->assertSame(240, $enrollment->fresh()->effectiveCertificateWorkloadMinutes($course));
        $this->assertSame('4', $enrollment->fresh()->certificateWorkloadHoursForInput());
    }

    public function test_admin_can_block_certificate_issuance_on_enrollment(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'student-cert-block-admin@example.com',
        ]);
        $course = Course::create([
            'system_setting_id' => $admin->system_setting_id,
            'owner_id' => $admin->id,
            'title' => 'Curso Cert Block Admin',
            'slug' => 'curso-cert-block-admin',
            'summary' => 'Resumo Curso Cert Block Admin',
            'description' => 'Descricao Curso Cert Block Admin',
            'status' => 'published',
            'duration_minutes' => 60,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.enroll.store'), [
                'course_id' => $course->id,
                'user_id' => $student->id,
                'progress_percent' => 100,
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                'access_block_reason' => '',
                'access_blocked_at' => '',
                'manual_override' => '',
                'certificate_issuance_blocked' => '1',
                'certificate_issuance_block_reason' => 'pendência documental',
            ])
            ->assertRedirect(route('admin.enroll.index'));

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        $this->assertTrue($enrollment->certificate_issuance_blocked);
        $this->assertSame('pendência documental', $enrollment->certificate_issuance_block_reason);
        $this->assertNull($enrollment->certificate_workload_minutes);
        $this->assertSame(60, $enrollment->effectiveCertificateWorkloadMinutes($course));

        $this->actingAs($admin)
            ->get(route('admin.enroll.index'))
            ->assertOk()
            ->assertSee('Certificado bloqueado');
    }

    public function test_admin_can_save_and_clear_certificate_workload_on_enrollment(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'student-cert-workload-admin@example.com',
        ]);
        $course = Course::create([
            'system_setting_id' => $admin->system_setting_id,
            'owner_id' => $admin->id,
            'title' => 'Curso Cert Workload Admin',
            'slug' => 'curso-cert-workload-admin',
            'summary' => 'Resumo Curso Cert Workload Admin',
            'description' => 'Descricao Curso Cert Workload Admin',
            'status' => 'published',
            'duration_minutes' => 120,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.enroll.store'), [
                'course_id' => $course->id,
                'user_id' => $student->id,
                'progress_percent' => 100,
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                'access_block_reason' => '',
                'access_blocked_at' => '',
                'manual_override' => '',
                'certificate_issuance_blocked' => '',
                'certificate_issuance_block_reason' => '',
                'certificate_workload_hours' => '3.5',
            ])
            ->assertRedirect(route('admin.enroll.index'));

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        $this->assertSame(210, $enrollment->certificate_workload_minutes);
        $this->assertSame(210, $enrollment->effectiveCertificateWorkloadMinutes($course));
        $this->assertSame('3.5', $enrollment->certificateWorkloadHoursForInput());

        $this->actingAs($admin)
            ->put(route('admin.enroll.update', $enrollment), [
                'course_id' => $course->id,
                'user_id' => $student->id,
                'progress_percent' => 100,
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'access_status' => EnrollmentAccessStatus::ACTIVE->value,
                'access_block_reason' => '',
                'access_blocked_at' => '',
                'manual_override' => '',
                'certificate_issuance_blocked' => '',
                'certificate_issuance_block_reason' => '',
                'certificate_workload_hours' => '',
            ])
            ->assertRedirect(route('admin.enroll.index'));

        $enrollment->refresh();

        $this->assertNull($enrollment->certificate_workload_minutes);
        $this->assertSame(120, $enrollment->effectiveCertificateWorkloadMinutes($course));
    }
}
