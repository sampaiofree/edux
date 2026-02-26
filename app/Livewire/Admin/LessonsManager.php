<?php

namespace App\Livewire\Admin;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use Livewire\Component;

class LessonsManager extends Component
{
    use WithFileUploads;

    public Module $module;

    public bool $showForm = false;
    public bool $showImport = false;

    public ?Lesson $editingLesson = null;

    public array $form = [
        'module_id' => null,
        'title' => '',
        'content' => null,
        'video_url' => null,
        'duration_minutes' => null,
        'position' => 1,
    ];

    #[Validate('nullable|file|max:2048|mimes:csv,txt')]
    public $csvUpload = null;

    public ?string $csvText = null;
    public bool $hasHeader = true;
    public string $separator = 'auto';
    public string $importSource = 'file'; // file or text
    public ?int $moduleOverride = null;
    public array $columns = [];
    public array $mapping = [];
    public array $previewRows = [];

    protected function rules(): array
    {
        return [
            'form.module_id' => ['required', 'integer', 'exists:modules,id'],
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
        $this->form['module_id'] = $this->module->id;
        $this->moduleOverride = $this->module->id;
    }

    public function render(): View
    {
        return view('livewire.admin.lessons-manager', [
            'lessons' => $this->module->lessons->sortBy('position'),
            'modulesList' => $this->module->course?->modules()->orderBy('position')->get() ?? collect(),
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
            'module_id' => $this->module->id,
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
        if (! $this->isValidTargetModule((int) $payload['module_id'])) {
            $this->addError('form.module_id', 'Selecione um modulo valido para este curso.');
            return;
        }

        $message = 'Aula criada.';

        if ($this->editingLesson) {
            $originalModuleId = (int) $this->editingLesson->module_id;
            $targetModuleId = (int) $payload['module_id'];

            $this->editingLesson->update($payload);
            $message = 'Aula atualizada.';

            if ($originalModuleId !== $targetModuleId) {
                $this->normalizeModuleLessons($originalModuleId);
                $this->normalizeModuleLessons($targetModuleId);
                $this->refreshModule();
            } else {
                $this->normalizeLessons();
            }
        } else {
            Lesson::create($payload);

            if ((int) $payload['module_id'] === (int) $this->module->id) {
                $this->normalizeLessons();
            } else {
                $this->normalizeModuleLessons((int) $payload['module_id']);
                $this->refreshModule();
            }
        }
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

    public function openImport(): void
    {
        $this->resetImportState();
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->showImport = false;
        $this->resetImportState();
    }

    public function analyzeCsv(): void
    {
        $content = null;

        if ($this->importSource === 'file' && $this->csvUpload) {
            $this->validateOnly('csvUpload');
            $content = @file_get_contents($this->csvUpload->getRealPath());
        } elseif ($this->importSource === 'text' && $this->csvText) {
            $content = $this->csvText;
        }

        if (! $content) {
            $this->addError('csv', 'Envie um arquivo ou cole o texto do CSV.');
            return;
        }

        [$columns, $rows] = $this->parseCsv($content, $this->separator, $this->hasHeader);

        if (empty($columns)) {
            $this->addError('csv', 'Nao foi possivel identificar colunas no CSV.');
            return;
        }

        $this->columns = $columns;
        $this->previewRows = array_slice($rows, 0, 5);
        $this->mapping = $this->guessMapping($columns);
    }

    public function importLessons(): void
    {
        if (empty($this->columns)) {
            $this->addError('csv', 'Analise o CSV antes de importar.');
            return;
        }

        if (! $this->moduleOverride && ! $this->module->course?->modules()->exists()) {
            $this->addError('moduleOverride', 'Selecione um modulo padrao antes de importar.');
            return;
        }

        $content = null;
        if ($this->importSource === 'file' && $this->csvUpload) {
            $content = @file_get_contents($this->csvUpload->getRealPath());
        } elseif ($this->importSource === 'text' && $this->csvText) {
            $content = $this->csvText;
        }

        if (! $content) {
            $this->addError('csv', 'Envie um arquivo ou cole o texto do CSV.');
            return;
        }

        [$columns, $rows] = $this->parseCsv($content, $this->separator, $this->hasHeader);
        if (empty($columns)) {
            $this->addError('csv', 'Nao foi possivel identificar colunas no CSV.');
            return;
        }

        if (! in_array('title', $this->mapping, true)) {
            $this->addError('mapping', 'Mapeie ao menos o campo Titulo.');
            return;
        }

        $modules = $this->module->course?->modules()->get() ?? collect();
        $modulesByTitle = $modules->keyBy(fn ($m) => mb_strtolower($m->title));
        $defaultModuleId = $this->moduleOverride ?: ($this->module->id ?? $modules->first()?->id);

        $imported = 0;
        $skipped = 0;

        $positionsCache = [];
        $touchedModules = [];

        foreach ($rows as $row) {
            $payload = $this->rowToLessonPayload($columns, $row);

            if (! $payload['title']) {
                $skipped++;
                continue;
            }

            $moduleId = $defaultModuleId;
            if (($this->mapping['module'] ?? null) && isset($payload['module_lookup'])) {
                $maybe = $modulesByTitle[mb_strtolower($payload['module_lookup'])] ?? null;
                if ($maybe) {
                    $moduleId = $maybe->id;
                }
            }

            $moduleId = $moduleId ?: $defaultModuleId;
            if (! $moduleId) {
                $skipped++;
                continue;
            }

            $position = $payload['position'] ?? null;
            if (! $position || $position < 1) {
                $positionsCache[$moduleId] = $positionsCache[$moduleId] ?? ($this->nextPositionForModule($moduleId));
                $position = $positionsCache[$moduleId];
                $positionsCache[$moduleId]++;
            }

            Lesson::create([
                'module_id' => $moduleId,
                'title' => $payload['title'],
                'content' => $payload['content'] ?? null,
                'video_url' => $payload['video_url'] ?? null,
                'duration_minutes' => $payload['duration_minutes'] ?? null,
                'position' => $position,
            ]);

            $imported++;
            $touchedModules[$moduleId] = true;
        }

        foreach (array_keys($touchedModules) as $moduleId) {
            $this->normalizeModuleLessons((int) $moduleId);
        }

        $this->closeImport();
        session()->flash('status', "Importacao concluida: {$imported} aulas criadas, {$skipped} ignoradas.");
        $this->dispatch('moduleLessonsChanged')->to(ModulesManager::class);
    }

    private function payload(): array
    {
        return [
            'module_id' => (int) $this->form['module_id'],
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
            'module_id' => $this->module->id,
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

    private function nextPositionForModule(int $moduleId): int
    {
        $module = $this->module->course?->modules()->with('lessons')->find($moduleId);

        if (! $module) {
            return 1;
        }

        return ($module->lessons->max('position') ?? 0) + 1;
    }

    private function normalizeModuleLessons(int $moduleId): void
    {
        $module = $this->module->course?->modules()->with('lessons')->find($moduleId);

        if (! $module) {
            return;
        }

        $ordered = $module->lessons()->orderBy('position')->get();

        DB::transaction(function () use ($ordered): void {
            $ordered->values()->each(function (Lesson $lesson, int $index): void {
                $lesson->update(['position' => $index + 1]);
            });
        });

        if ($moduleId === $this->module->id) {
            $this->refreshModule();
        }
    }

    private function canManageModule(): bool
    {
        $user = Auth::user();

        return $user && $user->isAdmin();
    }

    private function isValidTargetModule(int $moduleId): bool
    {
        if ($moduleId < 1) {
            return false;
        }

        return $this->module->course?->modules()->whereKey($moduleId)->exists() ?? false;
    }

    private function resetImportState(): void
    {
        $this->csvUpload = null;
        $this->csvText = null;
        $this->hasHeader = true;
        $this->separator = 'auto';
        $this->importSource = 'file';
        $this->moduleOverride = $this->module->id;
        $this->columns = [];
        $this->mapping = [];
        $this->previewRows = [];
        $this->resetErrorBag();
    }

    /**
     * @return array{0: array, 1: array}
     */
    private function parseCsv(string $content, string $separator, bool $hasHeader): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = array_values(array_filter(explode("\n", $content), fn ($line) => trim($line) !== ''));

        if (empty($lines)) {
            return [[], []];
        }

        $sep = $separator;
        if ($separator === 'auto') {
            $first = $lines[0];
            $countComma = substr_count($first, ',');
            $countSemicolon = substr_count($first, ';');
            $sep = $countSemicolon > $countComma ? ';' : ',';
        }

        $split = function (string $line) use ($sep): array {
            return array_map('trim', str_getcsv($line, $sep));
        };

        $rowsRaw = array_map($split, $lines);

        $columns = [];
        $dataRows = $rowsRaw;

        if ($hasHeader && count($rowsRaw) > 1) {
            $columns = $rowsRaw[0];
            $dataRows = array_slice($rowsRaw, 1);
        } else {
            $maxCols = max(array_map('count', $rowsRaw));
            $columns = array_map(fn ($idx) => 'col'.($idx + 1), range(0, $maxCols - 1));
        }

        return [$columns, $dataRows];
    }

    private function guessMapping(array $columns): array
    {
        $guesses = [];
        foreach ($columns as $index => $name) {
            $lower = mb_strtolower($name);
            if (str_contains($lower, 'titulo') || str_contains($lower, 'nome') || str_contains($lower, 'title')) {
                $guesses[$index] = 'title';
            } elseif (str_contains($lower, 'video')) {
                $guesses[$index] = 'video_url';
            } elseif (str_contains($lower, 'durac') || str_contains($lower, 'duration')) {
                $guesses[$index] = 'duration_minutes';
            } elseif (str_contains($lower, 'pos') || str_contains($lower, 'ordem')) {
                $guesses[$index] = 'position';
            } elseif (str_contains($lower, 'modulo') || str_contains($lower, 'module')) {
                $guesses[$index] = 'module';
            } elseif (str_contains($lower, 'conteudo') || str_contains($lower, 'content') || str_contains($lower, 'descricao')) {
                $guesses[$index] = 'content';
            }
        }

        return $guesses;
    }

    private function rowToLessonPayload(array $columns, array $row): array
    {
        $payload = [
            'title' => null,
            'content' => null,
            'video_url' => null,
            'duration_minutes' => null,
            'position' => null,
        ];

        foreach ($columns as $idx => $colName) {
            $value = $row[$idx] ?? null;
            $field = $this->mapping[$idx] ?? null;

            if (! $field || $value === null || $value === '') {
                continue;
            }

            switch ($field) {
                case 'title':
                    $payload['title'] = $value;
                    break;
                case 'content':
                    $payload['content'] = $value;
                    break;
                case 'video_url':
                    $payload['video_url'] = $value;
                    break;
                case 'duration_minutes':
                    $payload['duration_minutes'] = is_numeric($value) ? (int) $value : null;
                    break;
                case 'position':
                    $payload['position'] = is_numeric($value) ? (int) $value : null;
                    break;
                case 'module':
                    $payload['module_lookup'] = $value;
                    break;
                default:
                    break;
            }
        }

        return $payload;
    }
}
