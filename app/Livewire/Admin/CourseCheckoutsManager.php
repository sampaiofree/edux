<?php

namespace App\Livewire\Admin;

use App\Models\CheckoutBonus;
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
    public ?CheckoutBonus $editingBonus = null;

    public ?string $nome = null;
    public ?string $descricao = null;
    public ?int $hours = null;
    public ?string $price = null;
    public ?string $checkout_url = null;
    public bool $is_active = true;

    public bool $showCheckoutModal = false;
    public bool $showBonusListModal = false;
    public bool $showBonusFormModal = false;

    public ?int $bonusListCheckoutId = null;
    public ?int $bonusCheckoutId = null;
    public ?string $bonus_nome = null;
    public ?string $bonus_descricao = null;
    public ?string $bonus_preco = null;

    public function mount(int $courseId): void
    {
        $this->course = Course::with([
            'checkouts' => fn ($query) => $query
                ->with(['bonuses' => fn ($bonusQuery) => $bonusQuery->orderBy('id')])
                ->orderBy('hours'),
        ])->findOrFail($courseId);

        abort_unless($this->canManageCourse(), 403);
    }

    public function render(): View
    {
        return view('livewire.admin.course-checkouts-manager', [
            'checkouts' => $this->course->checkouts,
            'selectedBonusCheckout' => $this->bonusListCheckoutId
                ? $this->course->checkouts->firstWhere('id', $this->bonusListCheckoutId)
                : null,
        ]);
    }

    protected function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
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
            'hours.unique' => 'Essa carga horaria ja esta cadastrada para este curso.',
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

    public function openCreateCheckoutModal(): void
    {
        $this->resetCheckoutForm();
        $this->showCheckoutModal = true;
    }

    public function editCheckout(int $checkoutId): void
    {
        $checkout = $this->findCheckout($checkoutId);

        if (! $checkout) {
            return;
        }

        $this->editingCheckout = $checkout;
        $this->nome = $checkout->nome;
        $this->descricao = $checkout->descricao;
        $this->hours = $checkout->hours;
        $this->price = (string) $checkout->price;
        $this->checkout_url = $checkout->checkout_url;
        $this->is_active = $checkout->is_active;
        $this->showCheckoutModal = true;
        $this->resetErrorBag();
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
        $this->closeCheckoutModal();
        session()->flash('status', $message);
    }

    public function deleteCheckout(int $checkoutId): void
    {
        $checkout = $this->findCheckout($checkoutId);

        if (! $checkout) {
            return;
        }

        if ($this->editingCheckout?->id === $checkoutId) {
            $this->closeCheckoutModal();
        }

        if ($this->bonusListCheckoutId === $checkoutId) {
            $this->closeBonusListModal();
        }

        $checkout->delete();

        $this->refreshCourse();
        session()->flash('status', 'Checkout excluido.');
    }

    public function closeCheckoutModal(): void
    {
        $this->showCheckoutModal = false;
        $this->resetCheckoutForm();
    }

    public function openBonusListModal(int $checkoutId): void
    {
        $checkout = $this->findCheckout($checkoutId);

        if (! $checkout) {
            return;
        }

        $this->bonusListCheckoutId = $checkout->id;
        $this->showBonusListModal = true;
    }

    public function closeBonusListModal(): void
    {
        $this->showBonusListModal = false;
        $this->bonusListCheckoutId = null;
        $this->closeBonusFormModal();
    }

    public function openCreateBonusModal(?int $checkoutId = null): void
    {
        $targetCheckoutId = $checkoutId ?? $this->bonusListCheckoutId;
        $checkout = $targetCheckoutId ? $this->findCheckout($targetCheckoutId) : null;

        if (! $checkout) {
            return;
        }

        $this->bonusListCheckoutId = $checkout->id;
        $this->showBonusListModal = true;
        $this->resetBonusForm();
        $this->bonusCheckoutId = $checkout->id;
        $this->showBonusFormModal = true;
    }

    public function editBonus(int $bonusId): void
    {
        $bonus = $this->findBonus($bonusId);

        if (! $bonus) {
            return;
        }

        $this->editingBonus = $bonus;
        $this->bonusCheckoutId = $bonus->course_checkout_id;
        $this->bonusListCheckoutId = $bonus->course_checkout_id;
        $this->bonus_nome = $bonus->nome;
        $this->bonus_descricao = $bonus->descricao;
        $this->bonus_preco = (string) $bonus->preco;
        $this->showBonusListModal = true;
        $this->showBonusFormModal = true;
        $this->resetErrorBag();
    }

    public function saveBonus(): void
    {
        $data = $this->validate($this->bonusRules(), $this->bonusMessages());

        $payload = [
            'course_checkout_id' => $data['bonusCheckoutId'],
            'nome' => $data['bonus_nome'],
            'descricao' => $data['bonus_descricao'],
            'preco' => $data['bonus_preco'],
        ];

        if ($this->editingBonus) {
            $this->editingBonus->update($payload);
            $message = 'Bonus atualizado.';
        } else {
            CheckoutBonus::create($payload);
            $message = 'Bonus cadastrado.';
        }

        $this->refreshCourse();
        $this->bonusListCheckoutId = $payload['course_checkout_id'];
        $this->showBonusListModal = true;
        $this->closeBonusFormModal();
        session()->flash('status', $message);
    }

    public function deleteBonus(int $bonusId): void
    {
        $bonus = $this->findBonus($bonusId);

        if (! $bonus) {
            return;
        }

        $checkoutId = $bonus->course_checkout_id;

        $bonus->delete();
        $this->refreshCourse();

        if ($this->editingBonus?->id === $bonusId) {
            $this->closeBonusFormModal();
        }

        if (! $this->findCheckout($checkoutId)) {
            $this->closeBonusListModal();
        } else {
            $this->bonusListCheckoutId = $checkoutId;
            $this->showBonusListModal = true;
        }

        session()->flash('status', 'Bonus excluido.');
    }

    public function closeBonusFormModal(): void
    {
        $this->showBonusFormModal = false;
        $this->resetBonusForm();
    }

    protected function bonusRules(): array
    {
        return [
            'bonusCheckoutId' => [
                'required',
                Rule::exists('course_checkouts', 'id')
                    ->where(fn ($query) => $query->where('course_id', $this->course->id)),
            ],
            'bonus_nome' => ['required', 'string', 'max:255'],
            'bonus_descricao' => ['nullable', 'string'],
            'bonus_preco' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function bonusMessages(): array
    {
        return [
            'bonusCheckoutId.exists' => 'Checkout selecionado invalido.',
        ];
    }

    private function resetCheckoutForm(): void
    {
        $this->editingCheckout = null;
        $this->nome = null;
        $this->descricao = null;
        $this->hours = null;
        $this->price = null;
        $this->checkout_url = null;
        $this->is_active = true;
        $this->resetErrorBag([
            'nome',
            'descricao',
            'hours',
            'price',
            'checkout_url',
            'is_active',
        ]);
    }

    private function resetBonusForm(): void
    {
        $this->editingBonus = null;
        $this->bonusCheckoutId = null;
        $this->bonus_nome = null;
        $this->bonus_descricao = null;
        $this->bonus_preco = null;
        $this->resetErrorBag([
            'bonusCheckoutId',
            'bonus_nome',
            'bonus_descricao',
            'bonus_preco',
        ]);
    }

    private function refreshCourse(): void
    {
        $this->course->refresh()->load([
            'checkouts' => fn ($query) => $query
                ->with(['bonuses' => fn ($bonusQuery) => $bonusQuery->orderBy('id')])
                ->orderBy('hours'),
        ]);
    }

    private function findCheckout(int $checkoutId): ?CourseCheckout
    {
        return $this->course->checkouts->firstWhere('id', $checkoutId);
    }

    private function findBonus(int $bonusId): ?CheckoutBonus
    {
        return $this->course->checkouts
            ->flatMap(fn (CourseCheckout $checkout) => $checkout->bonuses)
            ->firstWhere('id', $bonusId);
    }

    private function canManageCourse(): bool
    {
        $user = Auth::user();

        return $user && $user->isAdmin();
    }
}
