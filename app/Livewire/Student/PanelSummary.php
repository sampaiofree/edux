<?php

namespace App\Livewire\Student;

use App\Models\Enrollment;
use App\Models\User;
use Livewire\Component;

class PanelSummary extends Component
{
    public int $userId;

    public function mount(int $userId): void
    {
        $this->userId = $userId;
    }

    public function render()
    {
        $user = User::with([
            'systemSetting',
            'enrollments.course.certificates' => fn ($query) => $query->where('user_id', $this->userId),
        ])
            ->findOrFail($this->userId);

        $enrollments = Enrollment::with([
            'course.certificates' => fn ($query) => $query->where('user_id', $this->userId),
            'course.modules.lessons' => fn ($query) => $query->orderBy('position'),
        ])
            ->where('user_id', $this->userId)
            ->accessible()
            ->get();

        $completed = $enrollments->filter(fn ($enrollment) => ($enrollment->progress_percent ?? 0) >= 100);
        $running = $enrollments->count() - $completed->count();

        $pendingCertificates = $completed->filter(function ($enrollment) {
            return $enrollment->course->certificates->isEmpty();
        });

        return view('livewire.student.panel-summary', [
            'user' => $user,
            'totalEnrollments' => $enrollments->count(),
            'completed' => $completed->count(),
            'running' => max($running, 0),
            'pendingCertificates' => $pendingCertificates,
        ]);
    }
}
