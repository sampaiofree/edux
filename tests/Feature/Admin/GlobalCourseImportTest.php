<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\FinalTest;
use App\Models\SupportWhatsappNumber;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalCourseImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_dashboard_renders_import_button_and_modal_lists_only_global_courses(): void
    {
        [$destinationAdmin, $destinationTenant] = $this->createTenantAdmin('cursos-admin-import.test', 'Tenant Destino');
        [$sourceAdmin, ] = $this->createTenantAdmin('cursos-globais-import.test', 'Tenant Origem');

        $globalCourseA = $this->makeCourse($sourceAdmin, [
            'title' => 'Curso Global de Vendas',
            'slug' => 'curso-global-vendas',
            'is_global' => true,
        ]);
        $globalCourseB = $this->makeCourse($sourceAdmin, [
            'title' => 'Curso Global de Marketing',
            'slug' => 'curso-global-marketing',
            'is_global' => true,
        ]);
        $this->makeCourse($sourceAdmin, [
            'title' => 'Curso Privado Interno',
            'slug' => 'curso-privado-interno',
            'is_global' => false,
        ]);

        $response = $this->forceTestHost($destinationTenant->domain)
            ->actingAs($destinationAdmin)
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Importar curso', false);

        $this->actingAs($destinationAdmin);

        Livewire::test(Dashboard::class)
            ->call('openImportModal')
            ->assertSee('Curso Global de Vendas')
            ->assertSee('Curso Global de Marketing')
            ->assertDontSee('Curso Privado Interno')
            ->set('importSearch', 'marketing')
            ->assertSee('Curso Global de Marketing')
            ->assertDontSee('Curso Global de Vendas')
            ->assertDontSee('Curso Privado Interno');

        $this->assertTrue($globalCourseA->fresh()->is_global);
        $this->assertTrue($globalCourseB->fresh()->is_global);
    }

    public function test_admin_can_import_global_course_with_content_assets_and_reset_fields(): void
    {
        Storage::fake('public');

        [$destinationAdmin, $destinationTenant] = $this->createTenantAdmin('cursos-admin-clone.test', 'Tenant Destino Clone');
        [$sourceAdmin, $sourceTenant] = $this->createTenantAdmin('cursos-admin-global.test', 'Tenant Origem Clone');

        Storage::disk('public')->put('course-covers/global-cover.jpg', 'cover-content');
        Storage::disk('public')->put('certificate-backgrounds/front-global.png', 'front-content');
        Storage::disk('public')->put('certificate-backgrounds/back-global.png', 'back-content');
        $supportNumber = SupportWhatsappNumber::withoutGlobalScopes()->create([
            'system_setting_id' => $sourceTenant->id,
            'label' => 'Suporte Global',
            'whatsapp' => '5511999999999',
            'description' => 'Número de suporte do curso global',
            'is_active' => true,
            'position' => 1,
        ]);

        $sourceCourse = $this->makeCourse($sourceAdmin, [
            'title' => 'Auxiliar Administrativo Global',
            'slug' => 'auxiliar-administrativo-global',
            'summary' => 'Resumo do curso global',
            'description' => 'Descrição completa do curso global',
            'atuacao' => 'Atuação do curso',
            'oquefaz' => 'O que faz no mercado',
            'cover_image_path' => 'course-covers/global-cover.jpg',
            'promo_video_url' => 'https://www.youtube.com/watch?v=global123',
            'status' => 'published',
            'duration_minutes' => 720,
            'published_at' => Carbon::parse('2026-03-01 10:00:00'),
            'kavoo_id' => 9988,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_SPECIFIC,
            'support_whatsapp_number_id' => $supportNumber->id,
            'is_global' => true,
        ]);

        $this->seedModulesAndLessons($sourceCourse);
        $this->seedFinalTest($sourceCourse);
        $this->seedCertificateBranding($sourceCourse, 'certificate-backgrounds/front-global.png', 'certificate-backgrounds/back-global.png');

        $checkout = $sourceCourse->checkouts()->create([
            'nome' => 'Plano Premium',
            'descricao' => 'Checkout do curso global',
            'hours' => 24,
            'price' => 197.90,
            'checkout_url' => 'https://pay.example.test/checkout',
            'is_active' => true,
        ]);
        $checkout->bonuses()->create([
            'nome' => 'Bônus 1',
            'descricao' => 'Material complementar',
            'preco' => 29.90,
        ]);
        $sourceCourse->courseWebhookIds()->create([
            'webhook_id' => 'GLOBAL-WEBHOOK-1',
            'platform' => 'Hotmart',
        ]);

        $this->forceTestHost($destinationTenant->domain)->actingAs($destinationAdmin);

        Livewire::test(Dashboard::class)
            ->call('openImportModal')
            ->call('importCourse', $sourceCourse->id)
            ->assertSet('importModalOpen', false);

        $importedCourse = Course::withoutGlobalScopes()
            ->where('system_setting_id', $destinationTenant->id)
            ->where('owner_id', $destinationAdmin->id)
            ->where('title', $sourceCourse->title)
            ->whereKeyNot($sourceCourse->id)
            ->firstOrFail();

        $importedCourse->load([
            'modules.lessons',
            'finalTest.questions.options',
            'checkouts.bonuses',
            'courseWebhookIds',
        ]);
        $sourceCourse->load([
            'modules.lessons',
            'finalTest.questions.options',
            'certificateBranding',
        ]);
        $importedBranding = CertificateBranding::withoutGlobalScopes()
            ->where('course_id', $importedCourse->id)
            ->first();

        $this->assertSame($destinationTenant->id, $importedCourse->system_setting_id);
        $this->assertSame($destinationAdmin->id, $importedCourse->owner_id);
        $this->assertFalse($importedCourse->is_global);
        $this->assertSame('draft', $importedCourse->status);
        $this->assertNull($importedCourse->published_at);
        $this->assertSame(Course::SUPPORT_WHATSAPP_MODE_ALL, $importedCourse->support_whatsapp_mode);
        $this->assertNull($importedCourse->support_whatsapp_number_id);
        $this->assertNull($importedCourse->kavoo_id);
        $this->assertSame($sourceCourse->summary, $importedCourse->summary);
        $this->assertSame($sourceCourse->description, $importedCourse->description);
        $this->assertSame($sourceCourse->atuacao, $importedCourse->atuacao);
        $this->assertSame($sourceCourse->oquefaz, $importedCourse->oquefaz);
        $this->assertSame($sourceCourse->promo_video_url, $importedCourse->promo_video_url);
        $this->assertSame($sourceCourse->duration_minutes, $importedCourse->duration_minutes);
        $this->assertNotSame($sourceCourse->slug, $importedCourse->slug);

        $this->assertNotNull($importedCourse->cover_image_path);
        $this->assertNotSame($sourceCourse->cover_image_path, $importedCourse->cover_image_path);
        Storage::disk('public')->assertExists($importedCourse->cover_image_path);

        $this->assertSame($this->moduleSnapshot($sourceCourse), $this->moduleSnapshot($importedCourse));
        $this->assertSame($this->finalTestSnapshot($sourceCourse->finalTest), $this->finalTestSnapshot($importedCourse->finalTest));

        $this->assertNotNull($importedBranding);
        $this->assertNotSame(
            $sourceCourse->certificateBranding?->front_background_path,
            $importedBranding?->front_background_path
        );
        $this->assertNotSame(
            $sourceCourse->certificateBranding?->back_background_path,
            $importedBranding?->back_background_path
        );
        Storage::disk('public')->assertExists($importedBranding->front_background_path);
        Storage::disk('public')->assertExists($importedBranding->back_background_path);

        $this->assertCount(0, $importedCourse->checkouts);
        $this->assertCount(0, $importedCourse->courseWebhookIds);
        $this->assertSame(1, $sourceCourse->checkouts()->count());
        $this->assertSame(1, $sourceCourse->courseWebhookIds()->count());
        $this->assertSame($sourceTenant->id, $sourceCourse->system_setting_id);
    }

    public function test_admin_can_import_same_global_course_multiple_times_with_unique_slugs(): void
    {
        [$destinationAdmin, $destinationTenant] = $this->createTenantAdmin('cursos-import-multi.test', 'Tenant Multi Destino');
        [$sourceAdmin, ] = $this->createTenantAdmin('cursos-import-multi-src.test', 'Tenant Multi Origem');

        $sourceCourse = $this->makeCourse($sourceAdmin, [
            'title' => 'Curso Global Repetível',
            'slug' => 'curso-global-repetivel',
            'is_global' => true,
        ]);

        $this->forceTestHost($destinationTenant->domain)->actingAs($destinationAdmin);

        Livewire::test(Dashboard::class)->call('importCourse', $sourceCourse->id);
        Livewire::test(Dashboard::class)->call('importCourse', $sourceCourse->id);

        $importedCourses = Course::withoutGlobalScopes()
            ->where('system_setting_id', $destinationTenant->id)
            ->where('owner_id', $destinationAdmin->id)
            ->where('title', $sourceCourse->title)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $importedCourses);
        $this->assertSame([false, false], $importedCourses->pluck('is_global')->all());
        $this->assertCount(2, $importedCourses->pluck('slug')->unique());
        $this->assertFalse($importedCourses->pluck('slug')->contains($sourceCourse->slug));
    }

    public function test_import_does_not_create_course_specific_branding_when_source_only_uses_tenant_default_branding(): void
    {
        Storage::fake('public');

        [$destinationAdmin, $destinationTenant] = $this->createTenantAdmin('cursos-import-branding-dst.test', 'Tenant Branding Destino');
        [$sourceAdmin, $sourceTenant] = $this->createTenantAdmin('cursos-import-branding-src.test', 'Tenant Branding Origem');

        Storage::disk('public')->put('certificate-backgrounds/default-front.png', 'default-front-content');

        $sourceCourse = $this->makeCourse($sourceAdmin, [
            'title' => 'Curso Global Sem Branding Próprio',
            'slug' => 'curso-global-sem-branding-proprio',
            'is_global' => true,
        ]);

        CertificateBranding::withoutGlobalScopes()->create([
            'system_setting_id' => $sourceTenant->id,
            'course_id' => null,
            'front_background_path' => 'certificate-backgrounds/default-front.png',
            'back_background_path' => null,
        ]);

        $this->forceTestHost($destinationTenant->domain)->actingAs($destinationAdmin);

        Livewire::test(Dashboard::class)->call('importCourse', $sourceCourse->id);

        $importedCourse = Course::withoutGlobalScopes()
            ->where('system_setting_id', $destinationTenant->id)
            ->where('title', $sourceCourse->title)
            ->firstOrFail();

        $this->assertDatabaseMissing('certificate_brandings', [
            'course_id' => $importedCourse->id,
        ]);
    }

    /**
     * @return array{0:User,1:SystemSetting}
     */
    private function createTenantAdmin(string $domain, string $schoolName): array
    {
        $this->forceTestHost($domain);

        $admin = $this->createAdminForTenant([
            'name' => 'Admin '.$schoolName,
            'email' => Str::slug($schoolName).'-'.Str::random(4).'@example.com',
        ], [
            'domain' => $domain,
            'escola_nome' => $schoolName,
        ]);

        return [
            $admin->fresh(),
            $admin->systemSetting()->withoutGlobalScopes()->firstOrFail()->fresh(),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCourse(User $owner, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'owner_id' => $owner->id,
            'title' => 'Curso Teste',
            'slug' => Str::slug(($overrides['title'] ?? 'Curso Teste')).'-'.Str::lower(Str::random(4)),
            'summary' => 'Resumo breve',
            'description' => 'Descrição padrão',
            'status' => 'draft',
            'duration_minutes' => 120,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
            'is_global' => false,
        ], $overrides));
    }

    private function seedModulesAndLessons(Course $course): void
    {
        $moduleA = $course->modules()->create([
            'title' => 'Módulo A',
            'description' => 'Introdução',
            'position' => 1,
        ]);
        $moduleA->lessons()->createMany([
            [
                'title' => 'Aula 1',
                'content' => 'Conteúdo da aula 1',
                'video_url' => 'https://www.youtube.com/watch?v=lesson-a1',
                'duration_minutes' => 18,
                'position' => 1,
            ],
            [
                'title' => 'Aula 2',
                'content' => 'Conteúdo da aula 2',
                'video_url' => 'https://www.youtube.com/watch?v=lesson-a2',
                'duration_minutes' => 22,
                'position' => 2,
            ],
        ]);

        $moduleB = $course->modules()->create([
            'title' => 'Módulo B',
            'description' => 'Prática',
            'position' => 2,
        ]);
        $moduleB->lessons()->create([
            'title' => 'Aula 3',
            'content' => 'Conteúdo da aula 3',
            'video_url' => 'https://www.youtube.com/watch?v=lesson-b1',
            'duration_minutes' => 30,
            'position' => 1,
        ]);
    }

    private function seedFinalTest(Course $course): void
    {
        $finalTest = $course->finalTest()->create([
            'title' => 'Prova Final',
            'instructions' => 'Leia com atenção.',
            'passing_score' => 80,
            'max_attempts' => 3,
            'duration_minutes' => 25,
        ]);

        $question = $finalTest->questions()->create([
            'title' => 'Pergunta 1',
            'statement' => 'Qual é a resposta correta?',
            'position' => 1,
            'weight' => 2,
        ]);

        $question->options()->createMany([
            [
                'label' => 'Alternativa A',
                'is_correct' => false,
                'position' => 1,
            ],
            [
                'label' => 'Alternativa B',
                'is_correct' => true,
                'position' => 2,
            ],
        ]);
    }

    private function seedCertificateBranding(Course $course, string $frontPath, ?string $backPath): void
    {
        $course->certificateBranding()->create([
            'front_background_path' => $frontPath,
            'back_background_path' => $backPath,
        ]);
    }

    /**
     * @return array<int, array{title:string,description:?string,position:int,lessons:array<int, array{title:string,content:?string,video_url:?string,duration_minutes:?int,position:int}>}>
     */
    private function moduleSnapshot(Course $course): array
    {
        return $course->modules
            ->map(fn ($module): array => [
                'title' => $module->title,
                'description' => $module->description,
                'position' => $module->position,
                'lessons' => $module->lessons
                    ->map(fn ($lesson): array => [
                        'title' => $lesson->title,
                        'content' => $lesson->content,
                        'video_url' => $lesson->video_url,
                        'duration_minutes' => $lesson->duration_minutes,
                        'position' => $lesson->position,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function finalTestSnapshot(?FinalTest $finalTest): ?array
    {
        if (! $finalTest) {
            return null;
        }

        return [
            'title' => $finalTest->title,
            'instructions' => $finalTest->instructions,
            'passing_score' => $finalTest->passing_score,
            'max_attempts' => $finalTest->max_attempts,
            'duration_minutes' => $finalTest->duration_minutes,
            'questions' => $finalTest->questions
                ->map(fn ($question): array => [
                    'title' => $question->title,
                    'statement' => $question->statement,
                    'position' => $question->position,
                    'weight' => $question->weight,
                    'options' => $question->options
                        ->map(fn ($option): array => [
                            'label' => $option->label,
                            'is_correct' => $option->is_correct,
                            'position' => $option->position,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }
}
