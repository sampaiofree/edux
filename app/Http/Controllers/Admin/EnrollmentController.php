<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentAccessStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request): View
    {
        $search = (string) $request->query('search', '');
        $isNumericSearch = $search !== '' && is_numeric($search);

        $enrollments = Enrollment::query()
            ->with(['course', 'user'])
            ->when($search, function ($query) use ($search, $isNumericSearch) {
                $query->where(function ($sub) use ($search, $isNumericSearch) {
                    $sub->whereHas('course', function ($course) use ($search, $isNumericSearch) {
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
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.enroll.index', compact('enrollments', 'search'));
    }

    public function create(Request $request): View
    {
        $courseSearch = (string) $request->query('course_search', '');
        $userSearch = (string) $request->query('user_search', '');

        [$courses, $selectedCourse] = $this->courseOptions(
            $courseSearch,
            $request->old('course_id')
        );
        [$users, $selectedUser] = $this->userOptions(
            $userSearch,
            $request->old('user_id')
        );

        return view('admin.enroll.create', [
            'courses' => $courses,
            'users' => $users,
            'courseSearch' => $courseSearch,
            'userSearch' => $userSearch,
            'selectedCourse' => $selectedCourse,
            'selectedUser' => $selectedUser,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules($request));
        $payload = $this->preparePayload($validated, $request);

        Enrollment::create($payload);

        return redirect()
            ->route('admin.enroll.index')
            ->with('status', 'Matricula criada.');
    }

    public function edit(Request $request, Enrollment $enrollment): View
    {
        $courseSearch = (string) $request->query('course_search', '');
        $userSearch = (string) $request->query('user_search', '');
        $selectedCourseId = $request->old('course_id', $enrollment->course_id);
        $selectedUserId = $request->old('user_id', $enrollment->user_id);

        [$courses, $selectedCourse] = $this->courseOptions(
            $courseSearch,
            $selectedCourseId
        );
        [$users, $selectedUser] = $this->userOptions(
            $userSearch,
            $selectedUserId
        );

        return view('admin.enroll.edit', [
            'enrollment' => $enrollment,
            'courses' => $courses,
            'users' => $users,
            'courseSearch' => $courseSearch,
            'userSearch' => $userSearch,
            'selectedCourse' => $selectedCourse,
            'selectedUser' => $selectedUser,
        ]);
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate($this->validationRules($request, $enrollment));
        $payload = $this->preparePayload($validated, $request, $enrollment);

        $enrollment->update($payload);

        return redirect()
            ->route('admin.enroll.index')
            ->with('status', 'Matricula atualizada.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return redirect()
            ->route('admin.enroll.index')
            ->with('status', 'Matricula removida.');
    }

    private function validationRules(Request $request, ?Enrollment $enrollment = null): array
    {
        $courseId = $request->input('course_id');
        $userId = $request->input('user_id');
        $systemSettingId = (int) ($request->user()?->system_setting_id ?? 0);

        $courseRules = ['required', 'integer', Rule::exists('courses', 'id')->where('system_setting_id', $systemSettingId)];
        if ($courseId !== null && $courseId !== '' && $userId !== null && $userId !== '') {
            $courseRules[] = Rule::unique('enrollments', 'course_id')
                ->where('user_id', $userId)
                ->ignore($enrollment?->id);
        }

        return [
            'course_id' => $courseRules,
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('system_setting_id', $systemSettingId)],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'completed_at' => ['nullable', 'date'],
            'access_status' => ['required', Rule::in([
                EnrollmentAccessStatus::ACTIVE->value,
                EnrollmentAccessStatus::BLOCKED->value,
            ])],
            'access_block_reason' => ['nullable', 'string', 'max:255'],
            'access_blocked_at' => ['nullable', 'date'],
            'manual_override' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array{0:\Illuminate\Support\Collection<int, Course>,1:?Course}
     */
    private function courseOptions(string $search, $selectedId): array
    {
        $isNumericSearch = $search !== '' && is_numeric($search);

        $courses = Course::query()
            ->when($search !== '', function ($query) use ($search, $isNumericSearch) {
                $query->where(function ($sub) use ($search, $isNumericSearch) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");

                    if ($isNumericSearch) {
                        $sub->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy('title')
            ->limit(50)
            ->get();

        $selectedCourse = null;
        if ($selectedId) {
            $selectedCourse = Course::find($selectedId);
            if ($selectedCourse && ! $courses->contains('id', $selectedCourse->id)) {
                $courses = $courses->prepend($selectedCourse);
            }
        }

        return [$courses, $selectedCourse];
    }

    /**
     * @return array{0:\Illuminate\Support\Collection<int, User>,1:?User}
     */
    private function userOptions(string $search, $selectedId): array
    {
        $isNumericSearch = $search !== '' && is_numeric($search);

        $users = User::query()
            ->when($search !== '', function ($query) use ($search, $isNumericSearch) {
                $query->where(function ($sub) use ($search, $isNumericSearch) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%");

                    if ($isNumericSearch) {
                        $sub->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get();

        $selectedUser = null;
        if ($selectedId) {
            $selectedUser = User::find($selectedId);
            if ($selectedUser && ! $users->contains('id', $selectedUser->id)) {
                $users = $users->prepend($selectedUser);
            }
        }

        return [$users, $selectedUser];
    }

    private function preparePayload(array $validated, Request $request, ?Enrollment $enrollment = null): array
    {
        $manualOverride = (bool) ($validated['manual_override'] ?? false);
        $payload = $validated;

        $payload['manual_override'] = $manualOverride;

        if ($manualOverride) {
            $payload['access_status'] = EnrollmentAccessStatus::ACTIVE->value;
            $payload['access_block_reason'] = null;
            $payload['access_blocked_at'] = null;
            $payload['manual_override_by'] = $request->user()?->id;
            $payload['manual_override_at'] = now();

            return $payload;
        }

        if ($enrollment?->manual_override) {
            $payload['manual_override_by'] = null;
            $payload['manual_override_at'] = null;
        } else {
            $payload['manual_override_by'] = $enrollment?->manual_override_by;
            $payload['manual_override_at'] = $enrollment?->manual_override_at;
        }

        return $payload;
    }
}
