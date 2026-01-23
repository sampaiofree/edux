<section class="space-y-6"
    x-data="{
        filtersOpen: false,
        modalCourse: null,
        openModal(course) { this.modalCourse = course; document.body.classList.add('overflow-hidden'); },
        closeModal() { this.modalCourse = null; document.body.classList.remove('overflow-hidden'); }
    }">
    <div class="rounded-card bg-white p-4 shadow-card text-edux-text">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-edux-primary">Meus cursos</p>
                <h1 class="font-display text-2xl text-edux-primary">Continue de onde parou</h1>
                <p class="mt-1 text-sm text-slate-600">Use o botao de filtros para localizar rapidamente uma matricula.</p>
            </div>
            <button type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-edux-line bg-edux-background px-3 py-2 text-sm font-semibold text-edux-primary"
                @click="filtersOpen = !filtersOpen"
                :aria-expanded="filtersOpen.toString()"
                aria-controls="student-course-filters">
                <span>Filtros</span>
                <svg class="h-4 w-4 transition-transform" :class="filtersOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        @php
            $filtersActive = ($search ?? '') !== '' || ($status ?? 'all') !== 'all';
        @endphp
        @if ($filtersActive)
            <p class="mt-2 text-xs text-slate-500" x-show="!filtersOpen">
                Filtros ativos:
                @if (($search ?? '') !== '')
                    <span class="font-semibold text-edux-primary">Busca</span>
                @endif
                @if (($status ?? 'all') !== 'all')
                    <span class="font-semibold text-edux-primary">Status</span>
                @endif
            </p>
        @endif
        <div class="mt-3 grid gap-3" id="student-course-filters" x-show="filtersOpen" x-transition>
            <label class="text-sm font-semibold text-slate-600">
                <span>Buscar</span>
                <input type="search" wire:model.live.debounce.500ms="search" placeholder="Titulo do curso"
                    class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 focus:border-edux-primary focus:ring-edux-primary/30">
            </label>
            <label class="text-sm font-semibold text-slate-600">
                <span>Status</span>
                <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2.5 focus:border-edux-primary focus:ring-edux-primary/30">
                    <option value="all">Todos</option>
                    <option value="running">Em andamento</option>
                    <option value="completed">Concluidos</option>
                </select>
            </label>
        </div>
    </div>

    <div class="relative"
        x-data="{
            isDown: false,
            startX: 0,
            scrollLeft: 0,
            startDrag(event) {
                this.isDown = true;
                this.startX = (event.pageX || event.touches?.[0].pageX) - this.$refs.scroll.offsetLeft;
                this.scrollLeft = this.$refs.scroll.scrollLeft;
            },
            drag(event) {
                if (!this.isDown) return;
                event.preventDefault();
                const x = (event.pageX || event.touches?.[0].pageX) - this.$refs.scroll.offsetLeft;
                const walk = (x - this.startX) * 1.2;
                this.$refs.scroll.scrollLeft = this.scrollLeft - walk;
            },
            stopDrag() {
                this.isDown = false;
            }
        }"
        x-on:mousemove="drag($event)"
        x-on:mouseup="stopDrag"
        x-on:mouseleave="stopDrag">
        @php
            $defaultCourseCover = \App\Models\SystemSetting::current()->assetUrl('default_course_cover_path');
        @endphp
        <div class="flex gap-5 overflow-x-auto pb-4" x-ref="scroll" x-on:mousedown="startDrag($event)" x-on:touchstart="startDrag($event)"
            x-on:touchmove="drag($event)" x-on:touchend="stopDrag">
            @forelse ($enrollments as $enrollment)
                @php
                    $course = $enrollment->course;
                    $coverUrl = $course->coverImageUrl() ?? $defaultCourseCover;
                    $progress = $enrollment->progress_percent ?? 0;
                    $isCompleted = $progress >= 100;
                @endphp
                <a href="{{ route('learning.courses.show', $course) }}"
                    class="group relative flex min-w-[230px] w-56 flex-col overflow-hidden rounded-card border border-edux-line/70 bg-white text-edux-text shadow-card ring-offset-2 transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-edux-primary/50"
                    wire:key="enrollment-{{ $enrollment->id }}">
                    @if ($coverUrl)
                        <img src="{{ $coverUrl }}" alt="{{ $course->title }}" class="h-32 w-full object-cover transition group-hover:scale-[1.02]">
                    @endif
                    <div class="flex flex-1 flex-col p-4 space-y-2">
                        <div class="space-y-1">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Curso</p>
                            <h2 class="text-lg font-display text-edux-primary leading-tight">{{ $course->title }}</h2>
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
        <p class="mt-2 text-center text-xs text-slate-500">Clique e arraste para navegar horizontalmente.</p>
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
