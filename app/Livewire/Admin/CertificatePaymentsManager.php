<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\CertificatePayment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CertificatePaymentsManager extends Component
{
    use WithPagination;

    public ?int $user_id = null;

    public ?int $course_id = null;

    public float $amount = 0;

    public string $currency = 'BRL';

    public string $status = 'paid';

    public ?string $transaction_reference = null;

    public ?string $paid_at = null;

    public function save(): void
    {
        $data = $this->validate($this->rules());

        CertificatePayment::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'course_id' => $data['course_id'],
            ],
            [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => $data['status'],
                'transaction_reference' => $data['transaction_reference'],
                'paid_at' => $data['status'] === 'paid'
                    ? ($data['paid_at'] ?? now())
                    : null,
            ]
        );

        $this->reset(['user_id', 'course_id', 'amount', 'transaction_reference', 'paid_at']);
        $this->amount = 0;
        session()->flash('status', 'Pagamento registrado.');
    }

    public function render()
    {
        return view('livewire.admin.certificate-payments-manager', [
            'students' => User::where('role', UserRole::STUDENT)->orderBy('name')->get(),
            'courses' => Course::orderBy('title')->get(),
            'payments' => CertificatePayment::query()
                ->whereHas('course')
                ->with(['user', 'course'])
                ->latest()
                ->paginate(10),
        ]);
    }

    protected function rules(): array
    {
        $systemSettingId = auth()->user()?->system_setting_id;

        return [
            'user_id' => ['required', Rule::exists('users', 'id')->where('system_setting_id', $systemSettingId)],
            'course_id' => ['required', Rule::exists('courses', 'id')->where('system_setting_id', $systemSettingId)],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:5'],
            'status' => ['required', 'in:pending,paid,failed'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
