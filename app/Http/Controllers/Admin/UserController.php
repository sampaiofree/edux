<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request): View
    {
        $search = (string) $request->query('search', '');

        $users = User::query()
            ->when($search, fn ($query) => $query
                ->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%");
                }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
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
}
