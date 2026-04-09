<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAccessModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_create_page_renders_access_mode_field(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('courses.create'));

        $response->assertOk();
        $response->assertSee('name="access_mode"', false);
        $response->assertSee('Modo de acesso', false);
    }

    public function test_admin_edit_page_renders_access_mode_field(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->makeCourse($admin);

        $response = $this->actingAs($admin)->get(route('courses.edit', $course));

        $response->assertOk();
        $response->assertSee('name="access_mode"', false);
        $response->assertSee('Gratuito', false);
    }

    public function test_admin_can_store_course_with_free_access_mode(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('courses.store'), [
            'title' => 'Curso Gratuito Admin',
            'summary' => 'Resumo breve',
            'description' => 'Descricao do curso',
            'status' => 'draft',
            'access_mode' => Course::ACCESS_MODE_FREE,
            'duration_minutes' => 180,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ]);

        $course = Course::query()->where('title', 'Curso Gratuito Admin')->firstOrFail();

        $response->assertRedirect(route('courses.edit', $course));
        $response->assertSessionHasNoErrors();

        $this->assertSame(Course::ACCESS_MODE_FREE, $course->access_mode);
    }

    public function test_admin_can_toggle_access_mode_between_free_and_paid(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->makeCourse($admin, [
            'title' => 'Curso Acesso Alternado',
            'slug' => 'curso-acesso-alternado',
            'access_mode' => Course::ACCESS_MODE_FREE,
        ]);

        $response = $this->actingAs($admin)->post(route('courses.update.post', $course), [
            'title' => 'Curso Acesso Alternado',
            'summary' => 'Resumo novo',
            'description' => 'Descricao nova',
            'status' => 'published',
            'access_mode' => Course::ACCESS_MODE_PAID,
            'duration_minutes' => 240,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ]);

        $response->assertRedirect(route('courses.edit', $course->fresh()));
        $response->assertSessionHasNoErrors();

        $this->assertSame(Course::ACCESS_MODE_PAID, $course->fresh()->access_mode);
    }

    public function test_admin_store_defaults_access_mode_to_paid_when_input_is_omitted(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('courses.store'), [
            'title' => 'Curso Sem Access Mode',
            'summary' => 'Resumo breve',
            'description' => 'Descricao do curso',
            'status' => 'draft',
            'duration_minutes' => 180,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ]);

        $course = Course::query()->where('title', 'Curso Sem Access Mode')->firstOrFail();

        $response->assertRedirect(route('courses.edit', $course));
        $response->assertSessionHasNoErrors();

        $this->assertSame(Course::ACCESS_MODE_PAID, $course->access_mode);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCourse(User $owner, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'owner_id' => $owner->id,
            'title' => 'Curso Teste',
            'slug' => 'curso-teste',
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'draft',
            'access_mode' => Course::ACCESS_MODE_PAID,
            'duration_minutes' => 120,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ], $overrides));
    }
}
