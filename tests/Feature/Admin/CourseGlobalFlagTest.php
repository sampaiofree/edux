<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseGlobalFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_create_page_does_not_render_global_flag_field(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('courses.create'));

        $response->assertOk();
        $response->assertDontSee('name="is_global"', false);
    }

    public function test_admin_edit_page_does_not_render_global_flag_field(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->makeCourse($admin);

        $response = $this->actingAs($admin)->get(route('courses.edit', $course));

        $response->assertOk();
        $response->assertDontSee('name="is_global"', false);
    }

    public function test_admin_store_ignores_manual_is_global_input(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('courses.store'), [
            'title' => 'Curso Sem Global',
            'summary' => 'Resumo breve',
            'description' => 'Descricao do curso',
            'status' => 'draft',
            'duration_minutes' => 180,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
            'is_global' => 1,
        ]);

        $course = Course::query()->where('title', 'Curso Sem Global')->firstOrFail();

        $response->assertRedirect(route('courses.edit', $course));
        $response->assertSessionHasNoErrors();

        $this->assertFalse((bool) $course->fresh()->is_global);
    }

    public function test_admin_update_ignores_manual_is_global_input_and_preserves_existing_value(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->makeCourse($admin, [
            'title' => 'Curso Global Existente',
            'slug' => 'curso-global-existente',
            'is_global' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('courses.update.post', $course), [
            'title' => 'Curso Atualizado',
            'summary' => 'Resumo novo',
            'description' => 'Descricao nova',
            'status' => 'published',
            'duration_minutes' => 240,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
            'is_global' => 0,
        ]);

        $response->assertRedirect(route('courses.edit', $course->fresh()));
        $response->assertSessionHasNoErrors();

        $course->refresh();

        $this->assertSame('Curso Atualizado', $course->title);
        $this->assertTrue((bool) $course->is_global);
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
            'duration_minutes' => 120,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ], $overrides));
    }
}
