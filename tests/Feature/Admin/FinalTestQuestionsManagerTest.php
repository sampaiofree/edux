<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\FinalTestQuestionsManager;
use App\Models\Course;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalTestQuestionsManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_question_options_updated_event_reloads_questions_component_without_error(): void
    {
        $admin = $this->defaultTenantAdmin();
        $course = Course::create([
            'system_setting_id' => $admin->system_setting_id,
            'owner_id' => $admin->id,
            'title' => 'Curso Prova Final',
            'slug' => 'curso-prova-final',
            'summary' => 'Resumo',
            'description' => 'Descricao',
            'status' => 'draft',
            'duration_minutes' => 90,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ]);

        $finalTest = $course->finalTest()->create([
            'title' => 'Teste Final',
            'instructions' => 'Leia tudo.',
            'passing_score' => 70,
            'max_attempts' => 1,
            'duration_minutes' => 20,
        ]);

        $finalTest->questions()->create([
            'title' => 'Pergunta 1',
            'statement' => 'Enunciado',
            'position' => 1,
            'weight' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(FinalTestQuestionsManager::class, ['finalTestId' => $finalTest->id])
            ->assertSee('Pergunta 1')
            ->dispatch('question-options-updated')
            ->assertSee('Pergunta 1');
    }
}
