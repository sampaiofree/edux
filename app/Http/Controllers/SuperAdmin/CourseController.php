<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SupportWhatsappNumber;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseController extends Controller
{
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
            'owners' => $this->owners(),
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
        ]);

        $targetSystemSettingId = (int) $validated['system_setting_id'];
        $ownerId = (int) $validated['owner_id'];

        $this->ensureCourseChangeIsAllowed($course, $targetSystemSettingId, $ownerId);

        $course->fill([
            'system_setting_id' => $targetSystemSettingId,
            'owner_id' => $ownerId,
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
            'promo_video_url' => $validated['promo_video_url'] ?? null,
        ]);

        if ($course->isDirty('title')) {
            $course->slug = $this->generateUniqueSlug($course->title, $course->id);
        }

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

    private function ensureCourseChangeIsAllowed(Course $course, int $targetSystemSettingId, int $ownerId): void
    {
        $messages = [];

        $owner = User::withoutGlobalScopes()->find($ownerId);
        if (! $owner || ($owner->role->value ?? $owner->role) !== UserRole::ADMIN->value || (int) $owner->system_setting_id !== $targetSystemSettingId) {
            $messages['owner_id'] = 'Selecione um responsável administrador que pertença à escola escolhida.';
        }

        if ($targetSystemSettingId !== (int) $course->system_setting_id) {
            $enrollmentTenantIds = Enrollment::withoutGlobalScopes()
                ->where('course_id', $course->id)
                ->whereNotNull('system_setting_id')
                ->distinct()
                ->pluck('system_setting_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id !== $targetSystemSettingId)
                ->values();

            if ($enrollmentTenantIds->isNotEmpty()) {
                $messages['system_setting_id'] = 'Não é possível mover o curso enquanto existirem matrículas vinculadas a outra escola.';
            }

            $enrolledUserTenantIds = Enrollment::withoutGlobalScopes()
                ->join('users', 'users.id', '=', 'enrollments.user_id')
                ->where('enrollments.course_id', $course->id)
                ->whereNotNull('users.system_setting_id')
                ->distinct()
                ->pluck('users.system_setting_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id !== $targetSystemSettingId)
                ->values();

            if ($enrolledUserTenantIds->isNotEmpty()) {
                $messages['system_setting_id'] = 'Não é possível mover o curso enquanto existirem alunos de outra escola matriculados nele.';
            }

            $brandingTenantIds = CertificateBranding::withoutGlobalScopes()
                ->where('course_id', $course->id)
                ->whereNotNull('system_setting_id')
                ->distinct()
                ->pluck('system_setting_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id !== $targetSystemSettingId)
                ->values();

            if ($brandingTenantIds->isNotEmpty()) {
                $messages['system_setting_id'] = 'Não é possível mover o curso enquanto o branding do certificado estiver ligado a outra escola.';
            }

            if ($course->support_whatsapp_number_id) {
                $supportWhatsappTenantId = SupportWhatsappNumber::withoutGlobalScopes()
                    ->whereKey($course->support_whatsapp_number_id)
                    ->value('system_setting_id');

                if ($supportWhatsappTenantId !== null && (int) $supportWhatsappTenantId !== $targetSystemSettingId) {
                    $messages['system_setting_id'] = 'Não é possível mover o curso enquanto o WhatsApp de atendimento estiver ligado a outra escola.';
                }
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
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

    private function owners()
    {
        return User::withoutGlobalScopes()
            ->with('systemSetting')
            ->where('role', UserRole::ADMIN->value)
            ->orderBy('name')
            ->get();
    }
}
