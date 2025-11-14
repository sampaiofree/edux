<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\FinalTest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FinalTestManager extends Component
{
    public Course $course;
    public ?FinalTest $finalTest = null;

    public string $title = '';
    public ?string $instructions = null;
    public int $passing_score = 70;
    public int $max_attempts = 1;
    public ?int $duration_minutes = null;
    public bool $showForm = false;
    public bool $confirmingDelete = false;

    protected $listeners = [
        'final-test-reload-requested' => 'refreshFinalTest',
    ];

    protected $casts = [
        'showForm' => 'boolean',
    ];

    public function mount(int $courseId): void
    {
        $this->course = Course::with('finalTest.questions.options')->findOrFail($courseId);
        $this->finalTest = $this->course->finalTest;

        if ($this->finalTest) {
            $this->fillFormFromModel();
        }

        $this->showForm = ! $this->finalTest;
    }

    public function save(): void
    {
        $this->authorizeUser();

        $data = $this->validate($this->rules());

        if ($this->finalTest) {
            $this->finalTest->update($data);
            $message = 'Teste final atualizado.';
        } else {
            $this->finalTest = $this->course->finalTest()->create($data);
            $message = 'Teste final criado.';
        }

        $this->refreshFinalTest();
        $this->showForm = false;

        session()->flash('status', $message);
    }

    public function deleteFinalTest(): void
    {
        $this->authorizeUser();

        if (! $this->finalTest) {
            return;
        }

        $this->finalTest->delete();
        $this->finalTest = null;

        $this->resetForm();
        $this->refreshFinalTest();
        $this->showForm = false;
        $this->confirmingDelete = false;

        session()->flash('status', 'Teste final removido.');
    }

    public function openForm(): void
    {
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.final-test-manager', [
            'metrics' => $this->metrics,
        ]);
    }

    private function authorizeUser(): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTeacher() && $this->course->owner_id === $user->id) {
            return;
        }

        abort(403);
    }

    public function refreshFinalTest(): void
    {
        $this->course->refresh();
        $this->course->load('finalTest.questions.options');
        $this->finalTest = $this->course->finalTest;

        if ($this->finalTest) {
            $this->fillFormFromModel();
        }
    }

    private function fillFormFromModel(): void
    {
        $this->fill([
            'title' => $this->finalTest->title ?? '',
            'instructions' => $this->finalTest->instructions,
            'passing_score' => $this->finalTest->passing_score ?? 70,
            'max_attempts' => $this->finalTest->max_attempts ?? 1,
            'duration_minutes' => $this->finalTest->duration_minutes,
        ]);
    }

    private function resetForm(): void
    {
        $this->fill([
            'title' => '',
            'instructions' => null,
            'passing_score' => 70,
            'max_attempts' => 1,
            'duration_minutes' => null,
        ]);
    }

    public function getMetricsProperty(): array
    {
        return [
            'questions' => $this->finalTest?->questions->count() ?? 0,
            'passing_score' => $this->finalTest?->passing_score ?? 0,
            'max_attempts' => $this->finalTest?->max_attempts ?? 0,
            'duration' => $this->finalTest?->duration_minutes,
        ];
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'duration_minutes' => ['nullable', 'integer', 'min:5'],
        ];
    }
}
