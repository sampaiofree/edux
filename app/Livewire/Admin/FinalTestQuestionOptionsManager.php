<?php

namespace App\Livewire\Admin;

use App\Models\FinalTestQuestion;
use App\Models\FinalTestQuestionOption;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FinalTestQuestionOptionsManager extends Component
{
    public FinalTestQuestion $question;
    public ?FinalTestQuestionOption $editingOption = null;

    public string $label = '';
    public int $position = 1;
    public bool $is_correct = false;
    public bool $showForm = false;

    protected $casts = [
        'showForm' => 'boolean',
    ];

    public function mount(int $questionId): void
    {
        $this->question = FinalTestQuestion::with('finalTest.course', 'options')->findOrFail($questionId);
        $this->authorizeUser();
        $this->position = $this->nextPosition();
    }

    public function render()
    {
        return view('livewire.admin.final-test-question-options-manager');
    }

    public function showCreateForm(): void
    {
        $this->resetForm(false);
        $this->showForm = true;
    }

    public function createOption(): void
    {
        $this->authorizeUser();

        $data = $this->validate($this->rules());
        $data['is_correct'] = $this->is_correct;

        $option = $this->question->options()->create($data);
        $this->syncCorrectOption($option);
        $this->ensureHasCorrectOption($option);

        $this->resetForm();
        $this->refreshQuestion();
        $this->showForm = false;

        session()->flash('status', 'Opcao adicionada.');
    }

    public function updateOption(): void
    {
        $this->authorizeUser();

        if (! $this->editingOption) {
            return;
        }

        $data = $this->validate($this->rules());
        $data['is_correct'] = $this->is_correct;

        $this->editingOption->update($data);
        $this->syncCorrectOption($this->editingOption);
        $this->ensureHasCorrectOption($this->editingOption);

        $this->resetForm();
        $this->refreshQuestion();
        $this->showForm = false;

        session()->flash('status', 'Opcao atualizada.');
    }

    public function startEdit(int $optionId): void
    {
        $this->authorizeUser();

        $option = $this->question->options->firstWhere('id', $optionId);

        if (! $option) {
            return;
        }

        $this->editingOption = $option;
        $this->fill([
            'label' => $option->label,
            'position' => $option->position,
            'is_correct' => $option->is_correct,
        ]);
        $this->showForm = true;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function deleteOption(int $optionId): void
    {
        $this->authorizeUser();

        $option = $this->question->options->firstWhere('id', $optionId);

        if (! $option) {
            return;
        }

        $wasCorrect = $option->is_correct;
        $option->delete();

        if ($wasCorrect) {
            $next = $this->question->options()->orderBy('position')->first();
            if ($next) {
                $next->update(['is_correct' => true]);
            }
        }

        $this->resetForm();
        $this->refreshQuestion();
    }

    public function moveOption(int $optionId, string $direction): void
    {
        $this->authorizeUser();

        $options = $this->question->options->sortBy('position')->values();
        $currentIndex = $options->search(fn ($option) => $option->id === $optionId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        $target = $options->get($targetIndex);

        if (! $target) {
            return;
        }

        $current = $options->get($currentIndex);
        $currentPosition = $current->position;

        $current->update(['position' => $target->position]);
        $target->update(['position' => $currentPosition]);

        $this->refreshQuestion();
    }

    private function refreshQuestion(): void
    {
        $this->question->refresh();
        $this->question->load('options', 'finalTest.course');
        $this->position = $this->nextPosition();
        $this->dispatch('question-options-updated');
    }

    private function resetForm(bool $closeModal = true): void
    {
        $this->editingOption = null;
        $this->fill([
            'label' => '',
            'position' => $this->nextPosition(),
            'is_correct' => false,
        ]);

        if ($closeModal) {
            $this->showForm = false;
        }
    }

    private function nextPosition(): int
    {
        return ($this->question->options->max('position') ?? 0) + 1;
    }

    private function authorizeUser(): void
    {
        $user = Auth::user();
        $course = $this->question->finalTest->course;

        if (
            $user
            && $user->hasAdminPrivileges()
            && $user->canAccessSystemSetting($course->system_setting_id)
        ) {
            return;
        }

        abort(403);
    }

    private function syncCorrectOption(FinalTestQuestionOption $option): void
    {
        if (! $option->is_correct) {
            return;
        }

        $this->question->options()
            ->whereKeyNot($option->id)
            ->update(['is_correct' => false]);
    }

    private function ensureHasCorrectOption(FinalTestQuestionOption $fallback): void
    {
        if ($this->question->options()->where('is_correct', true)->exists()) {
            return;
        }

        $fallback->update(['is_correct' => true]);
    }

    private function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
            'is_correct' => ['boolean'],
        ];
    }
}
