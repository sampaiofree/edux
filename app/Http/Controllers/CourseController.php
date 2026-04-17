<?php

namespace App\Http\Controllers;

use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\CourseWebhookId;
use App\Models\SupportWhatsappNumber;
use App\Models\User;
use App\Support\HandlesCourseAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'owner_id' => $this->defaultOwnerId($user),
            'support_whatsapp_mode' => Course::SUPPORT_WHATSAPP_MODE_ALL,
            'access_mode' => Course::ACCESS_MODE_PAID,
        ]);
        $supportWhatsappNumbers = $this->supportWhatsappNumbers();

        return view('courses.create', compact('owners', 'user', 'course', 'supportWhatsappNumbers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $systemSettingId = (int) ($user->adminContextSystemSettingId() ?? 0);
        $hasAdminPrivileges = $user->hasAdminPrivileges();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'atuacao' => ['nullable', 'string'],
            'oquefaz' => ['nullable', 'string'],
            'promo_video_url' => ['nullable', 'url'],
            'status' => ['required', 'in:draft,published,archived'],
            'access_mode' => ['nullable', Rule::in(Course::accessModeValues())],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'published_at' => ['nullable', 'date'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('system_setting_id', $systemSettingId)],
            'support_whatsapp_mode' => [
                Rule::requiredIf(fn () => $hasAdminPrivileges),
                'nullable',
                Rule::in([Course::SUPPORT_WHATSAPP_MODE_ALL, Course::SUPPORT_WHATSAPP_MODE_SPECIFIC]),
            ],
            'support_whatsapp_number_id' => [
                Rule::requiredIf(fn () => $hasAdminPrivileges && $request->input('support_whatsapp_mode') === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC),
                'nullable',
                'integer',
                Rule::exists('support_whatsapp_numbers', 'id')->where('system_setting_id', $systemSettingId),
            ],
            'certificate_front_background' => ['nullable', 'image', 'max:4096'],
            'certificate_back_background' => ['nullable', 'image', 'max:4096'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'remove_cover_image' => ['nullable', 'boolean'],
            'remove_certificate_front_background' => ['nullable', 'boolean'],
            'remove_certificate_back_background' => ['nullable', 'boolean'],
            'curso_webhook_ids' => ['nullable', 'array'],
            'curso_webhook_ids.*.webhook_id' => ['nullable', 'string', 'max:191'],
            'curso_webhook_ids.*.platform' => ['nullable', 'required_with:curso_webhook_ids.*.webhook_id', 'string', 'max:191'],
        ], $this->courseValidationMessages());

        $courseWebhookIds = $hasAdminPrivileges
            ? $this->normalizeCourseWebhookIds($validated['curso_webhook_ids'] ?? [])
            : [];
        $this->ensureUniqueCourseWebhookIds($courseWebhookIds);
        $this->ensureCourseWebhookIdsAvailableForTenant($systemSettingId, $courseWebhookIds);

        $ownerId = $hasAdminPrivileges
            ? ($validated['owner_id'] ?? $this->defaultOwnerId($user))
            : $user->id;

        $course = DB::transaction(function () use ($courseWebhookIds, $hasAdminPrivileges, $ownerId, $systemSettingId, $validated): Course {
            $course = Course::create([
                'system_setting_id' => $systemSettingId,
                'owner_id' => $ownerId,
                'title' => $validated['title'],
                'slug' => $this->generateUniqueSlug($validated['title']),
                'summary' => $validated['summary'] ?? null,
                'description' => $validated['description'] ?? null,
                'atuacao' => $validated['atuacao'] ?? null,
                'oquefaz' => $validated['oquefaz'] ?? null,
                'promo_video_url' => $validated['promo_video_url'] ?? null,
                'status' => $validated['status'],
                'access_mode' => $validated['access_mode'] ?? Course::ACCESS_MODE_PAID,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'published_at' => $validated['published_at'] ?? null,
                'support_whatsapp_mode' => $hasAdminPrivileges
                    ? ($validated['support_whatsapp_mode'] ?? Course::SUPPORT_WHATSAPP_MODE_ALL)
                    : Course::SUPPORT_WHATSAPP_MODE_ALL,
                'support_whatsapp_number_id' => $hasAdminPrivileges && (($validated['support_whatsapp_mode'] ?? Course::SUPPORT_WHATSAPP_MODE_ALL) === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC)
                    ? ($validated['support_whatsapp_number_id'] ?? null)
                    : null,
            ]);

            if ($hasAdminPrivileges) {
                $this->syncCourseWebhookIds($course, $courseWebhookIds);
            }

            return $course;
        });

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
            'courseWebhookIds',
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
        abort_unless($user->hasAdminPrivileges(), 403);

        $course->load('finalTest.questions.options');

        return view('courses.final-test', compact('course', 'user'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageCourse($user, $course);
        $systemSettingId = (int) ($user->adminContextSystemSettingId() ?? 0);
        $hasAdminPrivileges = $user->hasAdminPrivileges();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'atuacao' => ['nullable', 'string'],
            'oquefaz' => ['nullable', 'string'],
            'promo_video_url' => ['nullable', 'url'],
            'status' => ['required', 'in:draft,published,archived'],
            'access_mode' => ['nullable', Rule::in(Course::accessModeValues())],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'published_at' => ['nullable', 'date'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('system_setting_id', $systemSettingId)],
            'support_whatsapp_mode' => [
                Rule::requiredIf(fn () => $hasAdminPrivileges),
                'nullable',
                Rule::in([Course::SUPPORT_WHATSAPP_MODE_ALL, Course::SUPPORT_WHATSAPP_MODE_SPECIFIC]),
            ],
            'support_whatsapp_number_id' => [
                Rule::requiredIf(fn () => $hasAdminPrivileges && $request->input('support_whatsapp_mode') === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC),
                'nullable',
                'integer',
                Rule::exists('support_whatsapp_numbers', 'id')->where('system_setting_id', $systemSettingId),
            ],
            'certificate_front_background' => ['nullable', 'image', 'max:4096'],
            'certificate_back_background' => ['nullable', 'image', 'max:4096'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'curso_webhook_ids' => ['nullable', 'array'],
            'curso_webhook_ids.*.webhook_id' => ['nullable', 'string', 'max:191'],
            'curso_webhook_ids.*.platform' => ['nullable', 'required_with:curso_webhook_ids.*.webhook_id', 'string', 'max:191'],
        ], $this->courseValidationMessages());

        $courseWebhookIds = $hasAdminPrivileges
            ? $this->normalizeCourseWebhookIds($validated['curso_webhook_ids'] ?? [])
            : [];
        $this->ensureUniqueCourseWebhookIds($courseWebhookIds);
        $this->ensureCourseWebhookIdsAvailableForTenant((int) $course->system_setting_id, $courseWebhookIds, $course->id);

        DB::transaction(function () use ($course, $courseWebhookIds, $hasAdminPrivileges, $validated): void {
            $course->fill([
                'title' => $validated['title'],
                'summary' => $validated['summary'] ?? null,
                'description' => $validated['description'] ?? null,
                'atuacao' => $validated['atuacao'] ?? null,
                'oquefaz' => $validated['oquefaz'] ?? null,
                'promo_video_url' => $validated['promo_video_url'] ?? null,
                'status' => $validated['status'],
                'access_mode' => $validated['access_mode'] ?? $course->access_mode ?? Course::ACCESS_MODE_PAID,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'published_at' => $validated['published_at'] ?? null,
                'support_whatsapp_mode' => $hasAdminPrivileges
                    ? ($validated['support_whatsapp_mode'] ?? $course->support_whatsapp_mode ?? Course::SUPPORT_WHATSAPP_MODE_ALL)
                    : ($course->support_whatsapp_mode ?? Course::SUPPORT_WHATSAPP_MODE_ALL),
                'support_whatsapp_number_id' => $hasAdminPrivileges
                    ? ((($validated['support_whatsapp_mode'] ?? $course->support_whatsapp_mode ?? Course::SUPPORT_WHATSAPP_MODE_ALL) === Course::SUPPORT_WHATSAPP_MODE_SPECIFIC)
                        ? ($validated['support_whatsapp_number_id'] ?? null)
                        : null)
                    : $course->support_whatsapp_number_id,
            ]);

            if ($hasAdminPrivileges && isset($validated['owner_id'])) {
                $course->owner_id = $validated['owner_id'];
            }

            if ($course->isDirty('title')) {
                $course->slug = $this->generateUniqueSlug($course->title, $course->id);
            }

            $course->save();

            if ($hasAdminPrivileges) {
                $this->syncCourseWebhookIds($course, $courseWebhookIds);
            }
        });

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
            ->whereIn('role', User::courseOwnerRoleValues())
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

    private function defaultOwnerId(User $user): ?int
    {
        if (! $user->hasAdminPrivileges()) {
            return $user->id;
        }

        $currentSystemSettingId = $user->adminContextSystemSettingId();

        if ($currentSystemSettingId !== null && (int) ($user->system_setting_id ?? 0) === (int) $currentSystemSettingId) {
            return $user->id;
        }

        return $this->owners()->first()?->id;
    }

    /**
     * @return array<int, array{webhook_id:string, platform:string}>
     */
    private function normalizeCourseWebhookIds(mixed $rawCourseWebhookIds): array
    {
        if (! is_array($rawCourseWebhookIds)) {
            return [];
        }

        $normalized = [];

        foreach ($rawCourseWebhookIds as $courseWebhookId) {
            if (! is_array($courseWebhookId)) {
                continue;
            }

            $webhookId = trim((string) ($courseWebhookId['webhook_id'] ?? ''));
            $platform = trim((string) ($courseWebhookId['platform'] ?? ''));

            if ($webhookId === '') {
                continue;
            }

            if ($platform === '') {
                throw ValidationException::withMessages([
                    'curso_webhook_ids' => 'Cada ID de webhook do curso precisa informar uma plataforma.',
                ]);
            }

            $normalized[] = [
                'webhook_id' => $webhookId,
                'platform' => $platform,
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param  array<int, array{webhook_id:string, platform:string}>  $courseWebhookIds
     */
    private function ensureUniqueCourseWebhookIds(array $courseWebhookIds): void
    {
        $seen = [];

        foreach ($courseWebhookIds as $courseWebhookId) {
            $key = Str::lower($courseWebhookId['webhook_id']);

            if (array_key_exists($key, $seen)) {
                throw ValidationException::withMessages([
                    'curso_webhook_ids' => 'Os IDs de webhook do curso não podem se repetir.',
                ]);
            }

            $seen[$key] = true;
        }
    }

    /**
     * @param  array<int, array{webhook_id:string, platform:string}>  $courseWebhookIds
     */
    private function ensureCourseWebhookIdsAvailableForTenant(int $systemSettingId, array $courseWebhookIds, ?int $ignoreCourseId = null): void
    {
        if ($systemSettingId <= 0 || $courseWebhookIds === []) {
            return;
        }

        $webhookIds = collect($courseWebhookIds)
            ->pluck('webhook_id')
            ->map(fn (string $webhookId): string => trim($webhookId))
            ->filter()
            ->values()
            ->all();

        if ($webhookIds === []) {
            return;
        }

        $conflictingWebhookId = CourseWebhookId::query()
            ->where('system_setting_id', $systemSettingId)
            ->whereIn('webhook_id', $webhookIds)
            ->when($ignoreCourseId !== null, fn ($query) => $query->where('course_id', '!=', $ignoreCourseId))
            ->value('webhook_id');

        if ($conflictingWebhookId === null) {
            return;
        }

        throw ValidationException::withMessages([
            'curso_webhook_ids' => "O ID de webhook [{$conflictingWebhookId}] já está vinculado a outro curso desta escola.",
        ]);
    }

    /**
     * @param  array<int, array{webhook_id:string, platform:string}>  $courseWebhookIds
     */
    private function syncCourseWebhookIds(Course $course, array $courseWebhookIds): void
    {
        $course->courseWebhookIds()->delete();

        if ($courseWebhookIds === []) {
            return;
        }

        $systemSettingId = (int) ($course->system_setting_id ?? 0) ?: null;

        $courseWebhookIds = array_map(
            static fn (array $courseWebhookId): array => [
                'system_setting_id' => $systemSettingId,
                'webhook_id' => $courseWebhookId['webhook_id'],
                'platform' => $courseWebhookId['platform'],
            ],
            $courseWebhookIds
        );

        $course->courseWebhookIds()->createMany($courseWebhookIds);
    }

    /**
     * @return array<string, string>
     */
    private function courseValidationMessages(): array
    {
        return [
            'curso_webhook_ids.*.platform.required_with' => 'Cada ID de webhook do curso precisa informar uma plataforma.',
        ];
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
        if (! $request->user()?->hasAdminPrivileges()) {
            return;
        }

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
