<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\FinalTest;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Services\GlobalCourseImportService;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $importSearch = '';
    public int $perPage = 5;
    public bool $importModalOpen = false;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function openImportModal(): void
    {
        abort_unless($this->canImportCourses(), 403);

        $this->importModalOpen = true;
        $this->importSearch = '';
    }

    public function closeImportModal(): void
    {
        $this->importModalOpen = false;
        $this->importSearch = '';
    }

    public function importCourse(int $courseId): void
    {
        abort_unless($this->canImportCourses(), 403);

        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $sourceCourse = Course::withoutGlobalScopes()
            ->whereKey($courseId)
            ->where('is_global', true)
            ->firstOrFail();

        app(GlobalCourseImportService::class)->import($sourceCourse, $user);

        $this->closeImportModal();
        $this->resetPage();

        session()->flash('status', 'Curso importado com sucesso.');
    }

    public function render()
    {
        $stats = [
            'courses' => Course::count(),
            'modules' => Module::count(),
            'lessons' => Lesson::count(),
            'final_tests' => FinalTest::count(),
        ];

        $courses = Course::with(['owner', 'modules.lessons', 'finalTest'])
            ->when($this->search, fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $globalCourses = $this->importModalOpen && $this->canImportCourses()
            ? Course::withoutGlobalScopes()
                ->with(['systemSetting', 'owner'])
                ->where('is_global', true)
                ->when($this->importSearch, function ($query): void {
                    $query->where(function ($nestedQuery): void {
                        $nestedQuery
                            ->where('title', 'like', '%'.$this->importSearch.'%')
                            ->orWhere('slug', 'like', '%'.$this->importSearch.'%');
                    });
                })
                ->orderBy('title')
                ->get()
            : collect();

        return view('livewire.admin.dashboard', [
            'stats' => $stats,
            'courses' => $courses,
            'globalCourses' => $globalCourses,
            'canImportCourses' => $this->canImportCourses(),
        ]);
    }

    private function canImportCourses(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAdminPrivileges();
    }
}
