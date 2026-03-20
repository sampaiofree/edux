<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\PublicCatalog;
use App\Models\Course;
use App\Models\CourseCheckout;
use App\Models\Enrollment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_guest_can_access_public_home_without_redirect(): void
    {
        $this->createPublishedCourse('informatica-basica', 'Informática Básica', 29.90);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Escolher meu curso agora!', false);
        $response->assertSee('id="lista-cursos"', false);
        $response->assertSee('4 motivos para começar uma nova etapa com mais preparo', false);
        $response->assertDontSee('Visão geral da plataforma', false);
        $response->assertDontSee('Entrar', false);
        $response->assertDontSee('Ir para o painel', false);
        $response->assertDontSee('href="'.route('courses.public.index').'"', false);
        $response->assertDontSee('Abrir catálogo com busca', false);
        $response->assertDontSee('http-equiv="refresh"', false);
    }

    public function test_authenticated_user_sees_same_standalone_home_without_auth_ctas(): void
    {
        $user = User::factory()->admin()->create();
        $this->createPublishedCourse('auxiliar-rh', 'Auxiliar de RH', 24.90);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Escolher meu curso agora!', false);
        $response->assertDontSee('Entrar', false);
        $response->assertDontSee('Ir para o painel', false);
        $response->assertDontSee('href="'.route('courses.public.index').'"', false);
        $response->assertDontSee('Abrir catálogo com busca', false);
    }

    public function test_home_lists_only_published_courses_and_omits_price_and_enrollment_metrics(): void
    {
        $published = $this->createPublishedCourse('auxiliar-administrativo', 'Auxiliar Administrativo', 19.90);
        $draft = $this->createCourse([
            'slug' => 'curso-rascunho',
            'title' => 'Curso Rascunho',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $studentA = User::factory()->student()->create();
        $studentB = User::factory()->student()->create();

        Enrollment::create([
            'course_id' => $published->id,
            'user_id' => $studentA->id,
        ]);
        Enrollment::create([
            'course_id' => $published->id,
            'user_id' => $studentB->id,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee($published->title);
        $response->assertSee(route('courses.public.show', $published), false);
        $response->assertDontSee($draft->title);
        $response->assertDontSee('A partir de R$ 19,90', false);
        $response->assertDontSee('2 alunos matriculados', false);
        $response->assertDontSee('2 matrículas registradas', false);
        $response->assertDontSee('2 matrículas', false);
    }

    public function test_catalog_context_search_and_load_more_continue_working(): void
    {
        $olderCourse = $this->createPublishedCourse('curso-antigo', 'Curso Antigo da Lista', 12.90);

        for ($i = 1; $i <= 9; $i++) {
            $this->createPublishedCourse('curso-'.$i, 'Curso '.$i, 10 + $i);
        }

        $specialCourse = $this->createPublishedCourse('catalogo-especial', 'Catalogo Especial Unico', 55.90);

        Livewire::test(PublicCatalog::class, ['context' => 'catalog'])
            ->set('search', $specialCourse->title)
            ->assertSee($specialCourse->title)
            ->assertDontSee($olderCourse->title);

        Livewire::test(PublicCatalog::class, ['context' => 'catalog'])
            ->assertDontSee($olderCourse->title)
            ->call('loadMore')
            ->assertSee($olderCourse->title);
    }

    public function test_bonus_section_uses_placeholder_without_asset_and_local_image_when_configured(): void
    {
        $this->createPublishedCourse('monitor-escolar', 'Monitor Escolar', 17.90);

        $responseWithoutAsset = $this->get('/');

        $responseWithoutAsset->assertOk();
        $responseWithoutAsset->assertSee('Materiais extras quando o curso oferecer suporte adicional', false);
        $responseWithoutAsset->assertDontSee('/storage/uploads/carta-estagio.png', false);

        SystemSetting::current()->forceFill([
            'carta_estagio' => 'uploads/carta-estagio.png',
        ])->save();

        $responseWithAsset = $this->get('/');

        $responseWithAsset->assertOk();
        $responseWithAsset->assertSee('Carta de estágio configurada na plataforma', false);
        $responseWithAsset->assertSee('/storage/uploads/carta-estagio.png', false);
    }

    public function test_home_does_not_depend_on_reference_or_external_thumbnail_assets(): void
    {
        $this->createPublishedCourse('atendimento', 'Atendimento ao Cliente', 21.90);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('jovemempreendedor.org', false);
        $response->assertDontSee('i.ytimg.com', false);
        $response->assertDontSee('img.youtube.com', false);
    }

    private function createPublishedCourse(string $slug, string $title, float $price): Course
    {
        return $this->createCourse([
            'slug' => $slug,
            'title' => $title,
            'status' => 'published',
            'published_at' => now(),
            'checkout_price' => $price,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourse(array $overrides = []): Course
    {
        $checkoutPrice = isset($overrides['checkout_price']) ? (float) $overrides['checkout_price'] : 29.90;
        unset($overrides['checkout_price']);

        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        /** @var Course $course */
        $course = Course::create(array_merge([
            'owner_id' => $owner->id,
            'title' => 'Curso Base',
            'slug' => 'curso-base',
            'summary' => 'Resumo do curso para a home pública.',
            'description' => 'Descrição do curso para a home pública.',
            'status' => 'draft',
            'duration_minutes' => 180,
            'published_at' => null,
        ], $overrides));

        CourseCheckout::create([
            'course_id' => $course->id,
            'nome' => 'Plano principal',
            'descricao' => 'Opção padrão',
            'hours' => 3,
            'price' => $checkoutPrice,
            'checkout_url' => 'https://checkout.exemplo.com/'.$course->slug,
            'is_active' => true,
        ]);

        return $course;
    }
}
