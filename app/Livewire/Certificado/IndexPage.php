<?php

namespace App\Livewire\Certificado;

use App\Models\Certificate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class IndexPage extends Component
{
    public function render()
    {
        return view('livewire.certificado.index-page', [
            'certificates' => $this->certificates(),
        ]);
    }

    private function certificates()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return Certificate::with('course')
            ->where('user_id', $user->id)
            ->get()
            ->sortBy(fn (Certificate $certificate) => strtolower($certificate->course?->title ?? ''))
            ->values();
    }
}
