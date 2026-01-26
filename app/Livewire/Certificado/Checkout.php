<?php

namespace App\Livewire\Certificado;

use App\Models\Certificate;
use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Checkout extends Component
{
    public ?int $courseId = null;
    public ?string $completionDate = null;
    public ?string $cpf = null;
    public string $completionConfirmed = '';

    public ?Course $course = null;
    public ?Enrollment $enrollment = null;
    public ?CertificateBranding $branding = null;
    public ?string $statusMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $enrollment = Enrollment::with(['course.certificateBranding'])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if ($enrollment) {
            $this->setEnrollment($enrollment);
        }
    }

    public function updatedCourseId($value): void
    {
        $this->resetMessages();

        $enrollment = $this->findEnrollment((int) $value);

        if ($enrollment) {
            $this->setEnrollment($enrollment, true);
            return;
        }

        $this->course = null;
        $this->branding = null;
        $this->enrollment = null;
    }

    public function updatedCpf($value): void
    {
        $sanitized = preg_replace('/\D/', '', $value ?: '');
        $this->cpf = $sanitized !== '' ? $sanitized : null;
    }

    public function generateCertificate()
    {
        $this->resetMessages();

        $user = Auth::user();

        if (! $user) {
            $this->errorMessage = 'Sessao expirada. Faca login novamente.';
            return null;
        }

        $validated = $this->validate([
            'courseId' => ['required', 'integer'],
            'completionDate' => ['nullable', 'date'],
            'cpf' => ['nullable', 'string'],
            'completionConfirmed' => ['required', Rule::in(['yes'])],
        ]);

        $enrollment = $this->findEnrollment((int) $validated['courseId']);

        if (! $enrollment || ! $enrollment->course) {
            $this->errorMessage = 'Matricula nao encontrada para este curso.';
            return null;
        }

        $course = $enrollment->course;
        $issuedAt = $this->resolveIssuedAt($validated['completionDate'] ?? null, $enrollment);
        $branding = $this->resolveBranding($course);
        $formattedCpf = $this->formatCpf($this->cpf);

        $certificate = Certificate::firstOrNew([
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);

        if (! $certificate->number) {
            $certificate->number = 'EDUX-' . strtoupper(Str::random(8));
        }

        if (! $certificate->public_token) {
            $certificate->public_token = (string) Str::uuid();
        }

        $publicUrl = route('certificates.verify', $certificate->public_token);
        $settings = SystemSetting::current();
        $qrDataUri = $this->qrDataUri($publicUrl);

        $frontContent = view('learning.certificates.templates.front', [
            'course' => $course,
            'branding' => $branding,
            'displayName' => $user->preferredName(),
            'issuedAt' => $issuedAt,
            'publicUrl' => $publicUrl,
            'settings' => $settings,
            'cpf' => $formattedCpf,
            'qrDataUri' => $qrDataUri,
        ])->render();

        $backContent = view('learning.certificates.templates.back', [
            'course' => $course,
            'branding' => $branding,
            'settings' => $settings,
        ])->render();

        $frontPdfContent = view('learning.certificates.templates.front', [
            'course' => $course,
            'branding' => $branding,
            'displayName' => $user->preferredName(),
            'issuedAt' => $issuedAt,
            'publicUrl' => $publicUrl,
            'settings' => $settings,
            'cpf' => $formattedCpf,
            'mode' => 'pdf',
            'qrDataUri' => $qrDataUri,
        ])->render();

        $backPdfContent = view('learning.certificates.templates.back', [
            'course' => $course,
            'branding' => $branding,
            'settings' => $settings,
            'mode' => 'pdf',
        ])->render();

        $certificate->front_content = $frontContent;
        $certificate->back_content = $backContent;
        $certificate->issued_at = $issuedAt;
        $certificate->save();

        $pdf = $this->buildPdf($frontPdfContent, $backPdfContent);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'certificado-' . $course->slug . '.pdf');
    }

    public function render()
    {
        $enrollments = $this->enrollments();
        $studentName = Auth::user()?->preferredName() ?? 'Aluno';
        $courseName = $this->course?->title;
        $formattedCompletionDate = $this->formattedCompletionDate();
        $periodStart = $this->formatDate($this->course?->created_at?->format('Y-m-d'));
        $periodEnd = $formattedCompletionDate;
        $workload = $this->workloadHours($this->course);
        $formattedCpf = $this->formatCpf($this->cpf);
        $frontBackgroundUrl = $this->backgroundUrl($this->branding?->front_background_path);
        $backBackgroundUrl = $this->backgroundUrl($this->branding?->back_background_path);
        $backPreviewParagraphs = $this->backPreviewParagraphs($this->course);
        $certificates = $this->certificates();

        return view('livewire.certificado.checkout', [
            'enrollments' => $enrollments,
            'studentName' => $studentName,
            'courseName' => $courseName,
            'formattedCompletionDate' => $formattedCompletionDate,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'workload' => $workload,
            'formattedCpf' => $formattedCpf,
            'frontBackgroundUrl' => $frontBackgroundUrl,
            'backBackgroundUrl' => $backBackgroundUrl,
            'backPreviewParagraphs' => $backPreviewParagraphs,
            'certificates' => $certificates,
        ]);
    }

    private function resetMessages(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
    }

    private function enrollments()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return Enrollment::with('course')
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->get();
    }

    private function findEnrollment(int $courseId): ?Enrollment
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return Enrollment::with(['course.certificateBranding'])
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();
    }

    private function setEnrollment(Enrollment $enrollment, bool $forceDate = false): void
    {
        $this->enrollment = $enrollment;
        $this->course = $enrollment->course;
        $this->courseId = $enrollment->course_id;
        $this->branding = $this->course
            ? $this->resolveBranding($this->course)
            : null;

        if (($forceDate || ! $this->completionDate) && $enrollment->completed_at) {
            $this->completionDate = $enrollment->completed_at->format('Y-m-d');
        }
    }

    private function resolveIssuedAt(?string $inputDate, Enrollment $enrollment): Carbon
    {
        $fallback = $enrollment->completed_at?->format('Y-m-d');
        $date = $inputDate ?: $fallback ?: now()->format('Y-m-d');

        try {
            return Carbon::parse($date);
        } catch (\Throwable $exception) {
            return now();
        }
    }

    private function resolveBranding(Course $course): CertificateBranding
    {
        return CertificateBranding::resolveForCourse($course);
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

    private function formattedCompletionDate(): ?string
    {
        $value = $this->completionDate ?: $this->enrollment?->completed_at?->format('Y-m-d');

        return $this->formatDate($value);
    }

    private function formatCpf(?string $cpf): ?string
    {
        if (! $cpf || mb_strlen($cpf) !== 11) {
            return null;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($cpf, 0, 3),
            substr($cpf, 3, 3),
            substr($cpf, 6, 3),
            substr($cpf, 9, 2),
        );
    }

    private function workloadHours(?Course $course): ?int
    {
        if (! $course || ! $course->duration_minutes) {
            return null;
        }

        return (int) round($course->duration_minutes / 60);
    }

    private function backgroundUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    private function backPreviewParagraphs(?Course $course): array
    {
        if (! $course) {
            return [];
        }

        $course->loadMissing(['modules.lessons']);
        $paragraphs = [];

        foreach ($course->modules as $moduleIndex => $module) {
            $moduleNumber = $module->position ?: ($moduleIndex + 1);
            $line = "Modulo {$moduleNumber}: {$module->title}";

            if ($module->lessons->isNotEmpty()) {
                $lessonFragments = $module->lessons->values()->map(function ($lesson, $lessonIndex) {
                    $lessonNumber = $lesson->position ?: ($lessonIndex + 1);
                    return "Aula {$lessonNumber}: {$lesson->title}";
                });

                $line .= '. ' . $lessonFragments->implode('. ') . '.';
            } else {
                $line .= '.';
            }

            $paragraphs[] = $line;
        }

        return $paragraphs;
    }

    private function certificates()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return Certificate::with('course')
            ->where('user_id', $user->id)
            ->get()
            ->sortBy(fn (Certificate $certificate) => strtolower($certificate->course?->title ?? ''))
            ->values();
    }

    private function qrDataUri(?string $publicUrl): ?string
    {
        if (! $publicUrl) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()->timeout(5)->get('https://api.qrserver.com/v1/create-qr-code/', [
                'size' => '240x240',
                'data' => $publicUrl,
            ]);

            if (! $response->successful()) {
                return null;
            }

            return 'data:image/png;base64,' . base64_encode($response->body());
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function buildPdf(string $frontContent, string $backContent): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'Inter');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $html = view('learning.certificates.pdf', compact('frontContent', 'backContent'))->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return $dompdf;
    }
}
