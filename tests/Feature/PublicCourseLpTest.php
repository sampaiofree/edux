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
        $response->assertSee('data-lp-variant="base"', false);
        $response->assertSee('class="lp-header"', false);
        $response->assertSee('id="hero"', false);
        $response->assertSee('id="planos"', false);
        $response->assertSee('Perguntas frequentes');
        $response->assertSee('data-checkout-source="mobile_sticky_cta"', false);
        $response->assertSee('data-lp-primary-scroll-cta', false);
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
        $this->assertSame(2, substr_count($response->getContent(), 'data-lp-plan-card-role="primary"'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-lp-plan-card-role="extra"'));
    }

    public function test_public_course_lp_renders_single_plan_and_informational_card_when_only_one_checkout_exists(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(1);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'data-lp-plan-card-role="primary"'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-lp-plan-card-role="info"'));
        $response->assertSee('Suporte e flexibilidade');
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
        $response->assertSee('Nenhuma opção de matrícula disponível no momento.');
        $response->assertDontSee('data-checkout-source="mobile_sticky_cta"', false);
    }

    public function test_public_course_lp_renders_module_fallback_when_course_has_no_modules(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(1, false);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('Visão geral');
        $response->assertSee('Como funciona o curso '.$course->title);
    }

    public function test_public_course_lp_renders_checkout_bonuses_and_certificate_previews(): void
    {
        [$course] = $this->createPublishedCourseWithModulesAndCheckouts(2);

        $response = $this->get(route('courses.public.show', $course));

        $response->assertOk();
        $response->assertSee('Bônus adicionais incluídos neste curso');
        $response->assertSee('Apostila de apoio', false);
        $response->assertSee('Certificação para fortalecer seu currículo');
        $response->assertSee('Seu nome aqui', false);
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
