<?php

namespace Tests\Feature;

use App\Enums\EnrollmentAccessStatus;
use App\Livewire\Certificado\Checkout;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FinalTest;
use App\Models\FinalTestAttempt;
use App\Models\FinalTestQuestion;
use App\Models\FinalTestQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateAppFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_generate_certificate_redirects_back_to_certificate_index_with_success_feedback(): void
    {
        Http::fake([
            'https://api.qrserver.com/*' => Http::response('fake-qr', 200, ['Content-Type' => 'image/png']),
        ]);

        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'cert-app-flow@example.com',
        ]);
        $course = $this->createCourseForTenant($admin, 'curso-app-cert', 'Curso App Cert');

        Enrollment::create([
            'system_setting_id' => $admin->system_setting_id,
            'course_id' => $course->id,
            'user_id' => $student->id,
            'completed_at' => now(),
            'progress_percent' => 100,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);

        $this->actingAs($student);

        Livewire::test(Checkout::class)
            ->set('courseId', $course->id)
            ->set('completionDate', now()->format('Y-m-d'))
            ->set('completionConfirmed', 'yes')
            ->call('generateCertificate')
            ->assertRedirect(route('certificado.index'));

        $certificate = Certificate::query()
            ->where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        $this->assertNotNull($certificate->public_token);
        $this->assertNotNull($certificate->issued_at);

        $indexResponse = $this->actingAs($student)->get(route('certificado.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Certificado emitido com sucesso!');
        $indexResponse->assertSee($course->title);
    }

    public function test_passed_final_test_shows_certificate_cta_and_prefills_generate_page(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->defaultTenantStudent([
            'email' => 'cert-final-test-cta@example.com',
        ]);
        $course = $this->createCourseForTenant($admin, 'curso-final-cert', 'Curso Final Cert');
        $finalTest = $this->createFinalTestForCourse($course);
        $completedAt = now()->subDay()->startOfDay();

        Enrollment::create([
            'system_setting_id' => $admin->system_setting_id,
            'course_id' => $course->id,
            'user_id' => $student->id,
            'completed_at' => $completedAt,
            'progress_percent' => 100,
            'access_status' => EnrollmentAccessStatus::ACTIVE->value,
        ]);

        FinalTestAttempt::create([
            'final_test_id' => $finalTest->id,
            'user_id' => $student->id,
            'score' => 85,
            'passed' => true,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(10),
            'attempted_at' => now()->subMinutes(10),
        ]);

        $expectedUrl = route('certificado.create', [
            'course_id' => $course->id,
            'completion_confirmed' => 'yes',
            'completion_date' => $completedAt->format('Y-m-d'),
        ]);

        $introResponse = $this->actingAs($student)->get(route('learning.courses.final-test.intro', $course));

        $introResponse->assertOk();
        $introResponse->assertSee('Você passou na prova!');
        $introResponse->assertSee('Sua nota foi 85%.');
        $introResponse->assertSee('Pegar meu certificado');
        $introResponse->assertSee('certificado/gerar?course_id='.$course->id, false);
        $introResponse->assertSee('completion_confirmed=yes', false);
        $introResponse->assertSee('completion_date='.$completedAt->format('Y-m-d'), false);

        $this->actingAs($student)->get($expectedUrl)->assertOk();

        Livewire::test(Checkout::class, [
            'courseId' => $course->id,
            'completionDate' => $completedAt->format('Y-m-d'),
            'completionConfirmed' => 'yes',
        ])
            ->assertSet('courseId', $course->id)
            ->assertSet('completionDate', $completedAt->format('Y-m-d'))
            ->assertSet('completionConfirmed', 'yes')
            ->assertSee($course->title)
            ->assertSee('Concluido');
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

        FinalTestQuestionOption::create([
            'final_test_question_id' => $question->id,
            'label' => 'Opcao incorreta',
            'is_correct' => false,
            'position' => 2,
        ]);

        return $finalTest;
    }
}
