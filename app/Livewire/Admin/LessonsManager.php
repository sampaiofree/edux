<?php

namespace App\Livewire\Admin;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LessonsManager extends Component
{
    public Module $module;

    public bool $showForm = false;

    public ?Lesson $editingLesson = null;

    public array $form = [
        'title' => '',
        'content' => null,
        'video_url' => null,
        'duration_minutes' => null,
        'position' => 1,
    ];

    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.content' => ['nullable', 'string'],
            'form.video_url' => ['nullable', 'url'],
            'form.duration_minutes' => ['nullable', 'integer', 'min:1'],
            'form.position' => ['required', 'integer', 'min:1'],
        ];
    }

    public function mount(int $moduleId): void
    {
        $this->module = Module::with([
            'course',
            'lessons' => fn ($query) => $query->orderBy('position'),
        ])->findOrFail($moduleId);

        abort_unless($this->canManageModule(), 403);

        $this->form['position'] = $this->nextPosition();
    }

    public function render(): View
    {
        return view('livewire.admin.lessons-manager', [
            'lessons' => $this->module->lessons->sortBy('position'),
        ]);
    }

    public function newLesson(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editLesson(int $lessonId): void
    {
        $lesson = $this->module->lessons->firstWhere('id', $lessonId);

        if (! $lesson) {
            return;
        }

        $this->editingLesson = $lesson;
        $this->form = [
            'title' => $lesson->title,
            'content' => $lesson->content,
            'video_url' => $lesson->video_url,
            'duration_minutes' => $lesson->duration_minutes,
            'position' => $lesson->position,
        ];
        $this->showForm = true;
    }

    public function saveLesson(): void
    {
        $this->validate();

        $payload = $this->payload();
        $message = 'Aula criada.';

        if ($this->editingLesson) {
            $this->editingLesson->update($payload);
            $message = 'Aula atualizada.';
        } else {
            $this->module->lessons()->create($payload);
        }

        $this->normalizeLessons();
        $this->closeForm();
        session()->flash('status', $message);
        $this->dispatch('moduleLessonsChanged')->to(ModulesManager::class);
    }

    public function deleteLesson(int $lessonId): void
    {
        $lesson = $this->module->lessons->firstWhere('id', $lessonId);

        if (! $lesson) {
            return;
        }

        $lesson->delete();
        $this->normalizeLessons();
        $this->closeForm();
        session()->flash('status', 'Aula removida.');
        $this->dispatch('moduleLessonsChanged')->to(ModulesManager::class);
    }

    public function moveLesson(int $lessonId, string $direction): void
    {
        $lessons = $this->module->lessons->sortBy('position')->values();
        $currentIndex = $lessons->search(fn (Lesson $lesson) => $lesson->id === $lessonId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($lessons[$targetIndex])) {
            return;
        }

        DB::transaction(function () use ($lessons, $currentIndex, $targetIndex): void {
            $currentLesson = $lessons[$currentIndex];
            $targetLesson = $lessons[$targetIndex];

            $currentPosition = $currentLesson->position;
            $currentLesson->update(['position' => $targetLesson->position]);
            $targetLesson->update(['position' => $currentPosition]);
        });

        $this->refreshModule();
        $this->dispatch('moduleLessonsChanged')->to(ModulesManager::class);
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function payload(): array
    {
        return [
            'title' => $this->form['title'],
            'content' => $this->form['content'],
            'video_url' => $this->form['video_url'],
            'duration_minutes' => $this->form['duration_minutes'],
            'position' => $this->form['position'],
        ];
    }

    private function resetForm(): void
    {
        $this->editingLesson = null;
        $this->form = [
            'title' => '',
            'content' => null,
            'video_url' => null,
            'duration_minutes' => null,
            'position' => $this->nextPosition(),
        ];
    }

    private function normalizeLessons(): void
    {
        $ordered = $this->module->lessons()->orderBy('position')->get();

        DB::transaction(function () use ($ordered): void {
            $ordered->values()->each(function (Lesson $lesson, int $index): void {
                $lesson->update(['position' => $index + 1]);
            });
        });

        $this->refreshModule();
    }

    private function refreshModule(): void
    {
        $this->module->refresh()->load([
            'lessons' => fn ($query) => $query->orderBy('position'),
        ]);

        if (! $this->editingLesson) {
            $this->form['position'] = $this->nextPosition();
        }
    }

    private function nextPosition(): int
    {
        return ($this->module->lessons->max('position') ?? 0) + 1;
    }

    private function canManageModule(): bool
    {
        $user = Auth::user();
        $course = $this->module->course;

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher() && $course->owner_id === $user->id;
    }
}
