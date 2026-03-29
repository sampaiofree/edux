<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class UserForm extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $role;

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $qualification = null;

    public ?string $whatsapp = null;

    public $profilePhoto;

    public function mount(): void
    {
        $this->role = UserRole::STUDENT->value;
    }

    public function save(): void
    {
        $systemSettingId = auth()->user()?->adminContextSystemSettingId();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where('system_setting_id', $systemSettingId)],
            'role' => ['required', Rule::in(collect(UserRole::cases())->pluck('value')->all())],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'qualification' => ['nullable', 'string'],
            'profilePhoto' => ['nullable', 'image', 'max:2048'],
        ], [
            'role.in' => 'Selecione um papel válido.',
        ]);

        $photoPath = $this->profilePhoto
            ? $this->profilePhoto->store('profile-photos', 'public')
            : null;

        User::create([
            'name' => $data['name'],
            'display_name' => $data['name'],
            'email' => $data['email'],
            'system_setting_id' => $systemSettingId,
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'whatsapp' => $this->whatsapp,
            'qualification' => $this->qualification,
            'profile_photo_path' => $photoPath,
        ]);

        session()->flash('status', 'Usuário criado com sucesso.');

        $this->reset([
            'name',
            'email',
            'password',
            'password_confirmation',
            'qualification',
            'whatsapp',
            'profilePhoto',
        ]);
        $this->role = UserRole::STUDENT->value;

        $this->dispatch('user-created');
    }

    public function render()
    {
        return view('livewire.admin.user-form', [
            'roles' => UserRole::cases(),
        ]);
    }
}
