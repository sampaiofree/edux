<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', 'all');
        $role = in_array($role, ['all', ...collect(UserRole::cases())->pluck('value')->all()], true) ? $role : 'all';

        $users = User::withoutGlobalScopes()
            ->with('systemSetting')
            ->when($role !== 'all', fn ($query) => $query->where('role', $role))
            ->when($search !== '', function ($query) use ($search): void {
                $isNumericSearch = is_numeric($search);

                $query->where(function ($subQuery) use ($search, $isNumericSearch): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhereHas('systemSetting', function ($systemSettingQuery) use ($search): void {
                            $systemSettingQuery->where('escola_nome', 'like', "%{$search}%")
                                ->orWhere('domain', 'like', "%{$search}%");
                        });

                    if ($isNumericSearch) {
                        $subQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('sa.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
        ]);
    }

    public function create(): View
    {
        return view('sa.users.create', [
            'roles' => UserRole::cases(),
            'tenants' => $this->tenants(),
        ]);
    }

    public function edit(int $id): View
    {
        $user = $this->findUser($id);

        return view('sa.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
            'tenants' => $this->tenants(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rulesForStore($request));

        $attributes = [
            'name' => $validated['name'],
            'display_name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'system_setting_id' => (int) $validated['system_setting_id'],
            'password' => $validated['password'],
            'whatsapp' => $validated['whatsapp'] ?? null,
            'qualification' => $validated['qualification'] ?? null,
        ];

        if ($request->hasFile('profile_photo')) {
            $attributes['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user = User::create($attributes);

        return redirect()
            ->route('sa.users.edit', $user->id)
            ->with('status', 'Usuário criado.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $this->findUser($id);

        $validated = $request->validate($this->rulesForUpdate($request, $user));

        $this->ensureUserChangeIsAllowed($user, (int) $validated['system_setting_id'], (string) $validated['role']);

        $user->fill([
            'name' => $validated['name'],
            'display_name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'system_setting_id' => (int) $validated['system_setting_id'],
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
            ->route('sa.users.edit', $user->id)
            ->with('status', 'Usuário atualizado.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $this->findUser($id);
        $deletingCurrentUser = $request->user()?->is($user) ?? false;

        $user->delete();

        if ($deletingCurrentUser) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return redirect()
            ->route('sa.users.index')
            ->with('status', 'Usuário removido.');
    }

    private function findUser(int $id): User
    {
        return User::withoutGlobalScopes()
            ->with('systemSetting')
            ->findOrFail($id);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesForStore(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('system_setting_id', (int) $request->input('system_setting_id')),
            ],
            'role' => ['required', Rule::in(collect(UserRole::cases())->pluck('value')->all())],
            'system_setting_id' => ['required', 'integer', Rule::exists('system_settings', 'id')],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'qualification' => ['nullable', 'string'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesForUpdate(Request $request, User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('system_setting_id', (int) $request->input('system_setting_id'))
                    ->ignore($user->id),
            ],
            'role' => ['required', Rule::in(collect(UserRole::cases())->pluck('value')->all())],
            'system_setting_id' => ['required', 'integer', Rule::exists('system_settings', 'id')],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'qualification' => ['nullable', 'string'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    private function ensureUserChangeIsAllowed(User $user, int $targetSystemSettingId, string $targetRole): void
    {
        $messages = [];

        $ownedCourseTenantIds = Course::withoutGlobalScopes()
            ->where('owner_id', $user->id)
            ->whereNotNull('system_setting_id')
            ->distinct()
            ->pluck('system_setting_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id !== $targetSystemSettingId)
            ->values();

        if ($ownedCourseTenantIds->isNotEmpty()) {
            $messages['system_setting_id'] = 'Não é possível mudar o tenant deste usuário enquanto ele for responsável por cursos de outra escola.';
        }

        $enrollmentTenantIds = Enrollment::withoutGlobalScopes()
            ->leftJoin('courses', 'courses.id', '=', 'enrollments.course_id')
            ->where('enrollments.user_id', $user->id)
            ->where(function ($query): void {
                $query->whereNotNull('enrollments.system_setting_id')
                    ->orWhereNotNull('courses.system_setting_id');
            })
            ->selectRaw('COALESCE(enrollments.system_setting_id, courses.system_setting_id) as resolved_system_setting_id')
            ->pluck('resolved_system_setting_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id !== $targetSystemSettingId)
            ->values();

        if ($enrollmentTenantIds->isNotEmpty()) {
            $messages['system_setting_id'] = 'Não é possível mudar o tenant deste usuário enquanto existirem matrículas vinculadas a outra escola.';
        }

        $ownedSystemSettingIds = SystemSetting::query()
            ->where('owner_user_id', $user->id)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id !== $targetSystemSettingId)
            ->values();

        if ($ownedSystemSettingIds->isNotEmpty()) {
            $messages['system_setting_id'] = 'Não é possível mover este usuário porque ele é o proprietário de outra escola.';
        }

        if ($targetRole === UserRole::STUDENT->value) {
            $ownsCourses = Course::withoutGlobalScopes()->where('owner_id', $user->id)->exists();
            $ownsSystemSetting = SystemSetting::query()->where('owner_user_id', $user->id)->exists();

            if ($ownsCourses || $ownsSystemSetting) {
                $messages['role'] = 'Não é possível trocar o papel para aluno enquanto o usuário for responsável por cursos ou escola.';
            }
        }

        if ($targetRole === UserRole::TEACHER->value) {
            $ownsSystemSetting = SystemSetting::query()->where('owner_user_id', $user->id)->exists();

            if ($ownsSystemSetting) {
                $messages['role'] = 'Não é possível trocar o papel para professor enquanto o usuário for responsável pela escola.';
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function tenants()
    {
        return SystemSetting::query()
            ->with(['owner' => fn ($query) => $query->withoutGlobalScopes()])
            ->orderByRaw('COALESCE(escola_nome, domain, id)')
            ->get();
    }
}
