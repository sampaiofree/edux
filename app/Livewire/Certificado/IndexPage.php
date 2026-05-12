<?php

namespace App\Livewire\Certificado;

use App\Models\Enrollment;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class IndexPage extends Component
{
    public function render()
    {
        return view('livewire.certificado.index-page', [
            'enrollments' => $this->enrollments(),
            'settings' => SystemSetting::current(),
        ]);
    }

    private function enrollments()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return Enrollment::with([
            'course' => fn ($query) => $query->with([
                'certificates' => fn ($certificateQuery) => $certificateQuery->where('user_id', $user->id),
            ]),
        ])
            ->where('user_id', $user->id)
            ->accessible()
            ->get()
            ->filter(fn (Enrollment $enrollment) => $enrollment->course !== null)
            ->sortBy(fn (Enrollment $enrollment) => strtolower($enrollment->course?->title ?? ''))
            ->values();
    }
}
