<?php

namespace App\Livewire\Student;

use App\Enums\EnrollmentAccessStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public int $userId;
    public string $search = '';
    public string $status = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
    ];

    public function mount(int $userId): void
    {
        $this->userId = $userId;
    }

    public function render()
    {
        $user = User::findOrFail($this->userId);
        $enrollments = $this->enrollments();
        $hasAnyEnrollment = $this->hasAnyEnrollment();

        return view('livewire.student.dashboard', [
            'user' => $user,
            'enrollments' => $enrollments,
            'freeCourses' => $hasAnyEnrollment ? collect() : $this->freeCourses(),
            'hasAnyEnrollment' => $hasAnyEnrollment,
        ]);
    }

    public function enrollFreeCourse(int $courseId)
    {
        $user = User::findOrFail($this->userId);
        abort_unless($user->isStudent(), 403);

        $course = Course::query()
            ->whereKey($courseId)
            ->where('status', 'published')
            ->where('access_mode', Course::ACCESS_MODE_FREE)
            ->firstOrFail();

        $enrollment = Enrollment::query()->firstOrCreate(
            [
                'course_id' => $course->id,
                'user_id' => $user->id,
            ],
            [
                'system_setting_id' => $course->system_setting_id,
                'progress_percent' => 0,
                'access_status' => EnrollmentAccessStatus::ACTIVE->value,
            ]
        );

        if (! $enrollment->wasRecentlyCreated) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('learning.courses.show', $course);
    }

    protected function enrollments(): Collection
    {
        $query = Enrollment::with([
            'course.owner',
            'course.modules.lessons' => fn ($q) => $q->orderBy('position'),
            'course.finalTest',
            'course.certificates' => fn ($q) => $q->where('user_id', $this->userId),
            'user',
        ])
            ->where('user_id', $this->userId)
            ->accessible();

        if ($this->search !== '') {
            $query->whereHas('course', fn ($course) => $course->where('title', 'like', '%'.$this->search.'%'));
        }

        $collection = $query->get();

        if ($this->status === 'running') {
            $collection = $collection->filter(fn ($enrollment) => ($enrollment->progress_percent ?? 0) < 100);
        } elseif ($this->status === 'completed') {
            $collection = $collection->filter(fn ($enrollment) => ($enrollment->progress_percent ?? 0) >= 100);
        }

        return $collection->values();
    }

    protected function hasAnyEnrollment(): bool
    {
        return Enrollment::query()
            ->where('user_id', $this->userId)
            ->exists();
    }

    protected function freeCourses(): Collection
    {
        return Course::query()
            ->withCount(['enrollments', 'lessons'])
            ->where('status', 'published')
            ->where('access_mode', Course::ACCESS_MODE_FREE)
            ->whereDoesntHave('enrollments', fn ($query) => $query->where('user_id', $this->userId))
            ->orderBy('title')
            ->get();
    }
}
