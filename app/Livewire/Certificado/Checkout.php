<?php

namespace App\Livewire\Certificado;

use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
    public ?CertificateBranding $branding = null;
    public ?string $cpf = null;

    private const FRONT_FALLBACK = 'system-assets/certificate-front-default.png';
    private const BACK_FALLBACK = 'system-assets/certificate-back-default.png';

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
        $this->branding = $this->resolveBranding($this->course);
    }

    public function render()
    {
        return view('livewire.certificado.checkout', [
            'courses' => $this->courses(),
            'workloads' => $this->workloadOptions(),
            'frontBackgroundUrl' => $this->frontBackgroundUrl,
            'backBackgroundUrl' => $this->backBackgroundUrl,
            'formattedCompletionDate' => $this->formattedCompletionDate,
            'completionPeriodStart' => $this->completionPeriodStart,
            'completionPeriodEnd' => $this->completionPeriodEnd,
            'formattedCpf' => $this->formattedCpf,
            'backPreviewParagraphs' => $this->backPreviewParagraphs,
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
        $this->branding = $this->resolveBranding($course);
        $this->courseName = $course->title;
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

    public function updatedCpf(?string $value): void
    {
        $sanitized = preg_replace('/\D/', '', $value ?: '');
        $this->cpf = $sanitized !== '' ? $sanitized : null;
    }

    public function canAdvanceFromStepOne(): bool
    {
        return $this->course !== null;
    }

    public function getFrontBackgroundUrlProperty(): ?string
    {
        $settings = SystemSetting::current();

        return $this->resolveBackgroundUrl(
            $this->branding?->front_background_path,
            $settings->default_certificate_front_path,
            self::FRONT_FALLBACK
        );
    }

    public function getBackBackgroundUrlProperty(): ?string
    {
        $settings = SystemSetting::current();

        return $this->resolveBackgroundUrl(
            $this->branding?->back_background_path,
            $settings->default_certificate_back_path,
            self::BACK_FALLBACK
        );
    }

    public function getFormattedCompletionDateProperty(): ?string
    {
        return $this->formatDate($this->completionDate);
    }

    public function getCompletionPeriodStartProperty(): ?string
    {
        return $this->formatDate($this->completionDate);
    }

    public function getCompletionPeriodEndProperty(): ?string
    {
        return $this->formatDate($this->completionDate);
    }

    public function getBackPreviewParagraphsProperty(): array
    {
        if (! $this->course) {
            return [];
        }

        $this->course->loadMissing(['modules.lessons']);

        return $this->buildBackPreviewParagraphs($this->course);
    }

    private function resolveBackgroundUrl(?string ...$paths): ?string
    {
        $disk = Storage::disk('public');

        foreach ($paths as $path) {
            if (! $path) {
                continue;
            }

            if (! $disk->exists($path)) {
                continue;
            }

            return $disk->url($path);
        }

        return null;
    }

    private function formatDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)
                ->locale('pt_BR')
                ->isoFormat('D [de] MMMM [de] YYYY');
        } catch (\Throwable $exception) {
            return $value;
        }
    }

    private function buildBackPreviewParagraphs(Course $course): array
    {
        $paragraphs = [];

        foreach ($course->modules as $moduleIndex => $module) {
            $moduleNumber = $module->position ?: ($moduleIndex + 1);
            $line = "Módulo {$moduleNumber}: {$module->title}";

            if ($module->lessons->isNotEmpty()) {
                $lessonFragments = $module->lessons->values()->map(function ($lesson, $lessonIndex) {
                    $lessonNumber = $lesson->position ?: ($lessonIndex + 1);
                    return "Aula {$lessonNumber}: {$lesson->title}";
                });

                $line .= '. '.$lessonFragments->implode('. ').'.';
            } else {
                $line .= '.';
            }

            $paragraphs[] = $line;
        }

        return $paragraphs;
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

    private function resolveBranding(Course $course): CertificateBranding
    {
        return $course->certificateBranding
            ?? CertificateBranding::firstOrCreate(['course_id' => null]);
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
