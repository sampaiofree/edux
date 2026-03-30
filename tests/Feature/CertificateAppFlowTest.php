<?php

namespace Tests\Feature;

use App\Enums\EnrollmentAccessStatus;
use App\Livewire\Certificado\Checkout;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
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

    public function test_generate_certificate_streams_pdf_download_for_app_compatible_flow(): void
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
            ->assertFileDownloaded('certificado-'.$course->slug.'.pdf');

        $certificate = Certificate::query()
            ->where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        $this->assertNotNull($certificate->public_token);
        $this->assertNotNull($certificate->issued_at);

        $indexResponse = $this->actingAs($student)->get(route('certificado.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee($course->title);
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
}
