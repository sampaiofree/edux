<section class="space-y-4">
    @if ($context === 'catalog')
        <div class="rounded-card bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Catalogo</p>
                    <h2 class="text-2xl font-display text-edux-primary">Cursos disponiveis</h2>
                </div>
                <input
                    type="search"
                    wire:model.debounce.400ms="search"
                    placeholder="Buscar curso"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 text-sm md:w-64 focus:border-edux-primary focus:ring-edux-primary/30"
                >
            </div>
        </div>
    @endif

    @if ($context === 'home')
        <div class="w3-courses-grid">
            @forelse ($courses as $course)
                <article class="w3-course-item" data-public-course-card>
                    <a href="{{ $course['course_url'] }}" class="w3-course-card">
                        <div class="w3-course-card__media">
                            @if ($course['cover_url'])
                                <img src="{{ $course['cover_url'] }}" alt="{{ $course['title'] }}">
                            @else
                                <div class="w3-course-card__media-placeholder">Curso</div>
                            @endif
                        </div>

                        <div class="w3-course-card__body">
                            <h3 class="w3-course-card__title">{{ $course['title'] }}</h3>
                            <p class="w3-course-card__headline">{{ $course['headline'] }}</p>
                            <p class="w3-course-card__meta">
                                @if ($course['duration_label'])
                                    {{ $course['duration_label'] }}h de conteudo online
                                @else
                                    Duracao informada na pagina publica do curso
                                @endif
                            </p>
                            <p class="w3-course-card__meta">Pagina publica com informacoes do curso</p>
                            <span class="w3-course-card__cta">Saiba mais</span>
                        </div>
                    </a>
                </article>
            @empty
                <div class="w3-empty-courses">Nenhum curso encontrado.</div>
            @endforelse
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($courses as $course)
                <article data-public-course-card class="overflow-hidden rounded-card bg-white shadow-card">
                    @if ($course['cover_url'])
                        <img src="{{ $course['cover_url'] }}" alt="{{ $course['title'] }}" class="h-40 w-full object-cover">
                    @endif

                    <div class="flex flex-col gap-4 p-5">
                        <div class="space-y-2">
                            <h3 class="font-display text-xl text-slate-950">{{ $course['title'] }}</h3>
                            <p class="text-sm leading-6 text-slate-600">{{ $course['headline'] }}</p>
                        </div>

                        <div class="text-xs uppercase tracking-wide text-slate-500">
                            @if ($course['duration_label'])
                                Duracao: {{ $course['duration_label'] }}h
                            @else
                                Duracao: nao informada
                            @endif
                        </div>

                        <div class="mt-auto flex flex-wrap gap-2">
                            <a href="{{ $course['course_url'] }}" class="edux-btn flex-1">
                                Ver curso
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-card bg-white p-6 text-slate-500 shadow-card">Nenhum curso encontrado.</div>
            @endforelse
        </div>
    @endif

    @if ($hasMore)
        <div class="{{ $context === 'home' ? 'w3-load-more-wrap' : 'flex justify-center' }}">
            <button
                type="button"
                wire:click="loadMore"
                wire:loading.attr="disabled"
                class="{{ $context === 'home' ? 'w3-btn w3-btn--primary w3-load-more' : 'edux-btn' }}"
            >
                Carregar mais
            </button>
        </div>
    @endif
</section>
