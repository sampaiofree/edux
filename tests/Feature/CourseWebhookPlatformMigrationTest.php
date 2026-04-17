<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseWebhookPlatformMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_04_17_113000_require_platform_on_curso_webhook_ids_table.php';

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_migration_backfills_null_and_blank_platforms_to_legacy(): void
    {
        $migration = $this->migration();
        $migration->down();

        $course = $this->makeCourse();

        DB::table('curso_webhook_ids')->insert([
            [
                'course_id' => $course->id,
                'system_setting_id' => $course->system_setting_id,
                'webhook_id' => 'NULL-PLATFORM',
                'platform' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_id' => $course->id,
                'system_setting_id' => $course->system_setting_id,
                'webhook_id' => 'BLANK-PLATFORM',
                'platform' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $migration->up();

        $this->assertDatabaseHas('curso_webhook_ids', [
            'course_id' => $course->id,
            'webhook_id' => 'NULL-PLATFORM',
            'platform' => 'legacy',
        ]);
        $this->assertDatabaseHas('curso_webhook_ids', [
            'course_id' => $course->id,
            'webhook_id' => 'BLANK-PLATFORM',
            'platform' => 'legacy',
        ]);
    }

    public function test_migration_enforces_platform_as_not_null(): void
    {
        $migration = $this->migration();
        $migration->down();
        $migration->up();

        $course = $this->makeCourse();

        $this->expectException(QueryException::class);

        DB::table('curso_webhook_ids')->insert([
            'course_id' => $course->id,
            'system_setting_id' => $course->system_setting_id,
            'webhook_id' => 'NULL-AFTER-MIGRATION',
            'platform' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION_PATH);
    }

    private function makeCourse(): Course
    {
        $tenant = SystemSetting::create([
            'domain' => 'migration-course-webhook-platform.test',
            'escola_nome' => 'Tenant Migration',
        ]);
        $admin = User::factory()->admin()->create([
            'system_setting_id' => $tenant->id,
        ]);

        return Course::create([
            'system_setting_id' => $tenant->id,
            'owner_id' => $admin->id,
            'title' => 'Curso Migration',
            'slug' => 'curso-migration-'.str()->lower(str()->random(6)),
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'draft',
            'duration_minutes' => 60,
            'published_at' => now(),
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ]);
    }
}
