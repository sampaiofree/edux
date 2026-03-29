<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class JovemEmpreendedorTenantBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_03_29_160000_backfill_jovem_empreendedor_orphan_tenant_records.php';

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_migration_backfills_orphan_courses_related_records_and_only_unambiguous_users(): void
    {
        $targetTenant = SystemSetting::create([
            'domain' => 'cursos.jovemempreendedor.org',
            'escola_nome' => 'Jovem Empreendedor',
        ]);
        $otherTenant = SystemSetting::create([
            'domain' => 'cursos.outro-tenant.test',
            'escola_nome' => 'Outro Tenant',
        ]);

        $otherTenantAdminId = $this->insertUser([
            'name' => 'Admin Outro Tenant',
            'email' => 'admin.outro@example.com',
            'role' => UserRole::ADMIN->value,
            'system_setting_id' => $otherTenant->id,
        ]);
        $orphanOwnerId = $this->insertUser([
            'name' => 'Owner Orfao',
            'email' => 'owner.orfao@example.com',
        ]);
        $unambiguousStudentId = $this->insertUser([
            'name' => 'Aluno Sem Ambiguidade',
            'email' => 'aluno.unico@example.com',
        ]);
        $ambiguousStudentId = $this->insertUser([
            'name' => 'Aluno Ambiguo',
            'email' => 'aluno.ambiguo@example.com',
        ]);

        $sharedSupportNumberId = $this->insertSupportWhatsappNumber([
            'label' => 'Atendimento Compartilhado',
            'whatsapp' => '5562995772922',
            'description' => 'Numero legado compartilhado',
            'system_setting_id' => null,
        ]);

        $orphanCourseId = $this->insertCourse([
            'owner_id' => $orphanOwnerId,
            'title' => 'Curso Orfao',
            'slug' => 'curso-orfao',
            'support_whatsapp_number_id' => $sharedSupportNumberId,
            'system_setting_id' => null,
        ]);
        $otherTenantCourseId = $this->insertCourse([
            'owner_id' => $otherTenantAdminId,
            'title' => 'Curso Outro Tenant',
            'slug' => 'curso-outro-tenant',
            'support_whatsapp_number_id' => $sharedSupportNumberId,
            'system_setting_id' => $otherTenant->id,
        ]);

        $unambiguousEnrollmentId = $this->insertEnrollment([
            'course_id' => $orphanCourseId,
            'user_id' => $unambiguousStudentId,
            'system_setting_id' => null,
        ]);
        $ambiguousTargetEnrollmentId = $this->insertEnrollment([
            'course_id' => $orphanCourseId,
            'user_id' => $ambiguousStudentId,
            'system_setting_id' => null,
        ]);
        $this->insertEnrollment([
            'course_id' => $otherTenantCourseId,
            'user_id' => $ambiguousStudentId,
            'system_setting_id' => null,
        ]);

        $courseBrandingId = $this->insertCertificateBranding([
            'course_id' => $orphanCourseId,
            'front_background_path' => 'certificates/front.png',
            'back_background_path' => 'certificates/back.png',
            'system_setting_id' => null,
        ]);

        $this->runMigration();

        $orphanCourse = DB::table('courses')->where('id', $orphanCourseId)->first();
        $otherTenantCourse = DB::table('courses')->where('id', $otherTenantCourseId)->first();
        $newSupportNumber = DB::table('support_whatsapp_numbers')->where('id', $orphanCourse->support_whatsapp_number_id)->first();

        $this->assertSame($targetTenant->id, $orphanCourse->system_setting_id);
        $this->assertNotSame($otherTenantCourse->support_whatsapp_number_id, $orphanCourse->support_whatsapp_number_id);
        $this->assertSame($targetTenant->id, $newSupportNumber->system_setting_id);

        $this->assertDatabaseHas('enrollments', [
            'id' => $unambiguousEnrollmentId,
            'system_setting_id' => $targetTenant->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'id' => $ambiguousTargetEnrollmentId,
            'system_setting_id' => $targetTenant->id,
        ]);
        $this->assertDatabaseHas('certificate_brandings', [
            'id' => $courseBrandingId,
            'system_setting_id' => $targetTenant->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $orphanOwnerId,
            'system_setting_id' => $targetTenant->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $unambiguousStudentId,
            'system_setting_id' => $targetTenant->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $ambiguousStudentId,
            'system_setting_id' => null,
        ]);

        $this->assertSame(2, DB::table('support_whatsapp_numbers')->count());

        $this->runMigration();

        $this->assertSame(2, DB::table('support_whatsapp_numbers')->count());
        $this->assertSame(
            $newSupportNumber->id,
            DB::table('courses')->where('id', $orphanCourseId)->value('support_whatsapp_number_id')
        );
    }

    public function test_migration_is_a_no_op_when_no_orphan_courses_exist_and_target_tenant_is_missing(): void
    {
        $systemSettingsBefore = DB::table('system_settings')->count();
        $coursesBefore = DB::table('courses')->count();

        $this->runMigration();

        $this->assertSame($systemSettingsBefore, DB::table('system_settings')->count());
        $this->assertSame($coursesBefore, DB::table('courses')->count());
    }

    public function test_migration_throws_when_orphan_courses_exist_but_target_tenant_is_missing(): void
    {
        $ownerId = $this->insertUser([
            'name' => 'Owner Sem Tenant',
            'email' => 'owner.sem-tenant@example.com',
        ]);

        $this->insertCourse([
            'owner_id' => $ownerId,
            'title' => 'Curso Sem Tenant',
            'slug' => 'curso-sem-tenant',
            'system_setting_id' => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Target tenant "cursos.jovemempreendedor.org" not found');

        $this->runMigration();
    }

    private function runMigration(): void
    {
        $migration = require base_path(self::MIGRATION_PATH);
        $migration->up();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertUser(array $attributes): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => 'Usuario Teste',
            'email' => uniqid('user-', true).'@example.com',
            'display_name' => null,
            'password' => Hash::make('password'),
            'role' => UserRole::STUDENT->value,
            'system_setting_id' => null,
            'whatsapp' => null,
            'qualification' => null,
            'profile_photo_path' => null,
            'email_verified_at' => now(),
            'remember_token' => null,
            'name_change_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertCourse(array $attributes): int
    {
        return DB::table('courses')->insertGetId(array_merge([
            'owner_id' => null,
            'title' => 'Curso Teste',
            'slug' => uniqid('curso-', true),
            'summary' => null,
            'description' => null,
            'status' => 'draft',
            'duration_minutes' => null,
            'published_at' => null,
            'cover_image_path' => null,
            'promo_video_url' => null,
            'certificate_payment_url' => null,
            'certificate_price' => null,
            'kavoo_id' => null,
            'atuacao' => null,
            'oquefaz' => null,
            'support_whatsapp_mode' => 'specific',
            'support_whatsapp_number_id' => null,
            'system_setting_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertEnrollment(array $attributes): int
    {
        return DB::table('enrollments')->insertGetId(array_merge([
            'course_id' => null,
            'user_id' => null,
            'progress_percent' => 0,
            'completed_at' => null,
            'access_status' => 'active',
            'access_block_reason' => null,
            'access_blocked_at' => null,
            'manual_override' => false,
            'manual_override_by' => null,
            'manual_override_at' => null,
            'system_setting_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertCertificateBranding(array $attributes): int
    {
        return DB::table('certificate_brandings')->insertGetId(array_merge([
            'course_id' => null,
            'front_background_path' => null,
            'back_background_path' => null,
            'system_setting_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertSupportWhatsappNumber(array $attributes): int
    {
        return DB::table('support_whatsapp_numbers')->insertGetId(array_merge([
            'label' => 'Suporte Teste',
            'whatsapp' => '5500000000000',
            'description' => null,
            'is_active' => true,
            'position' => 1,
            'system_setting_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
