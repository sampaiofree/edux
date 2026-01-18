<?php

namespace App\Livewire;

use App\Models\Course;
use Livewire\Component;

class PublicCatalog extends Component
{
    public string $search = '';
    public int $perPage = 9;

    public function updatedSearch(): void
    {
        $this->perPage = 9;
    }

    public function loadMore(): void
    {
        $this->perPage += 6;
    }

    public function render()
    {
        $query = Course::query()
            ->where('status', 'published')
            ->when($this->search !== '', function ($query) {
                $search = $this->search;

                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('created_at', 'desc');

        $total = (clone $query)->count();
        $courses = $query->take($this->perPage)->get();

        return view('livewire.public-catalog', [
            'courses' => $courses,
            'hasMore' => $courses->count() < $total,
        ]);
    }
}
