<div class="space-y-6">
    <div class="rounded-card bg-white p-4 shadow-card flex flex-wrap items-center gap-4">
        <label class="flex-1 text-sm font-semibold text-slate-600">
            <span class="sr-only">Buscar curso</span>
            <input type="search" wire:model.debounce.500ms="search" placeholder="Buscar curso..."
                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
        </label>
        <label class="text-sm font-semibold text-slate-600">
            <span class="sr-only">Status</span>
            <select wire:model.live="status" class="rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                <option value="all">Todos</option>
                <option value="draft">Rascunho</option>
                <option value="published">Publicado</option>
                <option value="archived">Arquivado</option>
            </select>
        </label>
        <a href="{{ route('courses.create') }}" class="edux-btn">
            + Novo curso
        </a>
        <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="openImportModal">
            Importar curso
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @forelse ($courses as $course)
            <article class="rounded-card bg-white shadow-card overflow-hidden flex flex-col" wire:key="course-{{ $course->id }}">
                @if ($course->coverImageUrl())
                    <img src="{{ $course->coverImageUrl() }}" alt="{{ $course->title }}" class="h-36 w-full object-cover">
                @else
                    <div class="h-36 w-full bg-edux-background flex items-center justify-center text-slate-400 text-sm">Sem imagem</div>
                @endif
                <div class="flex flex-1 flex-col gap-3 p-4">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span class="font-semibold text-edux-primary">{{ Str::limit($course->title, 26) }}</span>
                        <span @class([
                            'inline-flex rounded-full px-3 py-0.5 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $course->status === 'draft',
                            'bg-emerald-100 text-emerald-800' => $course->status === 'published',
                            'bg-slate-200 text-slate-700' => $course->status === 'archived',
                        ])>
                            {{ ucfirst($course->status) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">Responsável: {{ $course->owner->name }}</p>
                    <div class="mt-auto flex gap-2 text-sm">
                        <a href="{{ route('courses.edit', $course) }}" class="edux-btn flex-1 bg-white text-edux-primary">Editar</a>
                        <form method="POST" action="{{ route('courses.destroy', $course) }}" class="flex-1" onsubmit="return confirm('Remover curso?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="edux-btn w-full bg-red-500 text-white">Excluir</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-card bg-white p-6 text-center text-slate-500 shadow-card md:col-span-3">
                Nenhum curso encontrado.
            </div>
        @endforelse
    </div>

    <div>
        {{ $courses->links() }}
    </div>

    @if ($importModalOpen)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 px-4 py-8 sm:py-12">
            <div class="absolute inset-0" wire:click="closeImportModal"></div>
            <div class="relative z-10 w-full max-w-4xl rounded-card bg-white shadow-card">
                <div class="flex items-start justify-between gap-4 border-b border-edux-line px-5 py-4">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-edux-primary">Cursos globais</p>
                        <h2 class="font-display text-2xl text-edux-text">Importar curso</h2>
                        <p class="text-sm text-slate-500">Selecione um curso global para criar uma cópia em rascunho na sua conta.</p>
                    </div>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-edux-primary" wire:click="closeImportModal">
                        Fechar
                    </button>
                </div>

                <div class="border-b border-edux-line px-5 py-4">
                    <label class="block text-sm font-semibold text-slate-600">
                        <span class="sr-only">Buscar curso global</span>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="importSearch"
                            placeholder="Buscar por título ou slug..."
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                    </label>
                </div>

                <div class="max-h-[70vh] overflow-y-auto px-5 py-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        @forelse ($globalCourses as $globalCourse)
                            <article class="rounded-2xl border border-edux-line bg-slate-50 p-4" wire:key="global-course-{{ $globalCourse->id }}">
                                <div class="space-y-3">
                                    <div class="space-y-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <h3 class="font-display text-lg text-edux-text">{{ $globalCourse->title }}</h3>
                                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                Global
                                            </span>
                                        </div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            {{ $globalCourse->systemSetting?->escola_nome ?: ($globalCourse->systemSetting?->domain ?: 'Tenant sem nome') }}
                                        </p>
                                        <p class="text-xs text-slate-500">{{ $globalCourse->slug }}</p>
                                    </div>

                                    <p class="text-sm text-slate-600">
                                        {{ Str::limit($globalCourse->summary ?: 'Curso sem resumo disponível.', 160) }}
                                    </p>

                                    <div class="flex items-center justify-between gap-4 text-xs text-slate-500">
                                        <span>Responsável: {{ $globalCourse->owner?->name ?: 'Sem responsável' }}</span>
                                        <button
                                            type="button"
                                            class="edux-btn px-4 py-2 text-sm"
                                            wire:click="importCourse({{ $globalCourse->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="importCourse"
                                        >
                                            Importar
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-card border border-dashed border-edux-line bg-slate-50 px-6 py-10 text-center text-sm text-slate-500 md:col-span-2">
                                Nenhum curso global encontrado para os filtros informados.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
