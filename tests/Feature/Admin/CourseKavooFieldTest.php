<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseKavooFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_create_page_does_not_render_kavoo_field(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('courses.create'));

        $response->assertOk();
        $response->assertDontSee('ID Kavoo', false);
        $response->assertDontSee('name="kavoo_id"', false);
    }

    public function test_admin_edit_page_does_not_render_kavoo_field(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->makeCourse($admin, [
            'kavoo_id' => 321,
        ]);

        $response = $this->actingAs($admin)->get(route('courses.edit', $course));

        $response->assertOk();
        $response->assertDontSee('ID Kavoo', false);
        $response->assertDontSee('name="kavoo_id"', false);
    }

    public function test_admin_store_ignores_manual_kavoo_id_input(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('courses.store'), [
            'title' => 'Curso Sem Kavoo',
            'summary' => 'Resumo breve',
            'description' => 'Descricao do curso',
            'status' => 'draft',
            'duration_minutes' => 180,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
            'kavoo_id' => 987654,
        ]);

        $course = Course::query()->where('title', 'Curso Sem Kavoo')->firstOrFail();

        $response->assertRedirect(route('courses.edit', $course));
        $response->assertSessionHasNoErrors();
        $this->assertNull($course->fresh()->kavoo_id);
        $this->assertSame('Curso Sem Kavoo', $course->fresh()->title);
    }

    public function test_admin_update_ignores_manual_kavoo_id_input_and_preserves_existing_value(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->makeCourse($admin, [
            'title' => 'Curso Original',
            'slug' => 'curso-original',
            'kavoo_id' => 555,
        ]);

        $response = $this->actingAs($admin)->post(route('courses.update.post', $course), [
            'title' => 'Curso Atualizado',
            'summary' => 'Resumo novo',
            'description' => 'Descricao nova',
            'status' => 'published',
            'duration_minutes' => 240,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
            'kavoo_id' => 999,
        ]);

        $response->assertRedirect(route('courses.edit', $course->fresh()));
        $response->assertSessionHasNoErrors();

        $course->refresh();

        $this->assertSame('Curso Atualizado', $course->title);
        $this->assertSame(555, $course->kavoo_id);
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
