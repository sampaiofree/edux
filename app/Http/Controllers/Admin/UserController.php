<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $courses = Course::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        $users = $this->filteredUsersQuery($filters)
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $filters['search'],
            'createdFrom' => $filters['createdFrom'],
            'createdTo' => $filters['createdTo'],
            'courseId' => $filters['courseId'],
            'courses' => $courses,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $users = $this->filteredUsersQuery($filters)->cursor();

        return $this->streamCsv(
            'usuarios-filtrados',
            ['id', 'nome', 'email', 'papel', 'whatsapp', 'data_cadastro'],
            function ($handle) use ($users): void {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id,
                        $user->preferredName(),
                        $user->email,
                        $user->role->label(),
                        $user->whatsapp ?? '',
                        $user->created_at?->format('Y-m-d H:i:s') ?? '',
                    ], ';');
                }
            }
        );
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')
                ->where('system_setting_id', $user->system_setting_id)
                ->ignore($user->id)],
            'role' => ['required', Rule::in(collect(UserRole::cases())->pluck('value')->all())],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'qualification' => ['nullable', 'string'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $this->ensureRoleChangeIsAllowed($user, (string) $validated['role']);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'display_name' => $validated['name'],
            'role' => $validated['role'],
            'whatsapp' => $validated['whatsapp'] ?? null,
            'qualification' => $validated['qualification'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        if ($request->boolean('remove_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->save();

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Usuário atualizado.');
    }

    private function ensureRoleChangeIsAllowed(User $user, string $targetRole): void
    {
        $messages = [];

        $ownsCourses = Course::query()->where('owner_id', $user->id)->exists();
        $ownsSystemSetting = SystemSetting::query()->where('owner_user_id', $user->id)->exists();

        if ($targetRole === UserRole::STUDENT->value && ($ownsCourses || $ownsSystemSetting)) {
            $messages['role'] = 'Não é possível trocar o papel para aluno enquanto o usuário for responsável por cursos ou escola.';
        }

        if ($targetRole === UserRole::TEACHER->value && $ownsSystemSetting) {
            $messages['role'] = 'Não é possível trocar o papel para professor enquanto o usuário for responsável pela escola.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array{
     *     search:string,
     *     createdFrom:string,
     *     createdTo:string,
     *     from:?Carbon,
     *     to:?Carbon,
     *     courseId:?int
     * }  $filters
     */
    private function filteredUsersQuery(array $filters): Builder
    {
        return User::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%");
                });
            })
            ->when($filters['from'], fn (Builder $query, Carbon $from) => $query->where('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, Carbon $to) => $query->where('created_at', '<=', $to))
            ->when($filters['courseId'] !== null, function (Builder $query) use ($filters): void {
                $query->whereHas('enrollments', function (Builder $enrollmentQuery) use ($filters): void {
                    $enrollmentQuery->where('course_id', $filters['courseId']);
                });
            })
            ->orderBy('name');
    }

    /**
     * @return array{
     *     search:string,
     *     createdFrom:string,
     *     createdTo:string,
     *     from:?Carbon,
     *     to:?Carbon,
     *     courseId:?int
     * }
     */
    private function filters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $from = $this->parseDate((string) $request->query('created_from', ''));
        $to = $this->parseDate((string) $request->query('created_to', ''));

        return [
            'search' => $search,
            'createdFrom' => $from?->toDateString() ?? '',
            'createdTo' => $to?->toDateString() ?? '',
            'from' => $from?->startOfDay(),
            'to' => $to?->endOfDay(),
            'courseId' => $this->resolveCourseFilterId((string) $request->query('course_id', '')),
        ];
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveCourseFilterId(string $value): ?int
    {
        $value = trim($value);

        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }

        $courseId = (int) $value;

        return Course::query()->whereKey($courseId)->exists()
            ? $courseId
            : null;
    }

    /**
     * @param  array<int, string>  $header
     * @param  \Closure(resource): void  $writer
     */
    private function streamCsv(string $prefix, array $header, \Closure $writer): StreamedResponse
    {
        $filename = sprintf('%s-%s.csv', $prefix, now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($header, $writer): void {
            $handle = fopen('php://output', 'wb');

            if (! is_resource($handle)) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $header, ';');
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
