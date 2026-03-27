<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCheckout;
use App\Models\SupportWhatsappNumber;
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
        $response->assertSee('Consulta de matrícula');
        $response->assertSee('Escolha a forma de matrícula');
        $response->assertSee('Perguntas frequentes');
        $response->assertSee('sem vínculo com governo', false);
        $response->assertSee('data-checkout-source="checkout_compare_v4"', false);
        $response->assertSee('data-lp-cta-source="hero_primary_v4"', false);
        $response->assertSee('data-lp-cta-source="sticky_panel_primary_v4"', false);
        $response->assertSee('data-lp-cta-source="final_cta_v4"', false);
        $response->assertDontSee('data-lp-cta-source="hero_secondary_v4"', false);
        $response->assertDontSee('data-lp-cta-source="sticky_panel_secondary_v4"', false);
        $response->assertDontSee('data-lp-cta-source="final_secondary_v4"', false);
        $response->assertSee('data-lp-cta-source="mobile_sticky_cta_v4"', false);
        $response->assertDontSee('data-mobile-checkout-carousel', false);
        $response->assertDontSee('data-mobile-checkout-track', false);
        $response->assertDontSee('data-mobile-checkout-dot', false);
    }

    public function test_public_course_lp_v4_accepts_city_query_and_renders_city_name(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        $response = $this->get(route('courses.public.v4.show', $course).'?cidade=Recife');

        $response->assertOk();
        $response->assertSee('Atendimento para Recife');
        $response->assertSee('Cidade informada: Recife');
        $response->assertDontSee('data-lp-vacancy-badge', false);
        $response->assertSee('data-lp-hero-course-title', false);
        $response->assertSee('data-lp-hero-social-line', false);
        $response->assertSee('com valor social para Recife');
        $response->assertDontSee('Auxiliar Administrativo com valor social para Recife');
        $response->assertSee('Se você está em Recife, aproveite o valor social');
    }

    public function test_public_course_lp_v4_falls_back_without_city_query(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        $response = $this->get(route('courses.public.v4.show', $course));

        $response->assertOk();
        $response->assertDontSee('Cidade informada:');
        $response->assertSee('data-lp-vacancy-badge', false);
        $response->assertSee('data-lp-hero-course-title', false);
        $response->assertSee('data-lp-hero-social-line', false);
        $response->assertSee('com valor social de matrícula');
        $response->assertDontSee('Auxiliar Administrativo com valor social de matrícula');
        $response->assertSee('Aproveite o valor social e escolha sua matrícula agora.');
    }

    public function test_public_course_lp_v4_checkout_buttons_track_view_content_lead_and_initiate_checkout(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        $response = $this->get(route('courses.public.v4.show', $course));

        $response->assertOk();
        $response->assertSee('buildCheckoutStandardPayload', false);
        $response->assertSee('window.lpMetaTrackStandard(\'ViewContent\', standardPayload);', false);
        $response->assertSee('window.lpMetaTrackStandard(\'Lead\', {', false);
        $response->assertSee('window.lpMetaTrackStandard(\'InitiateCheckout\', standardPayload);', false);
        $response->assertSee("['city_campaign', 'home'].includes(prefiredSource)", false);
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

    public function test_public_course_lp_v4_keeps_checkout_flow_when_w_query_is_enabled(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        SupportWhatsappNumber::create([
            'system_setting_id' => $this->defaultTenantAdmin()->system_setting_id,
            'label' => 'Atendimento',
            'whatsapp' => '(11) 95555-1111',
            'description' => null,
            'is_active' => true,
            'position' => 1,
        ]);

        $response = $this->get(route('courses.public.v4.show', $course).'?w=1&cidade=Recife');

        $response->assertOk();
        $response->assertSee('data-lp-cta-source="hero_primary_v4"', false);
        $response->assertSee('href="#matricula"', false);
        $response->assertSee('https://checkout.exemplo.com/auxiliar-administrativo', false);
        $response->assertSee('data-checkout-source="checkout_compare_v4"', false);
    }

    public function test_public_course_lp_v4_renders_mobile_checkout_carousel_when_multiple_checkouts_exist(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout(3);

        $response = $this->get(route('courses.public.v4.show', $course));

        $response->assertOk();
        $response->assertSee('data-mobile-checkout-carousel', false);
        $response->assertSee('data-mobile-checkout-track', false);
        $response->assertSee('data-checkout-card-grid', false);
        $response->assertSee('data-checkout-card-featured', false);
        $response->assertSee('https://checkout.exemplo.com/auxiliar-administrativo/1', false);
        $response->assertSee('https://checkout.exemplo.com/auxiliar-administrativo/2', false);
        $response->assertSee('https://checkout.exemplo.com/auxiliar-administrativo/3', false);
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-checkout-card'));
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-checkout-card-price'));
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-checkout-card-meta'));
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-mobile-checkout-slide'));
        $this->assertSame(3, substr_count($response->getContent(), 'data-mobile-checkout-dot'));
        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-checkout-link'));
    }

    public function test_public_course_lp_v4_keeps_highest_priced_checkout_as_featured(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout(3);

        $response = $this->get(route('courses.public.v4.show', $course));

        $response->assertOk();
        $response->assertSee('R$ 47,90');
        $response->assertSee('Plano 9h');
        $this->assertMatchesRegularExpression(
            '/data-checkout-card-featured[\s\S]*?Plano 9h[\s\S]*?R\$ 47,90/',
            $response->getContent()
        );
    }

    public function test_public_course_lp_v4_consulta_de_matricula_panel_shows_cheapest_plan(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout(3);

        $response = $this->get(route('courses.public.v4.show', $course));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/Consulta de matrícula[\s\S]*?R\$ 27,90[\s\S]*?Plano 3h • 3h/u',
            $response->getContent()
        );
    }

    public function test_public_course_lp_v4_falls_back_to_checkout_when_w_query_has_no_whatsapp_contact(): void
    {
        [$course] = $this->createPublishedCourseWithCheckout();

        $response = $this->get(route('courses.public.v4.show', $course).'?w=1');

        $response->assertOk();
        $response->assertSee('https://checkout.exemplo.com/auxiliar-administrativo', false);
        $response->assertDontSee('https://wa.me/', false);
    }

    public function test_public_course_lp_v4_uses_whatsapp_fallback_when_no_checkout_exists(): void
    {
        $owner = $this->defaultTenantAdmin();

        $course = Course::create([
            'owner_id' => $owner->id,
            'title' => 'Monitor Escolar',
            'slug' => 'monitor-escolar',
            'summary' => 'Resumo',
            'description' => 'DescriÃ§Ã£o',
            'status' => 'published',
            'duration_minutes' => 120,
            'published_at' => now(),
        ]);

        SupportWhatsappNumber::create([
            'system_setting_id' => $this->defaultTenantAdmin()->system_setting_id,
            'label' => 'Atendimento',
            'whatsapp' => '(11) 98888-0000',
            'description' => null,
            'is_active' => true,
            'position' => 1,
        ]);

        $response = $this->get(route('courses.public.v4.show', $course));

        $response->assertOk();
        $response->assertSee('https://wa.me/11988880000?text=', false);
        $response->assertSee('window.lpMetaTrackStandard(\'Lead\'', false);
        $response->assertSee('data-cta-type="whatsapp"', false);
        $response->assertSee('Atendimento pelo WhatsApp');
        $response->assertDontSee('Nenhuma opção de matrícula disponível no momento.');
    }

    public function test_public_course_lp_v4_returns_404_for_unpublished_course(): void
    {
        $owner = $this->defaultTenantAdmin();

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
    private function createPublishedCourseWithCheckout(int $checkoutCount = 1): array
    {
        $owner = $this->defaultTenantAdmin();

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

        for ($i = 1; $i <= $checkoutCount; $i++) {
            CourseCheckout::create([
                'course_id' => $course->id,
                'nome' => 'Plano '.($i * 3).'h',
                'descricao' => 'Plano inicial',
                'hours' => $i * 3,
                'price' => 27.90 + (($i - 1) * 10),
                'checkout_url' => $checkoutCount === 1
                    ? 'https://checkout.exemplo.com/auxiliar-administrativo'
                    : 'https://checkout.exemplo.com/auxiliar-administrativo/'.$i,
                'is_active' => true,
            ]);
        }

        return [$course, $owner];
    }
}
