<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\SystemSetting;
use Illuminate\Support\Str;
use Livewire\Component;

class PublicCatalog extends Component
{
    public string $context = 'catalog';
    public string $search = '';
    public int $perPage = 9;

    public function mount(string $context = 'catalog'): void
    {
        $this->context = $context === 'home' ? 'home' : 'catalog';
        $this->perPage = $this->context === 'home' ? 6 : 9;
    }

    public function updatedSearch(): void
    {
        $this->perPage = $this->context === 'home' ? 6 : 9;
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
                        ->orWhere('summary', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            });

        if ($this->context === 'home') {
            $query
                ->withMin([
                    'checkouts as active_checkout_min_price' => fn ($query) => $query->where('is_active', true),
                ], 'price')
                ->orderByRaw('case when active_checkout_min_price is null then 1 else 0 end')
                ->orderBy('active_checkout_min_price')
                ->orderBy('title');
        } else {
            $query
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');
        }

        $total = (clone $query)->count();
        $courses = $query->take($this->perPage)->get();
        $defaultCourseCover = SystemSetting::current()->assetUrl('default_course_cover_path');
        $courseCards = $courses->map(function (Course $course) use ($defaultCourseCover): array {
            $summary = $course->summary ?: Str::limit(strip_tags((string) $course->description), 150);
            $durationHoursLabel = $course->duration_minutes
                ? rtrim(rtrim(number_format($course->duration_minutes / 60, 1, ',', '.'), '0'), ',')
                : null;

            return [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
                'summary' => $summary,
                'headline' => Str::limit($summary, 135),
                'cover_url' => $course->coverImageUrl() ?? $defaultCourseCover,
                'duration_label' => $durationHoursLabel,
                'course_url' => route('courses.public.show', $course),
            ];
        })->values();

        return view('livewire.public-catalog', [
            'courses' => $courseCards,
            'context' => $this->context,
            'hasMore' => $courseCards->count() < $total,
        ]);
    }
}
