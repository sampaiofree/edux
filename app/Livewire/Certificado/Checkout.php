<?php

namespace App\Livewire\Certificado;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class Checkout extends Component
{
    public int $step = 1;
    public bool $showPix = false;
    public bool $showSuccess = false;
    public bool $showCourseModal = false;

    public ?int $selectedCourseId = null;
    public string $courseName = '';
    public string $certificateName = '';
    public string $completionDate = '';
    public string $whatsapp = '';
    public string $email = '';
    public int $workload = 160;

    public function mount(?string $course = null): void
    {
        $this->courseName = $course ?? '';
        $this->completionDate = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.certificado.checkout', [
            'courses' => $this->courses(),
            'workloads' => $this->workloadOptions(),
        ]);
    }

    public function openCourseModal(): void
    {
        $this->showCourseModal = true;
    }

    public function closeCourseModal(): void
    {
        $this->showCourseModal = false;
    }

    public function selectCourse(int $courseId): void
    {
        $course = Course::find($courseId);

        if (! $course) {
            return;
        }

        $this->selectedCourseId = $course->id;
        $this->courseName = $course->title;
        $this->showCourseModal = false;
    }

    public function nextStep(): void
    {
        $this->validateStep();

        if ($this->step < 5) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function startPixPayment(): void
    {
        $this->showPix = true;
    }

    public function confirmPayment(): void
    {
        $this->showSuccess = true;
    }

    public function formattedCompletionDate(): string
    {
        return Carbon::parse($this->completionDate)->format('d/m/Y');
    }

    private function validateStep(): void
    {
        if ($this->step === 2) {
            $this->validate([
                'certificateName' => ['required', 'string'],
            ]);
        }

        if ($this->step === 3) {
            $this->validate([
                'completionDate' => ['required', 'date'],
                'whatsapp' => ['required', 'digits_between:10,11'],
                'email' => ['required', 'string'],
            ]);
        }
    }

    private function courses(): Collection
    {
        $publishedQuery = Course::query()->where('status', 'published');

        if ($publishedQuery->exists()) {
            return $publishedQuery->orderBy('title')->get();
        }

        return Course::query()->orderBy('title')->get();
    }

    private function workloadOptions(): array
    {
        return [
            [
                'hours' => 40,
                'price' => 'R$ 29,90',
            ],
            [
                'hours' => 80,
                'price' => 'R$ 49,90',
            ],
            [
                'hours' => 160,
                'price' => 'R$ 69,90',
                'highlight' => true,
            ],
            [
                'hours' => 240,
                'price' => 'R$ 97,00',
            ],
        ];
    }
}
