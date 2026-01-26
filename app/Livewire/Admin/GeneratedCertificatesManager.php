<?php

namespace App\Livewire\Admin;

use App\Models\Certificate;
use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class GeneratedCertificatesManager extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $courseId = null;
    public ?int $userId = null;
    public ?string $completionDate = null;
    public ?string $cpf = null;
    public ?string $statusMessage = null;
    public ?string $errorMessage = null;

    protected $rules = [
        'courseId' => ['required', 'integer', 'exists:courses,id'],
        'userId' => ['required', 'integer', 'exists:users,id'],
        'completionDate' => ['nullable', 'date'],
        'cpf' => ['nullable', 'string'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCourseId(): void
    {
        $this->reset(['userId', 'completionDate', 'cpf']);
        $this->resetErrorBag();
    }

    public function updatedUserId(): void
    {
        $this->resetErrorBag();
        $this->syncCompletionDate();
    }

    public function generateCertificate(): void
    {
        $this->resetMessages();

        $data = $this->validate();

        $course = Course::find($data['courseId']);
        $user = User::find($data['userId']);

        if (! $course || ! $user) {
            $this->errorMessage = 'Aluno ou curso n\u00e3o encontrado.';
            return;
        }

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $enrollment) {
            $this->errorMessage = 'Este aluno n\u00e3o est\u00e1 matriculado no curso selecionado.';
            return;
        }

        $issuedAt = $this->resolveIssuedAt($data['completionDate'] ?? null, $enrollment);
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

        $publicUrl = $certificate->public_token
            ? route('certificates.verify', $certificate->public_token)
            : null;
        $qrDataUri = $this->qrDataUri($publicUrl);

        $frontContent = view('learning.certificates.templates.front', [
            'course' => $course,
            'branding' => $branding,
            'displayName' => $user->preferredName(),
            'issuedAt' => $issuedAt,
            'publicUrl' => $publicUrl,
            'cpf' => $formattedCpf,
            'qrDataUri' => $qrDataUri,
        ])->render();

        $backContent = view('learning.certificates.templates.back', [
            'course' => $course,
            'branding' => $branding,
        ])->render();

        $certificate->front_content = $frontContent;
        $certificate->back_content = $backContent;
        $certificate->issued_at = $issuedAt;
        $certificate->save();

        $this->statusMessage = 'Certificado gerado e atualizado.';
        $this->reset(['userId', 'completionDate', 'cpf']);
        $this->dispatch('admin-certificate-saved');
    }

    public function render()
    {
        $search = $this->search;
        $isNumericSearch = $search !== '' && is_numeric($search);

        $certificates = Certificate::query()
            ->with(['course', 'user'])
            ->when($search !== '', function ($query) use ($search, $isNumericSearch) {
                $query->where(function ($sub) use ($search, $isNumericSearch) {
                    $sub->where('number', 'like', "%{$search}%")
                        ->orWhere('public_token', 'like', "%{$search}%")
                        ->orWhereHas('course', function ($course) use ($search, $isNumericSearch) {
                            $course->where('title', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");

                            if ($isNumericSearch) {
                                $course->orWhere('id', (int) $search);
                            }
                        })
                        ->orWhereHas('user', function ($user) use ($search, $isNumericSearch) {
                            $user->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('whatsapp', 'like', "%{$search}%");

                            if ($isNumericSearch) {
                                $user->orWhere('id', (int) $search);
                            }
                        });

                    if ($isNumericSearch) {
                        $sub->orWhere('id', (int) $search)
                            ->orWhere('course_id', (int) $search)
                            ->orWhere('user_id', (int) $search);
                    }
                });
            })
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(20);

        $courses = Course::orderBy('title')->get();
        $students = $this->courseId
            ? User::query()
                ->whereHas('enrollments', fn ($query) => $query->where('course_id', $this->courseId))
                ->orderBy('name')
                ->get()
            : collect();

        $selectedCourse = $this->courseId ? $courses->firstWhere('id', $this->courseId) : null;
        $selectedUser = $this->userId ? $students->firstWhere('id', $this->userId) : null;

        return view('livewire.admin.generated-certificates-manager', [
            'certificates' => $certificates,
            'courses' => $courses,
            'students' => $students,
            'selectedCourse' => $selectedCourse,
            'selectedUser' => $selectedUser,
            'formattedCompletionDate' => $this->formatDate($this->completionDate),
            'formattedCpf' => $this->formatCpf($this->cpf),
        ]);
    }

    private function syncCompletionDate(): void
    {
        if (! $this->courseId || ! $this->userId) {
            return;
        }

        $enrollment = Enrollment::query()
            ->where('course_id', $this->courseId)
            ->where('user_id', $this->userId)
            ->first();

        if ($enrollment?->completed_at) {
            $this->completionDate = $enrollment->completed_at->format('Y-m-d');
        }
    }

    private function resetMessages(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
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
        return $course->certificateBranding
            ?? CertificateBranding::firstOrCreate(['course_id' => null]);
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

    private function formatCpf(?string $cpf): ?string
    {
        if (! $cpf) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $cpf ?? '');

        if (mb_strlen($digits) !== 11) {
            return null;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3),
            substr($digits, 9, 2),
        );
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
}
