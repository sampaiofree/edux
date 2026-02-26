<?php

namespace App\Livewire\Admin;

use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\SupportWhatsappNumber;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class CourseForm extends Component
{
    use WithFileUploads;

    public ?Course $course = null;
    public string $title = '';
    public ?string $summary = null;
    public ?string $description = null;
    public ?string $atuacao = null;
    public ?string $oquefaz = null;
    public string $support_whatsapp_mode = Course::SUPPORT_WHATSAPP_MODE_ALL;
    public ?int $support_whatsapp_number_id = null;
    public string $status = 'draft';
    public ?int $duration_minutes = null;
    public ?string $published_at = null;
    public ?int $owner_id = null;
    public $certificate_front_background;
    public $certificate_back_background;
    public $cover_image;
    public ?string $promo_video_url = null;

    public array $statuses = [
        'draft' => 'Rascunho',
        'published' => 'Publicado',
        'archived' => 'Arquivado',
    ];

    public function mount(?int $courseId = null): void
    {
        if ($courseId) {
            $this->course = Course::with('certificateBranding')->findOrFail($courseId);
            $this->fill([
                'title' => $this->course->title,
                'summary' => $this->course->summary,
                'description' => $this->course->description,
                'atuacao' => $this->course->atuacao,
                'oquefaz' => $this->course->oquefaz,
                'support_whatsapp_mode' => $this->course->support_whatsapp_mode ?? Course::SUPPORT_WHATSAPP_MODE_ALL,
                'support_whatsapp_number_id' => $this->course->support_whatsapp_number_id,
                'promo_video_url' => $this->course->promo_video_url,
                'status' => $this->course->status,
                'duration_minutes' => $this->course->duration_minutes,
                'published_at' => optional($this->course->published_at)->format('Y-m-d\TH:i'),
                'owner_id' => $this->course->owner_id,
            ]);
        } else {
            $this->owner_id = Auth::id();
        }
    }

    public function save(): void
    {
        $user = Auth::user();
        $data = $this->validate($this->rules());
        unset($data['cover_image']);

        if (! $user->isAdmin()) {
            $data['owner_id'] = $user->id;
            $data['support_whatsapp_mode'] = $this->course?->support_whatsapp_mode ?? Course::SUPPORT_WHATSAPP_MODE_ALL;
            $data['support_whatsapp_number_id'] = $this->course?->support_whatsapp_number_id;
        } else {
            if (($data['support_whatsapp_mode'] ?? Course::SUPPORT_WHATSAPP_MODE_ALL) !== Course::SUPPORT_WHATSAPP_MODE_SPECIFIC) {
                $data['support_whatsapp_number_id'] = null;
            }
        }

        if ($this->course) {
            $this->course->update($data);
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['title']);
            $this->course = Course::create($data);
        }

        $this->handleBrandingUploads();
        $this->handleCoverImageUpload();

        session()->flash('status', 'Curso salvo com sucesso.');
        $this->redirectRoute('courses.edit', $this->course);
    }

    public function deleteFrontBackground(): void
    {
        if ($this->course && $this->course->certificateBranding?->front_background_path) {
            Storage::disk('public')->delete($this->course->certificateBranding->front_background_path);
            $this->course->certificateBranding->update(['front_background_path' => null]);
        }
    }

    public function deleteBackBackground(): void
    {
        if ($this->course && $this->course->certificateBranding?->back_background_path) {
            Storage::disk('public')->delete($this->course->certificateBranding->back_background_path);
            $this->course->certificateBranding->update(['back_background_path' => null]);
        }
    }

    public function render()
    {
        return view('livewire.admin.course-form', [
            'owners' => $this->owners(),
            'branding' => $this->course?->certificateBranding,
            'supportWhatsappNumbers' => $this->supportWhatsappNumbers(),
        ]);
    }

    public function getIsAdminProperty(): bool
    {
        return Auth::user()->isAdmin();
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'atuacao' => ['nullable', 'string'],
            'oquefaz' => ['nullable', 'string'],
            'support_whatsapp_mode' => ['required', Rule::in([Course::SUPPORT_WHATSAPP_MODE_ALL, Course::SUPPORT_WHATSAPP_MODE_SPECIFIC])],
            'support_whatsapp_number_id' => [
                Rule::requiredIf(fn () => $this->support_whatsapp_mode === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC),
                'nullable',
                'integer',
                'exists:support_whatsapp_numbers,id',
            ],
            'promo_video_url' => ['nullable', 'url'],
            'status' => ['required', 'in:draft,published,archived'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'published_at' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'certificate_front_background' => ['nullable', 'image', 'max:4096'],
            'certificate_back_background' => ['nullable', 'image', 'max:4096'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    private function handleBrandingUploads(): void
    {
        if (! $this->is_admin) {
            return;
        }

        if (! $this->course) {
            return;
        }

        /** @var CertificateBranding $branding */
        $branding = $this->course->certificateBranding()->firstOrNew([]);

        if ($this->certificate_front_background) {
            if ($branding->front_background_path) {
                Storage::disk('public')->delete($branding->front_background_path);
            }

            $branding->front_background_path = $this->certificate_front_background->store('certificate-backgrounds', 'public');
        }

        if ($this->certificate_back_background) {
            if ($branding->back_background_path) {
                Storage::disk('public')->delete($branding->back_background_path);
            }

            $branding->back_background_path = $this->certificate_back_background->store('certificate-backgrounds', 'public');
        }

        if ($branding->isDirty()) {
            $branding->course()->associate($this->course);
            $branding->save();
        }
    }

    private function handleCoverImageUpload(): void
    {
        if (! $this->cover_image || ! $this->course) {
            return;
        }

        if ($this->course->cover_image_path) {
            Storage::disk('public')->delete($this->course->cover_image_path);
        }

        $this->course->cover_image_path = $this->cover_image->store('course-covers', 'public');
        $this->course->save();
        $this->cover_image = null;
    }

    public function deleteCoverImage(): void
    {
        if (! $this->course || ! $this->course->cover_image_path) {
            return;
        }

        Storage::disk('public')->delete($this->course->cover_image_path);
        $this->course->update(['cover_image_path' => null]);
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (Course::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function owners()
    {
        return User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get();
    }

    private function supportWhatsappNumbers()
    {
        return SupportWhatsappNumber::query()
            ->orderByDesc('is_active')
            ->orderBy('position')
            ->orderBy('label')
            ->get();
    }
}
