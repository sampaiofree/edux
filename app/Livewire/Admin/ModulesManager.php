<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ModulesManager extends Component
{
    public Course $course;

    public int $lessonsComponentsVersion = 0;

    public bool $showForm = false;

    public ?Module $editingModule = null;

    public array $form = [
        'title' => '',
        'description' => null,
        'position' => 1,
    ];

    protected $listeners = [
        'moduleLessonsChanged' => 'refreshCourse',
    ];

    public function mount(int $courseId): void
    {
        $this->course = Course::with([
            'modules' => fn ($query) => $query
                ->with(['lessons' => fn ($lessonQuery) => $lessonQuery->orderBy('position')])
                ->orderBy('position'),
        ])->findOrFail($courseId);

        abort_unless($this->canManageCourse(), 403);
        $this->form['position'] = $this->nextPosition();
    }

    public function render(): View
    {
        return view('livewire.admin.modules-manager', [
            'modules' => $this->course->modules,
            'metrics' => $this->metrics,
        ]);
    }

    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string'],
            'form.position' => ['required', 'integer', 'min:1'],
        ];
    }

    public function newModule(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editModule(int $moduleId): void
    {
        $module = $this->course->modules->firstWhere('id', $moduleId);

        if (! $module) {
            return;
        }

        $this->editingModule = $module;
        $this->form = [
            'title' => $module->title,
            'description' => $module->description,
            'position' => $module->position,
        ];
        $this->showForm = true;
    }

    public function saveModule(): void
    {
        $this->validate();

        $payload = $this->payload();
        $message = 'Modulo criado.';

        if ($this->editingModule) {
            $this->editingModule->update($payload);
            $message = 'Modulo atualizado.';
        } else {
            $this->course->modules()->create($payload);
        }

        $this->normalizeModules();
        $this->closeForm();
        session()->flash('status', $message);
    }

    public function deleteModule(int $moduleId): void
    {
        $module = $this->course->modules->firstWhere('id', $moduleId);

        if (! $module) {
            return;
        }

        $module->delete();
        $this->normalizeModules();
        $this->closeForm();
        session()->flash('status', 'Modulo removido.');
    }

    public function moveModule(int $moduleId, string $direction): void
    {
        $modules = $this->course->modules->sortBy('position')->values();
        $currentIndex = $modules->search(fn (Module $module) => $module->id === $moduleId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($modules[$targetIndex])) {
            return;
        }

        DB::transaction(function () use ($modules, $currentIndex, $targetIndex): void {
            $currentModule = $modules[$currentIndex];
            $targetModule = $modules[$targetIndex];

            $currentPosition = $currentModule->position;
            $currentModule->update(['position' => $targetModule->position]);
            $targetModule->update(['position' => $currentPosition]);
        });

        $this->refreshCourse();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function refreshCourse(): void
    {
        $this->course->refresh()->load([
            'modules' => fn ($query) => $query
                ->with(['lessons' => fn ($lessonQuery) => $lessonQuery->orderBy('position')])
                ->orderBy('position'),
        ]);

        // Force nested lessons managers to remount after any module/lesson refresh.
        // Using a version counter avoids relying on second-precision updated_at timestamps.
        $this->lessonsComponentsVersion++;

        if (! $this->editingModule) {
            $this->form['position'] = $this->nextPosition();
        }
    }

    public function getMetricsProperty(): array
    {
        $lessons = $this->course->modules->flatMap(function (Module $module) {
            return $module->lessons;
        });

        return [
            'modules' => $this->course->modules->count(),
            'lessons' => $lessons->count(),
            'duration' => (int) $lessons->sum(fn ($lesson) => $lesson->duration_minutes ?? 0),
        ];
    }

    private function payload(): array
    {
        return [
            'title' => $this->form['title'],
            'description' => $this->form['description'] ?: null,
            'position' => $this->form['position'],
        ];
    }

    private function resetForm(): void
    {
        $this->editingModule = null;
        $this->form = [
            'title' => '',
            'description' => null,
            'position' => $this->nextPosition(),
        ];
    }

    private function nextPosition(): int
    {
        return ($this->course->modules->max('position') ?? 0) + 1;
    }

    private function normalizeModules(): void
    {
        $ordered = $this->course->modules()->orderBy('position')->get();

        DB::transaction(function () use ($ordered): void {
            $ordered->values()->each(function (Module $module, int $index): void {
                $module->update(['position' => $index + 1]);
            });
        });

        $this->refreshCourse();
    }

    private function canManageCourse(): bool
    {
        $user = Auth::user();

        return $user && $user->isAdmin();
    }
}
