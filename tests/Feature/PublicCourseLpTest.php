<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CheckoutBonus;
use App\Models\Course;
use App\Models\CourseCheckout;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\SupportWhatsappNumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseLpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_public_course_lp_renders_new_structure_for_guest(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts();

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('data-lp-variant="v4"', false);
        $response->assertSee('catalogo_course_lp_v4', false);
        $response->assertSee('Consulta de matrícula');
        $response->assertSee('Escolha a forma de matrícula');
        $response->assertSee('Perguntas frequentes');
        $response->assertSee('data-checkout-source="checkout_compare_v4"', false);
        $response->assertSee('data-lp-cta-source="hero_primary_v4"', false);
        $response->assertSee('data-lp-cta-source="sticky_panel_primary_v4"', false);
        $response->assertSee('data-lp-cta-source="final_cta_v4"', false);
        $response->assertDontSee('data-lp-cta-source="hero_secondary_v4"', false);
        $response->assertDontSee('data-lp-cta-source="sticky_panel_secondary_v4"', false);
        $response->assertDontSee('data-lp-cta-source="final_secondary_v4"', false);
        $response->assertSee('data-lp-cta-source="mobile_sticky_cta_v4"', false);
    }

    public function test_public_course_lp_renders_fixed_city_top_bar_when_city_query_is_present(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts();

        $response = $this->get(route('courses.public.show', $course).'?cidade=Recife');

        $response->assertOk();
        $response->assertSee('data-lp-city-fixed-top', false);
        $response->assertSee('Atendimento para Recife');
        $response->assertSee('data-course-slug="'.$course->slug.'"', false);
        $response->assertSee('data-lp-vacancy="1"', false);
        $response->assertDontSee('data-lp-vacancy-badge', false);
        $response->assertSee('data-lp-hero-course-title', false);
        $response->assertSee('data-lp-hero-social-line', false);
        $response->assertSee('com valor social para Recife');
        $response->assertDontSee('Auxiliar Administrativo com valor social para Recife');
        $response->assertSee('data-city-name="Recife"', false);
        $response->assertSee('data-city-scope="recife"', false);
        $response->assertSee('data-lp-checkout-closed-banner', false);
    }

    public function test_public_course_lp_does_not_render_fixed_city_top_bar_without_city_query(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts();

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertDontSee('data-lp-city-fixed-top', false);
        $response->assertSee('data-lp-vacancy="1"', false);
        $response->assertSee('data-lp-vacancy-badge', false);
        $response->assertSee('data-lp-hero-social-line', false);
        $response->assertSee('com valor social de matrícula');
        $response->assertDontSee('Auxiliar Administrativo com valor social de matrícula');
        $response->assertSee('data-city-scope=""', false);
    }

    public function test_public_course_lp_exposes_waitlist_url_metadata_when_whatsapp_is_available(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts();

        $supportNumber = SupportWhatsappNumber::create([
            'label' => 'Atendimento principal',
            'whatsapp' => '+55 (11) 99999-0000',
            'description' => null,
            'is_active' => true,
            'position' => 1,
        ]);

        $course->forceFill([
            'support_whatsapp_mode' => 'specific',
            'support_whatsapp_number_id' => $supportNumber->id,
        ])->save();

        $response = $this->get(route('courses.public.show', $course));
        $expectedWaitlistUrl = 'https://wa.me/5511999990000?text='.rawurlencode(
            'Quero entrar na lista de espera do curso '.$course->title.'.'
        );

        $response->assertOk();
        $response->assertSee('data-waitlist-url="'.$expectedWaitlistUrl.'"', false);
    }

    public function test_public_course_lp_exposes_empty_waitlist_url_metadata_without_valid_whatsapp(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts();

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('data-waitlist-url=""', false);
    }

    public function test_public_course_lp_checkout_buttons_track_view_content_lead_and_initiate_checkout(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts();

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('buildCheckoutStandardPayload', false);
        $response->assertSee('window.lpMetaTrackStandard(\'ViewContent\', standardPayload);', false);
        $response->assertSee('window.lpMetaTrackStandard(\'Lead\', {', false);
        $response->assertSee('window.lpMetaTrackStandard(\'InitiateCheckout\', standardPayload);', false);
        $response->assertSee("['city_campaign', 'home'].includes(prefiredSource)", false);
    }

    public function test_public_course_lp_returns_404_for_unpublished_course(): void
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

        $this->get(route('courses.public.show', $course))
            ->assertNotFound();
    }

    public function test_public_course_lp_renders_two_primary_plan_cards_and_extra_cards_when_multiple_checkouts_exist(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(3);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('https://checkout.exemplo.com/'.$course->slug.'/1', false);
        $response->assertSee('https://checkout.exemplo.com/'.$course->slug.'/2', false);
        $response->assertSee('https://checkout.exemplo.com/'.$course->slug.'/3', false);
        $response->assertSee('data-checkout-card-grid', false);
        $response->assertSee('data-checkout-card-featured', false);
        $response->assertSee('data-mobile-checkout-carousel', false);
        $response->assertSee('data-mobile-checkout-track', false);
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-checkout-source="checkout_compare_v4"'));
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-checkout-card'));
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-checkout-card-price'));
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-checkout-card-meta'));
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-mobile-checkout-slide'));
        $this->assertSame(3, substr_count($response->getContent(), 'data-mobile-checkout-dot'));
    }

    public function test_public_course_lp_keeps_highest_priced_checkout_as_featured(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(3);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('R$ 49,90');
        $response->assertSee('Plano 3');
        $this->assertMatchesRegularExpression(
            '/data-checkout-card-featured[\s\S]*?Plano 3[\s\S]*?R\$ 49,90/',
            $response->getContent()
        );
    }

    public function test_public_course_lp_consulta_de_matricula_panel_shows_cheapest_plan(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(3);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/Consulta de matrícula[\s\S]*?R\$ 29,90[\s\S]*?Plano 1 • 3h/u',
            $response->getContent()
        );
    }

    public function test_public_course_lp_renders_single_plan_and_informational_card_when_only_one_checkout_exists(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(1);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('https://checkout.exemplo.com/'.$course->slug.'/1', false);
        $response->assertDontSee('https://checkout.exemplo.com/'.$course->slug.'/2', false);
        $response->assertSee('data-checkout-card-grid', false);
        $response->assertSee('data-checkout-card-featured', false);
        $response->assertDontSee('data-mobile-checkout-carousel', false);
        $response->assertDontSee('data-mobile-checkout-track', false);
        $response->assertDontSee('data-mobile-checkout-dot', false);
        $this->assertGreaterThanOrEqual(1, substr_count($response->getContent(), 'data-checkout-source="checkout_compare_v4"'));
        $response->assertSee('Como funciona sua matrícula');
    }

    public function test_public_course_lp_uses_whatsapp_when_no_valid_checkout_exists(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(0);

        SupportWhatsappNumber::create([
            'label' => 'Atendimento',
            'whatsapp' => '(11) 95555-1111',
            'description' => null,
            'is_active' => true,
            'position' => 1,
        ]);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('Atendimento pelo WhatsApp');
        $response->assertSee('https://wa.me/11955551111?text=', false);
        $response->assertSee('data-cta-type="whatsapp"', false);
    }

    public function test_public_course_lp_shows_unavailable_state_without_checkout_or_whatsapp(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(0);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('Nenhuma opcao de matricula disponivel no momento.');
        $response->assertDontSee('https://wa.me/', false);
    }

    public function test_public_course_lp_renders_module_fallback_when_course_has_no_modules(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(1, false);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('O que você vai aprender');
        $response->assertSee('Currículo organizado em módulos');
    }

    public function test_public_course_lp_renders_checkout_bonuses_and_certificate_previews(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(2);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('Documentos de apoio e comprovação');
        $response->assertSee('Apostila de apoio', false);
        $response->assertSee('R$ 29,90', false);
        $response->assertSee('por R$ 0,00', false);
        $response->assertSee('Certificado e carta ajudam sua apresentação profissional');
    }

    public function test_public_course_lp_testimonials_do_not_depend_on_external_thumbnails(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts();

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertDontSee('img.youtube.com', false);
        $response->assertDontSee('i.ytimg.com', false);
    }

    /**
     * @return array{0: \App\Models\Course, 1: \App\Models\User}
     */
    private function createPublishedCourseWithModulesAndCheckouts(int $checkoutCount = 2, bool $withModules = true): array
    {
        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $course = Course::create([
            'owner_id' => $owner->id,
            'title' => 'Auxiliar Administrativo',
            'slug' => 'auxiliar-administrativo',
            'summary' => 'Curso prático para rotina administrativa.',
            'description' => 'Descrição completa do curso para fortalecer currículo e prática profissional.',
            'atuacao' => 'Escritórios;Atendimento;Empresas privadas',
            'oquefaz' => 'Organiza documentos;Apoia rotinas operacionais;Melhora a apresentação profissional',
            'status' => 'published',
            'duration_minutes' => 180,
            'published_at' => now(),
        ]);

        if ($withModules) {
            $module = Module::create([
                'course_id' => $course->id,
                'title' => 'Introdução ao curso',
                'description' => 'Descrição do módulo',
                'position' => 1,
            ]);

            Lesson::create([
                'module_id' => $module->id,
                'title' => 'Primeiros passos',
                'content' => 'Conteúdo',
                'position' => 1,
            ]);

            Lesson::create([
                'module_id' => $module->id,
                'title' => 'Rotina prática',
                'content' => 'Conteúdo',
                'position' => 2,
            ]);
        }

        for ($i = 1; $i <= $checkoutCount; $i++) {
            $checkout = CourseCheckout::create([
                'course_id' => $course->id,
                'nome' => 'Plano '.$i,
                'descricao' => 'Descrição do plano '.$i,
                'hours' => 3 * $i,
                'price' => 19.90 + ($i * 10),
                'checkout_url' => 'https://checkout.exemplo.com/'.$course->slug.'/'.$i,
                'is_active' => true,
            ]);

            CheckoutBonus::create([
                'course_checkout_id' => $checkout->id,
                'nome' => 'Apostila de apoio',
                'descricao' => 'Material complementar',
                'preco' => 29.90,
            ]);
        }

        return [$course, $owner];
    }
}
