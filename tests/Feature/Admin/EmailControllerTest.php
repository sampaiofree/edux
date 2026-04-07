<?php

namespace Tests\Feature\Admin;

use App\Mail\AdminAudienceMessage;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_can_open_email_composer_page(): void
    {
        $admin = $this->createAdminForTenant([
            'email' => 'admin-email-page@example.com',
        ]);
        $course = $this->createCourseForTenant($admin, 'curso-email-page', 'Curso Email Page');

        $this->forceTestHost($this->defaultTenantDomain())
            ->actingAs($admin)
            ->get(route('admin.email.create'))
            ->assertOk()
            ->assertSee('Enviar e-mail para alunos', false)
            ->assertSee($course->title, false);
    }

    public function test_admin_can_send_email_to_all_students_in_current_tenant_only(): void
    {
        Mail::fake();

        $admin = $this->createAdminForTenant([
            'email' => 'admin-email-all@example.com',
        ]);

        $studentA = $this->createStudentForTenant($admin, [
            'email' => 'aluno-a@example.com',
            'name' => 'Aluno A',
        ]);
        $studentB = $this->createStudentForTenant($admin, [
            'email' => 'aluno-b@example.com',
            'name' => 'Aluno B',
        ]);
        $this->createStudentForTenant($admin, [
            'email' => '',
            'name' => 'Aluno Sem Email',
        ]);

        $otherTenantAdmin = User::factory()->admin()->create([
            'email' => 'admin-outro-tenant@example.com',
        ]);
        $otherStudent = User::factory()->student()->create([
            'system_setting_id' => $otherTenantAdmin->system_setting_id,
            'email' => 'aluno-outro-tenant@example.com',
            'name' => 'Aluno Outro Tenant',
        ]);

        $this->forceTestHost($this->defaultTenantDomain())
            ->actingAs($admin)
            ->post(route('admin.email.store'), [
                'subject' => 'Comunicado geral',
                'body' => "Linha 1\nLinha 2",
                'audience' => 'all',
                'button_text' => 'Abrir plataforma',
                'button_url' => 'https://example.com/plataforma',
            ])
            ->assertRedirect(route('admin.email.create'));

        Mail::assertSent(AdminAudienceMessage::class, 2);
        Mail::assertSent(AdminAudienceMessage::class, fn (AdminAudienceMessage $mail): bool => $mail->hasTo($studentA->email));
        Mail::assertSent(AdminAudienceMessage::class, fn (AdminAudienceMessage $mail): bool => $mail->hasTo($studentB->email));
        Mail::assertNotSent(AdminAudienceMessage::class, fn (AdminAudienceMessage $mail): bool => $mail->hasTo($admin->email));
        Mail::assertNotSent(AdminAudienceMessage::class, fn (AdminAudienceMessage $mail): bool => $mail->hasTo($otherStudent->email));
    }

    public function test_admin_can_send_email_filtered_by_course(): void
    {
        Mail::fake();

        $admin = $this->createAdminForTenant([
            'email' => 'admin-email-course@example.com',
        ]);
        $courseA = $this->createCourseForTenant($admin, 'curso-email-a', 'Curso Email A');
        $courseB = $this->createCourseForTenant($admin, 'curso-email-b', 'Curso Email B');

        $studentA = $this->createStudentForTenant($admin, [
            'email' => 'aluno-curso-a@example.com',
            'name' => 'Aluno Curso A',
        ]);
        $studentB = $this->createStudentForTenant($admin, [
            'email' => 'aluno-curso-b@example.com',
            'name' => 'Aluno Curso B',
        ]);

        Enrollment::create([
            'course_id' => $courseA->id,
            'user_id' => $studentA->id,
        ]);

        Enrollment::create([
            'course_id' => $courseB->id,
            'user_id' => $studentB->id,
        ]);

        $this->forceTestHost($this->defaultTenantDomain())
            ->actingAs($admin)
            ->post(route('admin.email.store'), [
                'subject' => 'Aviso do curso A',
                'body' => 'Conteúdo segmentado',
                'audience' => 'course',
                'course_id' => $courseA->id,
                'button_text' => '',
                'button_url' => '',
            ])
            ->assertRedirect(route('admin.email.create'));

        Mail::assertSent(AdminAudienceMessage::class, 1);
        Mail::assertSent(AdminAudienceMessage::class, fn (AdminAudienceMessage $mail): bool => $mail->hasTo($studentA->email));
        Mail::assertNotSent(AdminAudienceMessage::class, fn (AdminAudienceMessage $mail): bool => $mail->hasTo($studentB->email));
    }

    private function createCourseForTenant(User $owner, string $slug, string $title): Course
    {
        return Course::create([
            'system_setting_id' => $owner->system_setting_id,
            'owner_id' => $owner->id,
            'title' => $title,
            'slug' => $slug,
            'summary' => 'Resumo do curso',
            'description' => 'Descrição do curso',
            'status' => 'published',
        ]);
    }
}
