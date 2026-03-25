<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\PublicCatalog;
use App\Models\Course;
use App\Models\CourseCheckout;
use App\Models\Enrollment;
use App\Models\SupportWhatsappNumber;
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
        $response->assertSee('QUERO ME INSCREVER AGORA', false);
        $response->assertSee('id="lista-cursos"', false);
        $response->assertSee('4 motivos para você mudar de vida hoje', false);
        $response->assertDontSee('Visão geral da plataforma', false);
        $response->assertDontSee('Entrar', false);
        $response->assertDontSee('Ir para o painel', false);
        $response->assertDontSee('href="'.route('courses.public.index').'"', false);
        $response->assertDontSee('Abrir catálogo com busca', false);
        $response->assertDontSee('http-equiv="refresh"', false);
    }

    public function test_home_displays_city_name_from_query_string_above_banner_heading(): void
    {
        $this->createPublishedCourse('informatica-basica', 'Informática Básica', 29.90);

        $response = $this->get('/?cidade=salvador');

        $response->assertOk();
        $response->assertDontSee('w3-city-fixed-top', false);
        $response->assertSeeInOrder([
            'Salvador',
            'Inscrições abertas para o Programa Nacional de Capacitação Profissional.',
        ], false);
    }

    public function test_home_renders_local_reason_card_images(): void
    {
        $this->createPublishedCourse('informatica-basica', 'Informática Básica', 29.90);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('/images/home/reasons/qualificacao.webp', false);
        $response->assertSee('/images/home/reasons/semexperiencia.webp', false);
        $response->assertSee('/images/home/reasons/primeiroemprego.webp', false);
        $response->assertSee('/images/home/reasons/empregomelhor.webp', false);
    }

    public function test_home_course_card_has_waitlist_metadata_when_course_has_specific_whatsapp(): void
    {
        $supportNumber = SupportWhatsappNumber::query()->create([
            'label' => 'Atendimento principal',
            'whatsapp' => '+55 (11) 99999-0000',
            'description' => 'Time comercial',
            'is_active' => true,
            'position' => 1,
        ]);

        $course = $this->createCourse([
            'slug' => 'curso-com-lista',
            'title' => 'Curso com Lista',
            'status' => 'published',
            'published_at' => now(),
            'support_whatsapp_mode' => 'specific',
            'support_whatsapp_number_id' => $supportNumber->id,
            'checkout_price' => 39.90,
        ]);

        $response = $this->get('/');
        $expectedMessage = rawurlencode('Quero entrar na lista de espera do curso '.$course->title.'.');
        $expectedWaitlistUrl = 'https://wa.me/5511999990000?text='.$expectedMessage;

        $response->assertOk();
        $response->assertSee('data-home-course-card="1"', false);
        $response->assertSee('data-course-slug="'.$course->slug.'"', false);
        $response->assertSee('data-waitlist-url="'.$expectedWaitlistUrl.'"', false);
        $response->assertSee('data-vacancy-badge', false);
        $response->assertSee('data-course-cta', false);
    }

    public function test_home_course_card_has_empty_waitlist_metadata_without_valid_whatsapp(): void
    {
        $course = $this->createPublishedCourse('curso-sem-lista', 'Curso Sem Lista', 19.90);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-course-slug="'.$course->slug.'"', false);
        $response->assertSee('data-city-scope=""', false);
        $response->assertSee('data-waitlist-url=""', false);
    }

    public function test_home_course_card_link_includes_home_source_query_param(): void
    {
        $course = $this->createPublishedCourse('curso-origem-home', 'Curso Origem Home', 39.90);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('href="'.route('courses.public.show', $course).'?edux_source=home"', false);
    }

    public function test_home_course_card_link_preserves_city_query_param_when_present(): void
    {
        $course = $this->createPublishedCourse('curso-cidade-home', 'Curso Cidade Home', 42.90);

        $response = $this->get('/?cidade=salvador');

        $response->assertOk();
        $response->assertSee(
            'href="'.route('courses.public.show', $course).'?edux_source=home&amp;cidade=salvador"',
            false
        );
        $response->assertSee('data-city-scope="salvador"', false);
    }

    public function test_home_course_cards_render_tracking_metadata_and_click_tracking_script(): void
    {
        $course = $this->createPublishedCourse('curso-track-home', 'Curso Track Home', 39.90);

        $response = $this->get('/?cidade=salvador');

        $response->assertOk();
        $response->assertSee('data-course-id="'.$course->id.'"', false);
        $response->assertSee('data-course-position="1"', false);
        $response->assertSee('window.homeMetaTrackStandard(\'ViewContent\'', false);
        $response->assertSee('window.homeMetaTrackStandard(\'Lead\'', false);
        $response->assertSee("url.searchParams.set('edux_vc_prefired', '1')", false);
    }

    public function test_authenticated_user_sees_same_standalone_home_without_auth_ctas(): void
    {
        $user = User::factory()->admin()->create();
        $this->createPublishedCourse('auxiliar-rh', 'Auxiliar de RH', 24.90);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('QUERO ME INSCREVER AGORA', false);
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
        $responseWithoutAsset->assertSee('Carta de Estágio e Recomendação', false);
        $responseWithoutAsset->assertDontSee('/storage/uploads/carta-estagio.png', false);

        SystemSetting::current()->forceFill([
            'carta_estagio' => 'uploads/carta-estagio.png',
        ])->save();

        $responseWithAsset = $this->get('/');

        $responseWithAsset->assertOk();
        $responseWithAsset->assertSee('Carta de Estágio e Recomendação', false);
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
