<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\SupportWhatsappNumber;
use App\Models\User;
use App\Support\HandlesCourseAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    use HandlesCourseAuthorization;
    public function create(Request $request): View
    {
        $owners = $this->owners();
        $user = $request->user();
        $course = new Course([
            'status' => 'draft',
            'owner_id' => $user->id,
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
        ]);
        $supportWhatsappNumbers = $this->supportWhatsappNumbers();

        return view('courses.create', compact('owners', 'user', 'course', 'supportWhatsappNumbers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'atuacao' => ['nullable', 'string'],
            'oquefaz' => ['nullable', 'string'],
            'promo_video_url' => ['nullable', 'url'],
            'status' => ['required', 'in:draft,published,archived'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'published_at' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'kavoo_id' => ['nullable', 'integer', 'min:0'],
            'support_whatsapp_mode' => [
                Rule::requiredIf(fn () => $user->isAdmin()),
                'nullable',
                Rule::in([Course::SUPPORT_WHATSAPP_MODE_ALL, Course::SUPPORT_WHATSAPP_MODE_SPECIFIC]),
            ],
            'support_whatsapp_number_id' => [
                Rule::requiredIf(fn () => $user->isAdmin() && $request->input('support_whatsapp_mode') === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC),
                'nullable',
                'integer',
                'exists:support_whatsapp_numbers,id',
            ],
            'certificate_front_background' => ['nullable', 'image', 'max:4096'],
            'certificate_back_background' => ['nullable', 'image', 'max:4096'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'remove_cover_image' => ['nullable', 'boolean'],
            'remove_certificate_front_background' => ['nullable', 'boolean'],
            'remove_certificate_back_background' => ['nullable', 'boolean'],
        ]);

        $ownerId = $user->isAdmin()
            ? ($validated['owner_id'] ?? $user->id)
            : $user->id;

        $course = Course::create([
            'owner_id' => $ownerId,
            'title' => $validated['title'],
            'slug' => $this->generateUniqueSlug($validated['title']),
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'atuacao' => $validated['atuacao'] ?? null,
            'oquefaz' => $validated['oquefaz'] ?? null,
            'promo_video_url' => $validated['promo_video_url'] ?? null,
            'status' => $validated['status'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
            'kavoo_id' => $user->isAdmin() ? ($validated['kavoo_id'] ?? null) : null,
            'support_whatsapp_mode' => $user->isAdmin()
                ? ($validated['support_whatsapp_mode'] ?? Course::SUPPORT_WHATSAPP_MODE_ALL)
                : Course::SUPPORT_WHATSAPP_MODE_ALL,
            'support_whatsapp_number_id' => $user->isAdmin() && (($validated['support_whatsapp_mode'] ?? Course::SUPPORT_WHATSAPP_MODE_ALL) === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC)
                ? ($validated['support_whatsapp_number_id'] ?? null)
                : null,
        ]);

        if ($request->hasFile('cover_image')) {
            $course->cover_image_path = $request->file('cover_image')->store('course-covers', 'public');
            $course->save();
        }

        $this->syncBrandingUploads($request, $course);

        return redirect()->route('courses.edit', $course)->with('status', 'Curso criado com sucesso.');
    }

    public function edit(Request $request, Course $course): View
    {
        $user = $request->user();
        $this->ensureCanManageCourse($user, $course);

        $course->load([
            'modules.lessons' => fn ($query) => $query->orderBy('position'),
            'finalTest',
            'certificateBranding',
        ]);

        $owners = $this->owners();
        $supportWhatsappNumbers = $this->supportWhatsappNumbers();

        return view('courses.edit', compact('course', 'owners', 'user', 'supportWhatsappNumbers'));
    }

    public function editModules(Request $request, Course $course): View
    {
        $user = $request->user();
        $this->ensureCanManageCourse($user, $course);

        $course->load([
            'modules' => fn ($query) => $query
                ->with(['lessons' => fn ($lessonQuery) => $lessonQuery->orderBy('position')])
                ->orderBy('position'),
        ]);

        return view('courses.modules', compact('course', 'user'));
    }

    public function editFinalTest(Request $request, Course $course): View
    {
        $user = $request->user();
        $this->ensureCanManageCourse($user, $course);

        $course->load('finalTest.questions.options');

        return view('courses.final-test', compact('course', 'user'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageCourse($user, $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'atuacao' => ['nullable', 'string'],
            'oquefaz' => ['nullable', 'string'],
            'promo_video_url' => ['nullable', 'url'],
            'status' => ['required', 'in:draft,published,archived'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'published_at' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'kavoo_id' => ['nullable', 'integer', 'min:0'],
            'support_whatsapp_mode' => [
                Rule::requiredIf(fn () => $user->isAdmin()),
                'nullable',
                Rule::in([Course::SUPPORT_WHATSAPP_MODE_ALL, Course::SUPPORT_WHATSAPP_MODE_SPECIFIC]),
            ],
            'support_whatsapp_number_id' => [
                Rule::requiredIf(fn () => $user->isAdmin() && $request->input('support_whatsapp_mode') === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC),
                'nullable',
                'integer',
                'exists:support_whatsapp_numbers,id',
            ],
            'certificate_front_background' => ['nullable', 'image', 'max:4096'],
            'certificate_back_background' => ['nullable', 'image', 'max:4096'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $course->fill([
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'atuacao' => $validated['atuacao'] ?? null,
            'oquefaz' => $validated['oquefaz'] ?? null,
            'promo_video_url' => $validated['promo_video_url'] ?? null,
            'status' => $validated['status'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
            'kavoo_id' => $user->isAdmin() ? ($validated['kavoo_id'] ?? null) : $course->kavoo_id,
            'support_whatsapp_mode' => $user->isAdmin()
                ? ($validated['support_whatsapp_mode'] ?? $course->support_whatsapp_mode ?? Course::SUPPORT_WHATSAPP_MODE_ALL)
                : ($course->support_whatsapp_mode ?? Course::SUPPORT_WHATSAPP_MODE_ALL),
            'support_whatsapp_number_id' => $user->isAdmin() && (($validated['support_whatsapp_mode'] ?? $course->support_whatsapp_mode ?? Course::SUPPORT_WHATSAPP_MODE_ALL) === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC)
                ? ($validated['support_whatsapp_number_id'] ?? null)
                : null,
        ]);

        if ($user->isAdmin() && isset($validated['owner_id'])) {
            $course->owner_id = $validated['owner_id'];
        }

        if ($course->isDirty('title')) {
            $course->slug = $this->generateUniqueSlug($course->title, $course->id);
        }

        $course->save();

        if ($request->boolean('remove_cover_image')) {
            if ($course->cover_image_path) {
                Storage::disk('public')->delete($course->cover_image_path);
            }

            $course->cover_image_path = null;
            $course->save();
        }

        if ($request->hasFile('cover_image')) {
            if ($course->cover_image_path) {
                Storage::disk('public')->delete($course->cover_image_path);
            }

            $course->cover_image_path = $request->file('cover_image')->store('course-covers', 'public');
            $course->save();
        }

        $this->syncBrandingUploads($request, $course);

        return redirect()
            ->route('courses.edit', $course)
            ->with('status', 'Curso atualizado.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageCourse($user, $course);

        $course->delete();

        return redirect()->route('admin.dashboard')->with('status', 'Curso removido.');
    }

    private function owners()
    {
        return User::query()
            ->where('role', UserRole::ADMIN->value)
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

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Course::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function syncBrandingUploads(Request $request, Course $course): void
    {
        $hasUploads = $request->hasFile('certificate_front_background') || $request->hasFile('certificate_back_background');
        $removeFront = $request->boolean('remove_certificate_front_background');
        $removeBack = $request->boolean('remove_certificate_back_background');

        if (! $hasUploads && ! $removeFront && ! $removeBack) {
            return;
        }

        /** @var CertificateBranding $branding */
        $branding = $course->certificateBranding()->firstOrNew([]);

        if ($removeFront && $branding->front_background_path) {
            Storage::disk('public')->delete($branding->front_background_path);
            $branding->front_background_path = null;
        }

        if ($removeBack && $branding->back_background_path) {
            Storage::disk('public')->delete($branding->back_background_path);
            $branding->back_background_path = null;
        }

        if ($request->hasFile('certificate_front_background')) {
            if ($branding->front_background_path) {
                Storage::disk('public')->delete($branding->front_background_path);
            }

            $branding->front_background_path = $request->file('certificate_front_background')
                ->store('certificate-backgrounds', 'public');
        }

        if ($request->hasFile('certificate_back_background')) {
            if ($branding->back_background_path) {
                Storage::disk('public')->delete($branding->back_background_path);
            }

            $branding->back_background_path = $request->file('certificate_back_background')
                ->store('certificate-backgrounds', 'public');
        }

        $branding->course()->associate($course);
        $branding->save();
    }
}
