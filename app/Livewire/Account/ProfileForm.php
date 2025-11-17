<?php

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class ProfileForm extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public ?string $qualification = null;
    public ?string $password = null;
    public ?string $password_confirmation = null;
    public $profile_photo;

    public bool $canRename = true;
    public ?string $currentPhotoPath = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->qualification = $user->qualification;
        $this->canRename = ! ($user->isStudent() && ! $user->name_change_available);
        $this->currentPhotoPath = $user->profile_photo_path;
    }

    public function save(): void
    {
        $user = Auth::user();

        if (! $this->canRename && $this->name !== $user->name) {
            $message = 'Você já utilizou sua troca de nome.';
            $this->addError('name', $message);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $message,
            ]);
            return;
        }

        $this->validate($this->rules());

        $user->email = $this->email;
        $user->qualification = $this->qualification;

        $nameChanged = $user->name !== $this->name;

        if ($this->canRename || ! $nameChanged) {
            $user->name = $this->name;
            if ($user->isStudent()) {
                $user->display_name = $this->name;
                if ($nameChanged && $this->canRename) {
                    $user->name_change_available = false;
                    $this->canRename = false;
                }
            }
        }

        if ($this->password) {
            $user->password = Hash::make($this->password);
        }

        if ($this->profile_photo) {
            if ($this->currentPhotoPath) {
                Storage::disk('public')->delete($this->currentPhotoPath);
            }

            $this->currentPhotoPath = $this->profile_photo->store('profile-photos', 'public');
            $user->profile_photo_path = $this->currentPhotoPath;
            $this->reset('profile_photo');
        }

        $user->save();

        $successMessage = 'Perfil atualizado com sucesso.';
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $successMessage,
        ]);
        session()->flash('status', $successMessage);
        $this->reset(['password', 'password_confirmation']);
    }

    public function removePhoto(): void
    {
        $user = Auth::user();

        if ($this->currentPhotoPath) {
            Storage::disk('public')->delete($this->currentPhotoPath);
            $this->currentPhotoPath = null;
        }

        $user->profile_photo_path = null;
        $user->save();
    }

    public function render()
    {
        return view('livewire.account.profile-form', [
            'currentPhotoUrl' => $this->currentPhotoPath
                ? Storage::disk('public')->url($this->currentPhotoPath)
                : null,
        ]);
    }

    private function rules(): array
    {
        $userId = Auth::id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$userId],
            'qualification' => ['nullable', 'string'],
            'password' => ['nullable', 'min:8', 'same:password_confirmation'],
            'password_confirmation' => ['nullable'],
            'profile_photo' => ['nullable', 'image', 'max:512'],
        ];
    }
}
