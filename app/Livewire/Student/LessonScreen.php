<?php

namespace App\Livewire\Student;

use App\Models\Certificate;
use App\Models\CertificateBranding;
use App\Models\CertificatePayment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\SystemSetting;
use App\Support\EnsuresStudentEnrollment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;

class LessonScreen extends Component
{
    use EnsuresStudentEnrollment;

    public Course $course;
    public Lesson $lesson;
    public ?Lesson $previousLesson = null;
    public ?Lesson $nextLesson = null;
    public array $completedLessonIds = [];
    public bool $isCompleted = false;
    public int $progressPercent = 0;
    public ?Certificate $certificate = null;
    public ?string $statusMessage = null;
    public ?string $errorMessage = null;
    public bool $canRename;
    public bool $hasPaidCertificate = false;
    public bool $showPaymentModal = false;

    private $user;
    private $enrollment;

    public function mount(int $courseId, int $lessonId): void
    {
        $this->user = Auth::user();
        $this->course = Course::with([
            'modules.lessons' => fn ($query) => $query->orderBy('position'),
            'finalTest',
            'certificateBranding',
            'checkouts' => fn ($query) => $query->where('is_active', true)->orderBy('hours'),
        ])->findOrFail($courseId);

        $this->lesson = Lesson::with('module')->findOrFail($lessonId);

        abort_if(! $this->lesson->module || $this->lesson->module->course_id !== $this->course->id, 404);

        $this->enrollment = $this->ensureEnrollment($this->user, $this->course);
        $this->canRename = $this->user->name_change_available ?? false;

        $this->refreshState();
    }

    public function hydrate(): void
    {
        $this->user = Auth::user();

        if ($this->user && $this->course) {
            $this->enrollment = $this->ensureEnrollment($this->user, $this->course);
        }
    }

    public function completeLesson(): void
    {
        if (! $this->user) {
            $this->errorMessage = "Sessao expirada. Entre novamente.";
            return;
        }

        if (! $this->lesson) {
            $this->errorMessage = "Aula nao encontrada.";
            return;
        }

        if ($this->isCompleted) {
            $this->statusMessage = "Esta aula ja esta marcada como concluida.";
            return;
        }

        LessonCompletion::updateOrCreate(
            [
                "lesson_id" => $this->lesson->id,
                "user_id" => $this->user->id,
            ],
            [
                "completed_at" => now(),
            ]
        );

        $this->enrollment->recalculateProgress();

        $nextLesson = $this->course->nextLessonFor($this->user);
        $this->refreshState();

        if ($nextLesson && $nextLesson->id !== $this->lesson->id) {
            $this->redirectRoute("learning.courses.lessons.show", [$this->course, $nextLesson], navigate: true);
            return;
        }

        $this->statusMessage = "Parabens! Todas as aulas foram concluidas. Voce ja pode solicitar o certificado.";
    }

    public function requestCertificate(): void
    {
        if (! $this->certificate && ! $this->hasPaidCertificate) {
            $this->errorMessage = 'Antes de emitir o certificado finalize o pagamento. Use a aba "Suporte" para receber instruÃ§Ãµes.';
            return;
        }

        [$eligible, $message] = $this->checkEligibility($this->user->id, $this->course->id);

        if (! $eligible) {
            $this->errorMessage = $message;
            return;
        }

        $certificate = Certificate::firstOrNew([
            'course_id' => $this->course->id,
            'user_id' => $this->user->id,
        ]);

        $branding = $this->resolveBranding($this->course);
        $issuedAt = now();
        $displayName = $this->user->preferredName();
        $settings = SystemSetting::current();

        if (! $certificate->exists) {
            $certificate->number = 'EDUX-' . strtoupper(Str::random(8));
            $certificate->public_token = (string) Str::uuid();
            $publicUrl = route('certificates.verify', $certificate->public_token);
            $qrDataUri = $this->qrDataUri($publicUrl);

            $certificate->front_content = view('learning.certificates.templates.front', [
                'course' => $this->course,
                'branding' => $branding,
                'displayName' => $displayName,
                'issuedAt' => $issuedAt,
                'publicUrl' => $publicUrl,
                'settings' => $settings,
                'qrDataUri' => $qrDataUri,
            ])->render();

            $certificate->back_content = view('learning.certificates.templates.back', [
                'course' => $this->course,
                'branding' => $branding,
                'settings' => $settings,
            ])->render();

            $certificate->issued_at = $issuedAt;
            $certificate->save();
        } elseif (! $certificate->public_token) {
            $certificate->public_token = (string) Str::uuid();
            $certificate->save();
        }

        $this->enrollment->forceFill(['completed_at' => now(), 'progress_percent' => 100])->save();
        $this->certificate = $certificate;
        $this->statusMessage = 'Certificado emitido com sucesso!';

        $this->redirectRoute('learning.courses.certificate.show', [$this->course, $certificate], navigate: true);
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function getYoutubeIdProperty(): ?string
    {
        if (! $this->lesson->video_url) {
            return null;
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtube\.com\/embed\/|youtu\.be\/)([A-Za-z0-9_\-]+)/',
            '/youtube\.com\/shorts\/([A-Za-z0-9_\-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->lesson->video_url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.student.lesson-screen');
    }

    private function refreshState(): void
    {
        $orderedLessons = $this->course->lessons()
            ->with('module')
            ->orderBy('modules.position')
            ->orderBy('lessons.position')
            ->get();

        $currentIndex = $orderedLessons->search(fn (Lesson $item) => $item->id === $this->lesson->id);

        abort_if($currentIndex === false, 404);

        $this->previousLesson = $currentIndex > 0 ? $orderedLessons[$currentIndex - 1] : null;
        $this->nextLesson = $orderedLessons->get($currentIndex + 1);

        $this->completedLessonIds = $this->user->lessonCompletions()
            ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $this->course->id))
            ->pluck('lesson_id')
            ->toArray();

        $this->isCompleted = in_array($this->lesson->id, $this->completedLessonIds, true);
        $this->progressPercent = $this->course->completionPercentageFor($this->user);
        $this->certificate = $this->course->certificates()
            ->where('user_id', $this->user->id)
            ->latest('issued_at')
            ->first();

        $this->hasPaidCertificate = $this->hasPayment();
    }

    /**
     * @return array{0: bool, 1: string|null}
     */
    private function checkEligibility(int $userId, int $courseId): array
    {
        $course = Course::with(['lessons', 'finalTest'])->findOrFail($courseId);

        $lessonIds = $course->lessons->pluck('id');
        $totalLessons = $lessonIds->count();
        $completedLessons = LessonCompletion::query()
            ->whereIn('lesson_id', $lessonIds)
            ->where('user_id', $userId)
            ->count();

        if ($totalLessons === 0) {
            return [false, 'Este curso ainda nÃ£o possui aulas cadastradas.'];
        }

        if ($completedLessons < $totalLessons) {
            return [false, 'Conclua todas as aulas antes de solicitar o certificado.'];
        }

        if ($course->finalTest) {
            $passed = $course->finalTest->attempts()
                ->where('user_id', $userId)
                ->where('passed', true)
                ->where('score', '>=', $course->finalTest->passing_score)
                ->exists();

            if (! $passed) {
                return [false, 'VocÃª precisa atingir a nota mÃ­nima no teste final para liberar o certificado.'];
            }
        }

        return [true, null];
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

    private function resolveBranding(Course $course): CertificateBranding
    {
        return CertificateBranding::resolveForCourse($course);
    }

    private function hasPayment(): bool
    {
        return CertificatePayment::where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->where('status', 'paid')
            ->exists();
    }
}
