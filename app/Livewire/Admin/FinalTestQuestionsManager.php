<?php

namespace App\Livewire\Admin;

use App\Models\FinalTest;
use App\Models\FinalTestQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FinalTestQuestionsManager extends Component
{
    public FinalTest $finalTest;
    public ?FinalTestQuestion $editingQuestion = null;

    public string $title = '';
    public ?string $statement = null;
    public int $position = 1;
    public int $weight = 1;
    public bool $showForm = false;

    protected $listeners = [
        'question-options-updated' => 'reloadFinalTest',
    ];

    protected $casts = [
        'showForm' => 'boolean',
    ];

    public function mount(int $finalTestId): void
    {
        $this->finalTest = FinalTest::with('course', 'questions.options')->findOrFail($finalTestId);
        $this->authorizeUser();
        $this->position = $this->nextPosition();
    }

    public function render()
    {
        return view('livewire.admin.final-test-questions-manager', [
            'questions' => $this->questions,
        ]);
    }

    public function getQuestionsProperty(): Collection
    {
        return $this->finalTest->questions->sortBy('position');
    }

    public function showCreateForm(): void
    {
        $this->resetForm(false);
        $this->showForm = true;
    }

    public function createQuestion(): void
    {
        $this->authorizeUser();

        $data = $this->validate($this->rules());

        $this->finalTest->questions()->create($data);
        $this->reloadFinalTest();
        $this->resetForm();

        session()->flash('status', 'Questao adicionada.');
    }

    public function startEdit(int $questionId): void
    {
        $this->authorizeUser();

        $question = $this->finalTest->questions->firstWhere('id', $questionId);

        if (! $question) {
            return;
        }

        $this->editingQuestion = $question;
        $this->fill([
            'title' => $question->title,
            'statement' => $question->statement,
            'position' => $question->position,
            'weight' => $question->weight,
        ]);
        $this->showForm = true;
    }

    public function updateQuestion(): void
    {
        $this->authorizeUser();

        if (! $this->editingQuestion) {
            return;
        }

        $data = $this->validate($this->rules());

        $this->editingQuestion->update($data);
        $this->reloadFinalTest();
        $this->resetForm();

        session()->flash('status', 'Questao atualizada.');
    }

    public function deleteQuestion(int $questionId): void
    {
        $this->authorizeUser();

        $question = $this->finalTest->questions->firstWhere('id', $questionId);

        if (! $question) {
            return;
        }

        $question->delete();
        $this->reloadFinalTest();
        $this->resetForm();

        session()->flash('status', 'Questao removida.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function moveQuestion(int $questionId, string $direction): void
    {
        $questions = $this->questions->values();
        $currentIndex = $questions->search(fn ($question) => $question->id === $questionId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        $target = $questions->get($targetIndex);

        if (! $target) {
            return;
        }

        $current = $questions->get($currentIndex);
        $currentPosition = $current->position;

        $current->update(['position' => $target->position]);
        $target->update(['position' => $currentPosition]);

        $this->reloadFinalTest();
    }

    public function reorderQuestions(array $order): void
    {
        foreach ($order as $item) {
            FinalTestQuestion::whereKey($item['value'])->update(['position' => $item['order'] + 1]);
        }

        $this->reloadFinalTest();
    }

    public function reloadFinalTest(): void
    {
        $this->finalTest->refresh();
        $this->finalTest->load('course', 'questions.options');
        $this->dispatch('final-test-reload-requested');
    }

    private function resetForm(bool $closeModal = true): void
    {
        $this->editingQuestion = null;
        $this->fill([
            'title' => '',
            'statement' => null,
            'position' => $this->nextPosition(),
            'weight' => 1,
        ]);

        if ($closeModal) {
            $this->showForm = false;
        }
    }

    private function nextPosition(): int
    {
        return ($this->finalTest->questions->max('position') ?? 0) + 1;
    }

    private function authorizeUser(): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        abort(403);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'statement' => ['nullable', 'string'],
            'position' => ['required', 'integer', 'min:1'],
            'weight' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }
}
