<?php

namespace App\Livewire\Student;

use App\Models\Enrollment;
use App\Models\User;
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

        return view('livewire.student.dashboard', [
            'user' => $user,
            'enrollments' => $this->enrollments(),
        ]);
    }

    protected function enrollments()
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
}
