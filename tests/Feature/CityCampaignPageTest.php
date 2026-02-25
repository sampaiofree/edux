<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCheckout;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityCampaignPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EncryptCookies::class);

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_renders_open_city_catalog_and_sets_countdown_cookie(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-25 10:00:00', 'UTC'));

        $this->createPublishedCourse('informatica-basica', 'Informática Básica');

        $response = $this->get('/cidade/belo-horizonte?utm_source=meta');

        $response->assertOk();
        $response->assertSee('PROFISSÃO AO SEU ALCANCE.', false);
        $response->assertSee('Exclusivo: Belo Horizonte', false);
        $response->assertSee('Cursos Disponíveis', false);
        $response->assertSee('data-city-countdown', false);
        $response->assertSee('id="cursos-cidade"', false);
        $response->assertSee('data-countdown-expires="'.CarbonImmutable::now('UTC')->addHours(8)->getTimestamp().'"', false);
        $response->assertCookie($this->countdownCookieName('belo-horizonte'));
    }

    public function test_lists_only_published_courses_and_preserves_campaign_params_on_course_links(): void
    {
        $publishedA = $this->createPublishedCourse('informatica-basica', 'Informática Básica');
        $publishedB = $this->createPublishedCourse('auxiliar-administrativo', 'Auxiliar Administrativo');
        $this->createCourse([
            'slug' => 'rascunho',
            'title' => 'Curso Rascunho',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->get('/cidade/sao-paulo?utm_campaign=teste');

        $response->assertOk();
        $response->assertSee($publishedA->title);
        $response->assertSee($publishedB->title);
        $response->assertDontSee('Curso Rascunho');
        $response->assertSee('edux_city_slug=sao-paulo', false);
        $response->assertSee('edux_source=city_campaign', false);
        $response->assertSee('utm_campaign=teste', false);
        $response->assertSee(route('courses.public.show', $publishedA), false);
        $response->assertSee('data-city-cta-source="course_row"', false);
    }

    public function test_uses_existing_cookie_to_keep_same_countdown_window_per_city(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-25 14:10:00', 'UTC'));

        $this->createPublishedCourse('informatica-basica', 'Informática Básica');
        $startedAt = CarbonImmutable::parse('2026-02-25 09:00:00', 'UTC');

        $response = $this
            ->withUnencryptedCookie($this->countdownCookieName('cidade-do-rio'), (string) $startedAt->getTimestamp())
            ->get('/cidade/cidade-do-rio');

        $response->assertOk();
        $response->assertSee('data-countdown-expires="'.$startedAt->addHours(8)->getTimestamp().'"', false);
        $response->assertSee('Cidade Do Rio', false);
    }

    public function test_renders_closed_state_after_countdown_expires(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-25 18:30:00', 'UTC'));

        $this->createPublishedCourse('informatica-basica', 'Informática Básica');
        $startedAt = CarbonImmutable::parse('2026-02-25 09:00:00', 'UTC');

        $response = $this
            ->withUnencryptedCookie($this->countdownCookieName('sao-paulo'), (string) $startedAt->getTimestamp())
            ->get('/cidade/sao-paulo');

        $response->assertOk();
        $response->assertSee('Inscrições encerradas para Sao Paulo');
        $response->assertSee('Entrar na lista de espera');
        $response->assertSee('href="#"', false);
    }

    public function test_renders_empty_state_when_there_are_no_published_courses(): void
    {
        $response = $this->get('/cidade/recife');

        $response->assertOk();
        $response->assertSee('Nenhum curso publicado está disponível no momento', false);
    }

    public function test_orders_courses_by_lowest_checkout_price_ascending(): void
    {
        $expensive = $this->createPublishedCourse('curso-caro', 'Curso Caro', [49.90, 59.90]);
        $cheap = $this->createPublishedCourse('curso-barato', 'Curso Barato', [9.90, 19.90]);
        $medium = $this->createPublishedCourse('curso-medio', 'Curso Medio', [19.90, 29.90]);

        $response = $this->get('/cidade/salvador');

        $response->assertOk();

        $content = $response->getContent();
        $cheapPos = strpos($content, $cheap->title);
        $mediumPos = strpos($content, $medium->title);
        $expensivePos = strpos($content, $expensive->title);

        $this->assertNotFalse($cheapPos);
        $this->assertNotFalse($mediumPos);
        $this->assertNotFalse($expensivePos);
        $this->assertLessThan($mediumPos, $cheapPos);
        $this->assertLessThan($expensivePos, $mediumPos);
    }

    public function test_ignores_legacy_curso_query_param_and_keeps_city_catalog_working(): void
    {
        $course = $this->createPublishedCourse('informatica-basica', 'Informática Básica');

        $response = $this->get('/cidade/sao-paulo?curso=slug-antigo&utm_source=meta');

        $response->assertOk();
        $response->assertSee('Cursos Disponíveis', false);
        $response->assertSee($course->title);
        $response->assertSee('utm_source=meta', false);
        $response->assertSee('data-city-cta-source="course_row"', false);
    }

    /**
     * @param  array<int, float|int>  $checkoutPrices
     */
    private function createPublishedCourse(string $slug, string $title, array $checkoutPrices = [19.90, 29.90]): Course
    {
        return $this->createCourse([
            'slug' => $slug,
            'title' => $title,
            'status' => 'published',
            'published_at' => now(),
            'checkout_prices' => $checkoutPrices,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourse(array $overrides = []): Course
    {
        $checkoutPrices = $overrides['checkout_prices'] ?? [19.90, 29.90];
        unset($overrides['checkout_prices']);

        if (! is_array($checkoutPrices)) {
            $checkoutPrices = [19.90, 29.90];
        }

        $checkoutPrices = array_values($checkoutPrices);
        $firstCheckoutPrice = isset($checkoutPrices[0]) ? (float) $checkoutPrices[0] : 19.90;
        $secondCheckoutPrice = isset($checkoutPrices[1]) ? (float) $checkoutPrices[1] : 29.90;

        $owner = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        /** @var Course $course */
        $course = Course::create(array_merge([
            'owner_id' => $owner->id,
            'title' => 'Curso de Informática',
            'slug' => 'curso-de-informatica',
            'summary' => 'Resumo do curso',
            'description' => 'Descrição do curso',
            'status' => 'draft',
            'duration_minutes' => 240,
            'published_at' => null,
        ], $overrides));

        CourseCheckout::create([
            'course_id' => $course->id,
            'nome' => 'Plano 4h',
            'descricao' => 'Opção inicial',
            'hours' => 4,
            'price' => $firstCheckoutPrice,
            'checkout_url' => 'https://checkout.exemplo.com/'.$course->slug,
            'is_active' => true,
        ]);

        CourseCheckout::create([
            'course_id' => $course->id,
            'nome' => 'Plano 8h',
            'descricao' => 'Opção completa',
            'hours' => 8,
            'price' => $secondCheckoutPrice,
            'checkout_url' => 'https://checkout.exemplo.com/'.$course->slug.'/8h',
            'is_active' => true,
        ]);

        return $course->fresh(['checkouts']) ?? $course;
    }

    private function countdownCookieName(string $city): string
    {
        return config('city_campaign.cookie_prefix', 'edux_city_campaign_').sha1($city.'|all_courses');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}
