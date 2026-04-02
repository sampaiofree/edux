<section class="space-y-6"
    x-data="{
        modalCourse: null,
        openModal(course) { this.modalCourse = course; document.body.classList.add('overflow-hidden'); },
        closeModal() { this.modalCourse = null; document.body.classList.remove('overflow-hidden'); }
    }">
    <p class="text-sm text-slate-600">Clique no seu curso abaixo para assistir as aulas.</p>

    <div class="space-y-4">
        @php
            $defaultCourseCover = \App\Models\SystemSetting::current()->assetUrl('default_course_cover_path');
        @endphp
        @forelse ($enrollments as $enrollment)
            @php
                $course = $enrollment->course;
                $coverUrl = $course->coverImageUrl() ?? $defaultCourseCover;
                $progress = $enrollment->progress_percent ?? 0;
                $isCompleted = $progress >= 100;
            @endphp
            <a href="{{ route('learning.courses.show', $course) }}"
                wire:navigate
                class="group relative flex w-full flex-col overflow-hidden rounded-card border border-edux-line/70 bg-white text-edux-text shadow-card ring-offset-2 transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-edux-primary/50"
                wire:key="enrollment-{{ $enrollment->id }}">
                @if ($coverUrl)
                    <img src="{{ $coverUrl }}" alt="{{ $course->title }}" class="h-32 w-full object-cover transition group-hover:scale-[1.02]">
                @endif
                <div class="flex flex-1 flex-col space-y-2 p-4">
                    <div class="space-y-1">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Curso</p>
                        <h2 class="text-lg leading-tight font-display text-edux-primary">{{ $course->title }}</h2>
                    </div>
                    <div class="mt-auto space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span>{{ $isCompleted ? 'Curso concluido' : 'Continue assistindo' }}</span>
                            <span class="font-semibold text-edux-primary">{{ $progress }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-edux-background">
                            <div class="h-full rounded-full bg-edux-primary transition-all" style="width: {{ $progress }}%;"></div>
                        </div>
                        <div class="flex items-center justify-between text-sm font-semibold text-edux-primary">
                            <span>Assistir aulas</span>
                            <span aria-hidden="true">&rarr;</span>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-card bg-white p-6 text-center text-slate-500 shadow-card">
                Voce ainda nao possui matriculas ativas.
            </div>
        @endforelse
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
        x-show="modalCourse"
        x-transition>
        <article class="w-full max-w-lg rounded-card bg-white p-6 shadow-card" @click.away="closeModal">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-edux-primary">Resumo do curso</p>
                    <h3 class="font-display text-2xl text-edux-primary" x-text="modalCourse?.title"></h3>
                </div>
                <button class="text-slate-500" @click="closeModal">&times;</button>
            </div>
            <p class="mt-4 text-sm text-slate-600" x-text="modalCourse?.summary || 'Sem resumo disponivel.'"></p>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-edux-background p-3 text-center">
                    <dt class="text-xs uppercase text-slate-500">Aulas</dt>
                    <dd class="text-xl font-display text-edux-primary" x-text="modalCourse?.lessons ?? '-'"></dd>
                </div>
                <div class="rounded-xl bg-edux-background p-3 text-center">
                    <dt class="text-xs uppercase text-slate-500">Progresso</dt>
                    <dd class="text-xl font-display text-edux-primary" x-text="modalCourse?.progress ? modalCourse.progress + '%' : '-'"></dd>
                </div>
            </dl>
            <button class="edux-btn mt-6 w-full" @click="closeModal">Fechar</button>
        </article>
    </div>
</section>
