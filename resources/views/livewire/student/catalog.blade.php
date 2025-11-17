<section class="space-y-4">
    <div class="rounded-card bg-white p-5 shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">+Cursos</p>
                <h2 class="text-2xl font-display text-edux-primary">Cursos disponíveis</h2>
            </div>
            <input type="search" wire:model.debounce.400ms="search" placeholder="Buscar curso"
                class="w-full rounded-xl border border-edux-line px-4 py-3 text-sm md:w-64 focus:border-edux-primary focus:ring-edux-primary/30">
        </div>
    </div>

    @php
        $defaultCourseCover = \App\Models\SystemSetting::current()->assetUrl('default_course_cover_path');
    @endphp
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($courses as $course)
            <article class="rounded-card bg-white shadow-card overflow-hidden flex flex-col">
                @php
                    $coverUrl = $course->coverImageUrl() ?? $defaultCourseCover;
                @endphp
                @if ($coverUrl)
                    <img src="{{ $coverUrl }}" alt="{{ $course->title }}" class="h-40 w-full object-cover">
                @endif
                <div class="flex flex-1 flex-col gap-3 p-5">
                    <div>
                        <h3 class="font-display text-xl text-edux-primary">{{ $course->title }}</h3>
                        <p class="text-sm text-slate-600">{{ Str::limit($course->summary, 120) }}</p>
                    </div>
                    <p class="text-xs uppercase text-slate-500">{{ $course->enrollments_count }} alunos matriculados</p>
                    <div class="mt-auto flex flex-wrap gap-2">
                        <a href="{{ route('courses.public.show', $course) }}" class="edux-btn flex-1">Saiba mais</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-card bg-white p-6 shadow-card text-slate-500">Nenhum curso encontrado.</div>
        @endforelse
    </div>
</section>
