<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CourseTenantTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseTenantTransferService $courseTenantTransferService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $status = in_array($status, ['all', 'draft', 'published', 'archived'], true) ? $status : 'all';

        $courses = Course::withoutGlobalScopes()
            ->with([
                'systemSetting',
                'owner' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $isNumericSearch = is_numeric($search);

                $query->where(function ($subQuery) use ($search, $isNumericSearch): void {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('owner', function ($ownerQuery) use ($search): void {
                            $ownerQuery->withoutGlobalScopes()
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('systemSetting', function ($systemSettingQuery) use ($search): void {
                            $systemSettingQuery->where('escola_nome', 'like', "%{$search}%")
                                ->orWhere('domain', 'like', "%{$search}%");
                        });

                    if ($isNumericSearch) {
                        $subQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sa.courses.index', [
            'courses' => $courses,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function edit(int $id): View
    {
        $course = $this->findCourse($id);

        return view('sa.courses.edit', [
            'course' => $course,
            'tenants' => $this->tenants(),
            'ownersByTenant' => $this->ownersByTenant(),
            'initialTenantId' => (string) old('system_setting_id', $course->system_setting_id),
            'initialOwnerId' => (string) old('owner_id', $course->owner_id),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $course = $this->findCourse($id);

        $validated = $request->validate([
            'system_setting_id' => ['required', 'integer', Rule::exists('system_settings', 'id')],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'published_at' => ['nullable', 'date'],
            'promo_video_url' => ['nullable', 'url'],
            'is_global' => ['nullable', 'boolean'],
        ]);

        $targetSystemSettingId = (int) $validated['system_setting_id'];
        $ownerId = (int) $validated['owner_id'];

        $this->ensureOwnerMatchesTenant($targetSystemSettingId, $ownerId);

        $payload = [
            'system_setting_id' => $targetSystemSettingId,
            'owner_id' => $ownerId,
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
            'promo_video_url' => $validated['promo_video_url'] ?? null,
            'is_global' => array_key_exists('is_global', $validated)
                ? (bool) $validated['is_global']
                : (bool) $course->is_global,
        ];

        if ($validated['title'] !== $course->title) {
            $payload['slug'] = $this->generateUniqueSlug($validated['title'], $course->id);
        }

        if ($targetSystemSettingId !== (int) $course->system_setting_id) {
            $course = $this->courseTenantTransferService->transfer($course, $payload);

            return redirect()
                ->route('sa.courses.edit', $course->id)
                ->with('status', 'Curso transferido para a nova escola com matrículas e histórico educacional.');
        }

        $course->fill($payload);
        $course->save();

        return redirect()
            ->route('sa.courses.edit', $course->id)
            ->with('status', 'Curso atualizado.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $course = $this->findCourse($id);
        $course->delete();

        return redirect()
            ->route('sa.courses.index')
            ->with('status', 'Curso removido.');
    }

    private function findCourse(int $id): Course
    {
        return Course::withoutGlobalScopes()
            ->with([
                'systemSetting',
                'owner' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->findOrFail($id);
    }

    private function ensureOwnerMatchesTenant(int $targetSystemSettingId, int $ownerId): void
    {
        $owner = User::withoutGlobalScopes()->find($ownerId);
        if (! $owner || ($owner->role->value ?? $owner->role) !== UserRole::ADMIN->value || (int) $owner->system_setting_id !== $targetSystemSettingId) {
            throw ValidationException::withMessages([
                'owner_id' => 'Selecione um responsável administrador que pertença à escola escolhida.',
            ]);
        }
    }

    private function generateUniqueSlug(string $title, ?int $ignoreCourseId = null): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? $base : 'curso';
        $slug = $base;
        $counter = 2;

        while ($this->slugExists($slug, $ignoreCourseId)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreCourseId = null): bool
    {
        return Course::withoutGlobalScopes()
            ->where('slug', $slug)
            ->when($ignoreCourseId !== null, fn ($query) => $query->where('id', '!=', $ignoreCourseId))
            ->exists();
    }

    private function tenants()
    {
        return SystemSetting::query()
            ->with(['owner' => fn ($query) => $query->withoutGlobalScopes()])
            ->orderByRaw('COALESCE(escola_nome, domain, id)')
            ->get();
    }

    private function ownersByTenant(): array
    {
        return User::withoutGlobalScopes()
            ->with('systemSetting')
            ->where('role', UserRole::ADMIN->value)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'system_setting_id'])
            ->groupBy(fn (User $owner): string => (string) $owner->system_setting_id)
            ->map(fn ($owners) => $owners
                ->map(fn (User $owner): array => [
                    'id' => (string) $owner->id,
                    'label' => trim($owner->name.' - '.$owner->email),
                ])
                ->values()
                ->all()
            )
            ->all();
    }
}
