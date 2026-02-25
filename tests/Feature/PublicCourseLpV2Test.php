<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCheckout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseLpV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_public_course_lp_v2_renders_with_variant_tracking_markers(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $course = Course::create([
            'owner_id' => $owner->id,
            'title' => 'Auxiliar Administrativo',
            'slug' => 'auxiliar-administrativo',
            'summary' => 'Curso prático para rotina administrativa.',
            'description' => 'Descrição',
            'status' => 'published',
            'duration_minutes' => 180,
            'published_at' => now(),
        ]);

        CourseCheckout::create([
            'course_id' => $course->id,
            'nome' => 'Plano 3h',
            'descricao' => 'Plano inicial',
            'hours' => 3,
            'price' => 27.90,
            'checkout_url' => 'https://checkout.exemplo.com/auxiliar-administrativo',
            'is_active' => true,
        ]);

        $response = $this->get(route('courses.public.v2.show', $course));

        $response->assertOk();
        $response->assertSee('data-lp-variant="v2"', false);
        $response->assertSee('catalogo_course_lp_v2', false);
        $response->assertSee("url.searchParams.set('edux_lp_variant', 'v2')", false);
    }
}
