<?php

namespace App\Livewire\Certificado;

use App\Models\Course;
use App\Services\CertificadoService;
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
    public ?Course $course = null;
    public array $preview = [
        'front' => null,
        'back'  => null,
    ];
    public ?string $cpf = null;
    public ?string $previewError = null;

    public function mount(?string $course = null): void
    {
        if ($course) {
            $this->course = Course::where('slug', $course)->firstOrFail();
        } else {
            $this->course = Course::query()->orderBy('title')->firstOrFail();
        }

        $this->courseName = $this->course->title;
        $this->selectedCourseId = $this->course->id;
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
        $this->course = $course;
        $this->courseName = $course->title;
        $this->preview = ['front' => null, 'back' => null];
        $this->previewError = null;
        $this->showCourseModal = false;
    }

    public function nextStep(): void
    {
        if ($this->step === 1 && ! $this->canAdvanceFromStepOne()) {
            return;
        }

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

    public function generatePreview(CertificadoService $service): void
    {
        $this->validate([
            'certificateName' => ['required', 'string'],
            'completionDate' => ['required', 'date'],
        ]);

        if (! $this->course) {
            return;
        }

        $this->previewError = null;
        $data = [
            'student_name' => $this->certificateName,
            'course_name' => $this->course->title,
            'completed_at' => $this->completionDate,
        ];

        $frontPreview = $service->previewFrente($data, true);
        $backPreview = $service->previewVerso($this->course, true);

        $this->preview['front'] = $frontPreview;
        $this->preview['back'] = $backPreview;

        if (! $frontPreview || ! $backPreview) {
            $this->previewError = 'Não foi possível gerar o preview agora. Tente novamente.';
        }
    }

    public function updatedCertificateName(): void
    {
        $this->preview = ['front' => null, 'back' => null];
        $this->previewError = null;
    }

    public function updatedCompletionDate(): void
    {
        $this->preview = ['front' => null, 'back' => null];
        $this->previewError = null;
    }

    public function updatedCpf(?string $value): void
    {
        $sanitized = preg_replace('/\D/', '', $value ?: '');
        $this->cpf = $sanitized !== '' ? $sanitized : null;
    }

    public function canGeneratePreview(): bool
    {
        return $this->certificateName !== '' && $this->completionDate !== '';
    }

    public function canAdvanceFromStepOne(): bool
    {
        return $this->course !== null;
    }

    public function getFormattedCpfProperty(): ?string
    {
        if (! $this->cpf || mb_strlen($this->cpf) !== 11) {
            return null;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($this->cpf, 0, 3),
            substr($this->cpf, 3, 3),
            substr($this->cpf, 6, 3),
            substr($this->cpf, 9, 2),
        );
    }

    private function validateStep(): void
    {
        if ($this->step === 2) {
            $this->validate([
                'certificateName' => ['required', 'string'],
                'completionDate' => ['required', 'date'],
            ]);
        }

        if ($this->step === 3) {
            $this->validate([
                'whatsapp' => ['required', 'regex:/^\+\d{10,15}$/'],
                'email' => ['required', 'string'],
            ]);
        }

        if ($this->step === 4 && empty($this->cpf)) {
            $this->validate([
                'cpf' => ['required', 'digits:11'],
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
