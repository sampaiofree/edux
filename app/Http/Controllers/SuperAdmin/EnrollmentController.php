<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\EnrollmentAccessStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $enrollments = Enrollment::withoutGlobalScopes()
            ->with([
                'systemSetting',
                'course' => fn ($query) => $query->withoutGlobalScopes(),
                'user' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $isNumericSearch = is_numeric($search);

                $query->where(function ($subQuery) use ($search, $isNumericSearch): void {
                    $subQuery->whereHas('course', function ($courseQuery) use ($search, $isNumericSearch): void {
                        $courseQuery->withoutGlobalScopes()
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");

                        if ($isNumericSearch) {
                            $courseQuery->orWhere('id', (int) $search);
                        }
                    })->orWhereHas('user', function ($userQuery) use ($search, $isNumericSearch): void {
                        $userQuery->withoutGlobalScopes()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('whatsapp', 'like', "%{$search}%");

                        if ($isNumericSearch) {
                            $userQuery->orWhere('id', (int) $search);
                        }
                    })->orWhereHas('systemSetting', function ($systemSettingQuery) use ($search): void {
                        $systemSettingQuery->where('escola_nome', 'like', "%{$search}%")
                            ->orWhere('domain', 'like', "%{$search}%");
                    });

                    if ($isNumericSearch) {
                        $subQuery->orWhere('id', (int) $search)
                            ->orWhere('course_id', (int) $search)
                            ->orWhere('user_id', (int) $search);
                    }
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sa.enrollments.index', [
            'enrollments' => $enrollments,
            'search' => $search,
        ]);
    }

    public function edit(int $id): View
    {
        $enrollment = $this->findEnrollment($id);

        return view('sa.enrollments.edit', [
            'enrollment' => $enrollment,
            'tenants' => $this->tenants(),
            'courses' => $this->courses(),
            'users' => $this->users(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $enrollment = $this->findEnrollment($id);
        $targetSystemSettingId = (int) $request->input('system_setting_id');

        $validated = $request->validate([
            'system_setting_id' => ['required', 'integer', Rule::exists('system_settings', 'id')],
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')->where('system_setting_id', $targetSystemSettingId),
                Rule::unique('enrollments', 'course_id')
                    ->where('user_id', $request->input('user_id'))
                    ->ignore($enrollment->id),
            ],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('system_setting_id', $targetSystemSettingId)],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'completed_at' => ['nullable', 'date'],
            'access_status' => ['required', Rule::in([
                EnrollmentAccessStatus::ACTIVE->value,
                EnrollmentAccessStatus::BLOCKED->value,
            ])],
            'access_block_reason' => ['nullable', 'string', 'max:255'],
            'access_blocked_at' => ['nullable', 'date'],
            'manual_override' => ['nullable', 'boolean'],
        ]);

        $enrollment->update([
            'system_setting_id' => (int) $validated['system_setting_id'],
            'course_id' => (int) $validated['course_id'],
            'user_id' => (int) $validated['user_id'],
            'progress_percent' => (int) $validated['progress_percent'],
            'completed_at' => $validated['completed_at'] ?? null,
            'access_status' => $validated['access_status'],
            'access_block_reason' => $validated['access_block_reason'] ?? null,
            'access_blocked_at' => $validated['access_blocked_at'] ?? null,
            'manual_override' => $request->boolean('manual_override'),
        ]);

        return redirect()
            ->route('sa.enrollments.edit', $enrollment->id)
            ->with('status', 'Matrícula atualizada.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $enrollment = $this->findEnrollment($id);
        $enrollment->delete();

        return redirect()
            ->route('sa.enrollments.index')
            ->with('status', 'Matrícula removida.');
    }

    private function findEnrollment(int $id): Enrollment
    {
        return Enrollment::withoutGlobalScopes()
            ->with([
                'systemSetting',
                'course' => fn ($query) => $query->withoutGlobalScopes(),
                'user' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->findOrFail($id);
    }

    private function tenants()
    {
        return SystemSetting::query()
            ->with(['owner' => fn ($query) => $query->withoutGlobalScopes()])
            ->orderByRaw('COALESCE(escola_nome, domain, id)')
            ->get();
    }

    private function courses()
    {
        return Course::withoutGlobalScopes()
            ->with([
                'systemSetting',
                'owner' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->orderBy('title')
            ->get();
    }

    private function users()
    {
        return User::withoutGlobalScopes()
            ->with('systemSetting')
            ->orderBy('name')
            ->get();
    }
}
