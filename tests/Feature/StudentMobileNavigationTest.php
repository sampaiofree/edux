<?php

namespace Tests\Feature;

use App\Enums\EnrollmentAccessStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FinalTest;
use App\Models\FinalTestQuestion;
use App\Models\FinalTestQuestionOption;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentMobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_student_shell_renders_navigation_overlay_and_intercepted_dashboard_links(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-mobile@example.com',
        ]);
        $course = $this->createCourseForTenant($admin, 'curso-mobile-nav', 'Curso Mobile Nav');

        Enrollment::create([
            'system_setting_id' => $admin->system_setting_id,
            'course_id' => $course->id,
            'user_id' => $student->id,
            'progress_percent' => 35,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-student-shell="1"', false);
        $response->assertSee('data-student-navigation-overlay="1"', false);
        $html = $response->getContent();

        $this->assertNavigableLink($html, route('dashboard'));
        $this->assertNavigableLink($html, route('learning.notifications.index'));
        $this->assertNavigableLink($html, route('account.edit'));
        $this->assertNavigableLink($html, route('certificado.index'));
        $this->assertNavigableLink($html, route('learning.courses.show', $course));

        $panelResponse = $this->actingAs($student)->get(route('dashboard', ['tab' => 'painel']));
        $panelResponse->assertOk();
        $panelHtml = $panelResponse->getContent();

        $this->assertNavigableLink($panelHtml, route('dashboard', ['tab' => 'cursos']));
        $this->assertNavigableLink($panelHtml, route('dashboard', ['tab' => 'vitrine']));
    }

    public function test_lesson_screen_renders_player_placeholder_and_intercepted_navigation_links(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-aula-mobile@example.com',
        ]);
        $course = $this->createCourseForTenant($admin, 'curso-aula-mobile', 'Curso Aula Mobile');
        $module = $this->createModuleForCourse($course, 'Modulo 1');
        $firstLesson = $this->createLessonForModule($module, 'Aula 1', 1);
        $secondLesson = $this->createLessonForModule($module, 'Aula 2', 2);
        $this->createFinalTestForCourse($course);

        Enrollment::create([
            'system_setting_id' => $admin->system_setting_id,
            'course_id' => $course->id,
            'user_id' => $student->id,
            'progress_percent' => 100,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);
        LessonCompletion::create([
            'lesson_id' => $firstLesson->id,
            'user_id' => $student->id,
            'completed_at' => now(),
        ]);
        LessonCompletion::create([
            'lesson_id' => $secondLesson->id,
            'user_id' => $student->id,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('learning.courses.lessons.show', [$course, $firstLesson]));

        $response->assertOk();
        $response->assertSee('data-lesson-player-shell="1"', false);
        $response->assertSee('data-lesson-player-placeholder="1"', false);
        $html = $response->getContent();

        $this->assertNavigableLink($html, route('learning.courses.lessons.show', [$course, $secondLesson]));
        $this->assertNavigableLink($html, route('learning.courses.final-test.intro', $course));
    }

    public function test_certificate_and_final_test_pages_keep_sync_flows_outside_wire_navigate(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-cert-mobile@example.com',
            'name_change_available' => true,
        ]);
        $course = $this->createCourseForTenant($admin, 'curso-cert-mobile', 'Curso Cert Mobile');
        $this->createFinalTestForCourse($course);

        Enrollment::create([
            'system_setting_id' => $admin->system_setting_id,
            'course_id' => $course->id,
            'user_id' => $student->id,
            'progress_percent' => 100,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);

        $certificate = Certificate::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'number' => 'CERT-MOBILE-001',
            'issued_at' => now(),
            'front_content' => '<p>Frente</p>',
            'back_content' => '<p>Verso</p>',
        ]);
        $certificate->forceFill([
            'public_token' => (string) Str::uuid(),
        ])->save();

        $certificateResponse = $this->actingAs($student)
            ->get(route('learning.courses.certificate.show', [$course, $certificate]));

        $certificateResponse->assertOk();
        $certificateHtml = $certificateResponse->getContent();

        $this->assertNavigableLink($certificateHtml, route('dashboard'));
        $this->assertNavigableLink($certificateHtml, route('account.edit'));
        $this->assertStringContainsString('data-certificate-share-trigger="1"', $certificateHtml);
        $this->assertStringContainsString('href="'.route('learning.courses.certificate.download', [$course, $certificate]).'"', $certificateHtml);
        $this->assertStringContainsString('data-certificate-download-url="'.route('learning.courses.certificate.download', [$course, $certificate]).'"', $certificateHtml);
        $this->assertStringContainsString('data-certificate-public-url="'.route('certificates.verify', $certificate->fresh()->public_token).'"', $certificateHtml);
        $this->assertStringContainsString('data-native-label="Compartilhar PDF"', $certificateHtml);
        $this->assertNotNavigableLink($certificateHtml, route('learning.courses.certificate.download', [$course, $certificate]));

        $finalTestResponse = $this->actingAs($student)
            ->get(route('learning.courses.final-test.intro', $course));

        $finalTestResponse->assertOk();
        $finalTestHtml = $finalTestResponse->getContent();

        $this->assertNavigableLink($finalTestHtml, route('dashboard'));
        $this->assertStringContainsString('action="'.route('learning.courses.final-test.start', $course).'"', $finalTestHtml);
        $this->assertStringNotContainsString('action="'.route('learning.courses.final-test.start', $course).'" wire:navigate', $finalTestHtml);
    }

    public function test_certificate_index_links_to_full_page_generation_flow_without_modal_markup(): void
    {
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-modal-cert@example.com',
        ]);

        $response = $this->actingAs($student)->get(route('certificado.index'));

        $response->assertOk();
        $response->assertSee('href="'.route('certificado.create').'"', false);
        $response->assertSee('Gerar certificado');
        $response->assertDontSee('data-certificate-modal-shell="1"', false);
        $response->assertDontSee('modalOpen', false);
    }

    public function test_certificate_create_page_renders_full_page_form_without_modal_markup(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-create-cert@example.com',
        ]);
        $course = $this->createCourseForTenant($admin, 'curso-create-cert', 'Curso Create Cert');

        Enrollment::create([
            'system_setting_id' => $admin->system_setting_id,
            'course_id' => $course->id,
            'user_id' => $student->id,
            'completed_at' => now(),
            'progress_percent' => 100,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($student)->get(route('certificado.create'));

        $response->assertOk();
        $response->assertSee('Gerar certificado');
        $response->assertSee('Selecione o curso, confirme os dados e gere seu certificado.');
        $response->assertSee('href="'.route('certificado.index').'"', false);
        $response->assertDontSee('data-certificate-modal-shell="1"', false);
        $response->assertDontSee('modalOpen', false);
    }

    public function test_certificate_index_renders_share_metadata_for_existing_certificates(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'aluno-cert-index@example.com',
        ]);
        $course = $this->createCourseForTenant($admin, 'curso-cert-index', 'Curso Cert Index');

        $certificate = Certificate::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'number' => 'CERT-INDEX-001',
            'issued_at' => now(),
            'front_content' => '<p>Frente</p>',
            'back_content' => '<p>Verso</p>',
        ]);
        $certificate->forceFill([
            'public_token' => (string) Str::uuid(),
        ])->save();

        $response = $this->actingAs($student)->get(route('certificado.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-certificate-share-trigger="1"', $html);
        $this->assertStringContainsString('data-certificate-download-url="'.route('learning.courses.certificate.download', [$course, $certificate]).'"', $html);
        $this->assertStringContainsString('data-certificate-public-url="'.route('certificates.verify', $certificate->public_token).'"', $html);
        $this->assertStringContainsString('data-native-label="Compartilhar PDF"', $html);
    }

    private function createCourseForTenant(User $owner, string $slug, string $title): Course
    {
        return Course::create([
            'system_setting_id' => $owner->system_setting_id,
            'owner_id' => $owner->id,
            'title' => $title,
            'slug' => $slug,
            'summary' => 'Resumo '.$title,
            'description' => 'Descricao '.$title,
            'status' => 'published',
            'duration_minutes' => 60,
            'published_at' => now(),
        ]);
    }

    private function createModuleForCourse(Course $course, string $title, int $position = 1): Module
    {
        return Module::create([
            'course_id' => $course->id,
            'title' => $title,
            'description' => 'Descricao '.$title,
            'position' => $position,
        ]);
    }

    private function createLessonForModule(Module $module, string $title, int $position = 1): Lesson
    {
        return Lesson::create([
            'module_id' => $module->id,
            'title' => $title,
            'content' => 'Conteudo '.$title,
            'video_url' => 'https://example.com/'.Str::slug($title),
            'duration_minutes' => 10,
            'position' => $position,
        ]);
    }

    private function createFinalTestForCourse(Course $course): FinalTest
    {
        $finalTest = FinalTest::create([
            'course_id' => $course->id,
            'title' => 'Teste final '.$course->title,
            'instructions' => 'Responda tudo.',
            'passing_score' => 70,
            'max_attempts' => 3,
            'duration_minutes' => 30,
        ]);

        $question = FinalTestQuestion::create([
            'final_test_id' => $finalTest->id,
            'title' => 'Pergunta 1',
            'statement' => 'Escolha a resposta correta.',
            'position' => 1,
            'weight' => 1,
        ]);

        FinalTestQuestionOption::create([
            'final_test_question_id' => $question->id,
            'label' => 'Opcao correta',
            'is_correct' => true,
            'position' => 1,
        ]);

        return $finalTest;
    }

    private function assertNavigableLink(string $html, string $url): void
    {
        $pattern = sprintf('/href="%s"[^>]*wire:navigate/s', preg_quote($url, '/'));

        $this->assertSame(1, preg_match($pattern, $html), "Expected navigable link for [{$url}].");
    }

    private function assertNotNavigableLink(string $html, string $url): void
    {
        $pattern = sprintf('/href="%s"[^>]*wire:navigate/s', preg_quote($url, '/'));

        $this->assertSame(0, preg_match($pattern, $html), "Did not expect navigable link for [{$url}].");
    }
}
