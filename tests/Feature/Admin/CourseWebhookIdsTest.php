<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\CourseWebhookId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseWebhookIdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_admin_create_page_renders_course_webhook_ids_section(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('courses.create'));

        $response->assertOk();
        $response->assertSee('IDs de webhook', false);
        $response->assertSee('ID de webhook', false);
        $response->assertSee('Plataforma (opcional)', false);
        $response->assertSee('Adicionar', false);
    }

    public function test_admin_edit_page_renders_existing_course_webhook_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->makeCourse($admin, [
            'title' => 'Curso com IDs',
            'slug' => 'curso-com-ids',
        ]);

        $course->courseWebhookIds()->createMany([
            [
                'webhook_id' => 'HOT-123',
                'platform' => 'Hotmart',
            ],
            [
                'webhook_id' => 'EDU-456',
                'platform' => null,
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('courses.edit', $course));

        $response->assertOk();
        $response->assertSee('IDs de webhook', false);
        $response->assertSee('HOT-123', false);
        $response->assertSee('Hotmart', false);
        $response->assertSee('EDU-456', false);
    }

    public function test_admin_store_persists_course_webhook_ids(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('courses.store'), $this->coursePayload([
            'title' => 'Curso Persistido',
            'curso_webhook_ids' => [
                [
                    'webhook_id' => ' HOT-123 ',
                    'platform' => 'Hotmart',
                ],
                [
                    'webhook_id' => 'EDU-456',
                    'platform' => '',
                ],
            ],
        ]));

        $course = Course::query()->where('title', 'Curso Persistido')->firstOrFail();

        $response->assertRedirect(route('courses.edit', $course));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('curso_webhook_ids', [
            'course_id' => $course->id,
            'webhook_id' => 'HOT-123',
            'platform' => 'Hotmart',
        ]);

        $this->assertDatabaseHas('curso_webhook_ids', [
            'course_id' => $course->id,
            'webhook_id' => 'EDU-456',
            'platform' => null,
        ]);
    }

    public function test_admin_update_replaces_existing_course_webhook_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->makeCourse($admin, [
            'title' => 'Curso Original',
            'slug' => 'curso-original-webhook',
        ]);

        $course->courseWebhookIds()->createMany([
            [
                'webhook_id' => 'OLD-1',
                'platform' => 'Hotmart',
            ],
            [
                'webhook_id' => 'OLD-2',
                'platform' => null,
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('courses.update.post', $course), $this->coursePayload([
            'title' => 'Curso Atualizado',
            'curso_webhook_ids' => [
                [
                    'webhook_id' => 'NEW-1',
                    'platform' => 'Eduzz',
                ],
            ],
        ]));

        $response->assertRedirect(route('courses.edit', $course->fresh()));
        $response->assertSessionHasNoErrors();

        $course->refresh();

        $this->assertSame('Curso Atualizado', $course->title);
        $this->assertDatabaseMissing('curso_webhook_ids', [
            'course_id' => $course->id,
            'webhook_id' => 'OLD-1',
        ]);
        $this->assertDatabaseMissing('curso_webhook_ids', [
            'course_id' => $course->id,
            'webhook_id' => 'OLD-2',
        ]);
        $this->assertDatabaseHas('curso_webhook_ids', [
            'course_id' => $course->id,
            'webhook_id' => 'NEW-1',
            'platform' => 'Eduzz',
        ]);
        $this->assertSame(1, $course->courseWebhookIds()->count());
    }

    public function test_admin_store_rejects_duplicate_course_webhook_ids_in_same_payload(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->from(route('courses.create'))
            ->actingAs($admin)
            ->post(route('courses.store'), $this->coursePayload([
                'title' => 'Curso Duplicado',
                'curso_webhook_ids' => [
                    [
                        'webhook_id' => 'DUP-1',
                        'platform' => 'Hotmart',
                    ],
                    [
                        'webhook_id' => ' DUP-1 ',
                        'platform' => 'Eduzz',
                    ],
                ],
            ]));

        $response->assertRedirect(route('courses.create'));
        $response->assertSessionHasErrors('curso_webhook_ids');
        $this->assertDatabaseMissing('courses', [
            'title' => 'Curso Duplicado',
        ]);
        $this->assertDatabaseCount('curso_webhook_ids', 0);
    }

    public function test_different_courses_can_reuse_same_webhook_id(): void
    {
        $admin = User::factory()->admin()->create();
        $firstCourse = $this->makeCourse($admin, [
            'title' => 'Curso Um',
            'slug' => 'curso-um',
        ]);

        $firstCourse->courseWebhookIds()->create([
            'webhook_id' => 'GLOBAL-1',
            'platform' => 'Hotmart',
        ]);

        $response = $this->actingAs($admin)->post(route('courses.store'), $this->coursePayload([
            'title' => 'Curso Dois',
            'curso_webhook_ids' => [
                [
                    'webhook_id' => 'GLOBAL-1',
                    'platform' => 'Eduzz',
                ],
            ],
        ]));

        $secondCourse = Course::query()->where('title', 'Curso Dois')->firstOrFail();

        $response->assertRedirect(route('courses.edit', $secondCourse));
        $response->assertSessionHasNoErrors();

        $this->assertSame(2, CourseWebhookId::query()->where('webhook_id', 'GLOBAL-1')->count());
        $this->assertDatabaseHas('curso_webhook_ids', [
            'course_id' => $firstCourse->id,
            'webhook_id' => 'GLOBAL-1',
            'platform' => 'Hotmart',
        ]);
        $this->assertDatabaseHas('curso_webhook_ids', [
            'course_id' => $secondCourse->id,
            'webhook_id' => 'GLOBAL-1',
            'platform' => 'Eduzz',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function coursePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Curso Teste',
            'summary' => 'Resumo breve',
            'description' => 'Descricao do curso',
            'status' => 'draft',
            'duration_minutes' => 180,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCourse(User $owner, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'owner_id' => $owner->id,
            'title' => 'Curso Teste',
            'slug' => 'curso-teste',
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'draft',
            'duration_minutes' => 120,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ], $overrides));
    }
}
