<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCheckout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseLpIconsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_public_course_lp_renders_colored_icon_markers(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $course = Course::create([
            'owner_id' => $owner->id,
            'title' => 'Informática Básica',
            'slug' => 'informatica-basica',
            'summary' => 'Curso para começar do zero.',
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
            'price' => 17.90,
            'checkout_url' => 'https://checkout.exemplo.com/informatica-basica',
            'is_active' => true,
        ]);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('data-lp-icon="clock"', false);
        $response->assertSee('data-lp-icon="list-check"', false);
        $response->assertSee('data-lp-icon="wallet"', false);
        $response->assertSee('data-lp-icon="badge-check"', false);
    }
}
