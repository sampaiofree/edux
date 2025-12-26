<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\CourseCheckout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CourseCheckoutsManager extends Component
{
    public Course $course;
    public ?CourseCheckout $editingCheckout = null;

    public ?int $hours = null;
    public ?string $price = null;
    public ?string $checkout_url = null;
    public bool $is_active = true;

    public function mount(int $courseId): void
    {
        $this->course = Course::with([
            'checkouts' => fn ($query) => $query->orderBy('hours'),
        ])->findOrFail($courseId);

        abort_unless($this->canManageCourse(), 403);
    }

    public function render(): View
    {
        return view('livewire.admin.course-checkouts-manager', [
            'checkouts' => $this->course->checkouts,
        ]);
    }

    protected function rules(): array
    {
        return [
            'hours' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('course_checkouts', 'hours')
                    ->where('course_id', $this->course->id)
                    ->ignore($this->editingCheckout?->id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'checkout_url' => ['required', 'url'],
            'is_active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'hours.unique' => 'Essa carga horária já está cadastrada para este curso.',
        ];
    }

    public function updatedHours($value): void
    {
        if ($value === null || $value === '') {
            $this->resetErrorBag('hours');
            return;
        }

        $this->validateOnly('hours');
    }

    public function saveCheckout(): void
    {
        $data = $this->validate();

        if ($this->editingCheckout) {
            $this->editingCheckout->update($data);
            $message = 'Checkout atualizado.';
        } else {
            $this->course->checkouts()->create($data);
            $message = 'Checkout cadastrado.';
        }

        $this->refreshCourse();
        $this->resetForm();
        session()->flash('status', $message);
    }

    public function editCheckout(int $checkoutId): void
    {
        $checkout = $this->course->checkouts->firstWhere('id', $checkoutId);

        if (! $checkout) {
            return;
        }

        $this->editingCheckout = $checkout;
        $this->hours = $checkout->hours;
        $this->price = $checkout->price;
        $this->checkout_url = $checkout->checkout_url;
        $this->is_active = $checkout->is_active;
        $this->resetErrorBag();
    }

    public function deactivateCheckout(int $checkoutId): void
    {
        $checkout = $this->course->checkouts->firstWhere('id', $checkoutId);

        if (! $checkout || ! $checkout->is_active) {
            return;
        }

        $checkout->update(['is_active' => false]);
        $this->refreshCourse();
        session()->flash('status', 'Checkout desativado.');
    }

    public function resetForm(): void
    {
        $this->editingCheckout = null;
        $this->hours = null;
        $this->price = null;
        $this->checkout_url = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    private function refreshCourse(): void
    {
        $this->course->refresh()->load([
            'checkouts' => fn ($query) => $query->orderBy('hours'),
        ]);
    }

    private function canManageCourse(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher() && $this->course->owner_id === $user->id;
    }
}
