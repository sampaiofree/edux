<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCheckout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseLpV4Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_public_course_lp_v4_renders_with_variant_tracking_markers_and_city_aware_layout(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        $response = $this->get(route('courses.public.v4.show', $course));

        $response->assertOk();
        $response->assertSee('data-lp-variant="v4"', false);
        $response->assertSee('catalogo_course_lp_v4', false);
        $response->assertSee("url.searchParams.set('edux_lp_variant', 'v4')", false);
        $response->assertSee('Comunicado de liberação local');
        $response->assertSee('Escolha a forma de matrícula');
        $response->assertSee('Perguntas frequentes');
        $response->assertSee('sem vínculo com governo', false);
        $response->assertSee('data-checkout-source="checkout_compare_v4"', false);
        $response->assertSee('data-checkout-source="mobile_sticky_cta_v4"', false);
    }

    public function test_public_course_lp_v4_accepts_city_query_and_renders_city_name(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        $response = $this->get(route('courses.public.v4.show', $course).'?cidade=Recife');

        $response->assertOk();
        $response->assertSee('Atendimento para Recife');
        $response->assertSee('Cidade informada: Recife');
        $response->assertSee('Se você está em Recife, aproveite o valor social');
    }

    public function test_public_course_lp_v4_falls_back_without_city_query(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        $response = $this->get(route('courses.public.v4.show', $course));

        $response->assertOk();
        $response->assertDontSee('Cidade informada:');
        $response->assertSee('Aproveite o valor social e escolha sua matrícula agora.');
    }

    public function test_public_course_lp_v4_normalizes_city_query(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        $response = $this->get(route('courses.public.v4.show', $course).'?'.http_build_query([
            'cidade' => '   sAo   pAuLo  ',
        ]));

        $response->assertOk();
        $response->assertSee('Sao Paulo');
        $response->assertSee('Atendimento para Sao Paulo');
    }

    public function test_public_course_lp_v4_returns_404_for_unpublished_course(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $course = Course::create([
            'owner_id' => $owner->id,
            'title' => 'Curso Oculto',
            'slug' => 'curso-oculto',
            'summary' => 'Resumo',
            'description' => 'Descrição',
            'status' => 'draft',
            'duration_minutes' => 120,
        ]);

        $this->get(route('courses.public.v4.show', $course))
            ->assertNotFound();
    }

    /**
     * @return array{0: \App\Models\Course, 1: \App\Models\User}
     */
    private function createPublishedCourseWithCheckout(): array
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

        return [$course, $owner];
    }
}
