<section class="rounded-2xl border border-edux-line/60 bg-edux-background/30 p-5 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm uppercase tracking-wide text-edux-primary">Aulas</p>
            <p class="text-xs text-slate-500">{{ $lessons->count() }} aulas cadastradas neste modulo.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="openImport">
                Importar aulas
            </button>
            <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="newLesson">
                {{ $editingLesson ? 'Editar aula' : 'Nova aula' }}
            </button>
        </div>
    </div>

    @if ($showImport)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 px-4 py-8 overflow-auto">
            <div class="absolute inset-0" wire:click="closeImport"></div>
            <div class="relative z-10 w-full max-w-4xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-edux-line/60 pb-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-edux-primary">Importar aulas</p>
                        <h3 class="text-2xl font-display text-edux-primary">CSV por arquivo ou texto</h3>
                        <p class="text-xs text-slate-500">Mapeie os campos e escolha o modulo alvo.</p>
                    </div>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-edux-primary" wire:click="closeImport">
                        Fechar
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-3">
                            <div class="flex gap-2 text-sm font-semibold text-slate-600">
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" value="file" wire:model.live="importSource" class="text-edux-primary">
                                    <span>Enviar arquivo</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" value="text" wire:model.live="importSource" class="text-edux-primary">
                                    <span>Colar texto</span>
                                </label>
                            </div>

                            @if ($importSource === 'file')
                                <div class="space-y-2">
                                    <input type="file" wire:model.live="csvUpload" accept=".csv,text/csv,text/plain" class="w-full text-sm">
                                    @error('csvUpload') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            @else
                                <div class="space-y-2">
                                    <textarea
                                        rows="6"
                                        wire:model.live="csvText"
                                        class="w-full rounded-xl border border-edux-line px-3 py-2 text-sm focus:border-edux-primary focus:ring-edux-primary/30"
                                        placeholder="Cole aqui o CSV"
                                    ></textarea>
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-3 text-sm text-slate-600">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" wire:model.live="hasHeader" class="text-edux-primary">
                                    <span>Arquivo tem cabeçalho</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <span>Separador</span>
                                    <select wire:model.live="separator" class="rounded-lg border border-edux-line px-2 py-1 text-sm focus:border-edux-primary focus:ring-edux-primary/30">
                                        <option value="auto">Auto</option>
                                        <option value=",">Virgula</option>
                                        <option value=";">Ponto e virgula</option>
                                    </select>
                                </label>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-600 block">
                                    <span>Aplicar modulo para todas as aulas</span>
                                    <select
                                        wire:model.live="moduleOverride"
                                        class="mt-1 w-full rounded-xl border border-edux-line px-3 py-2 text-sm focus:border-edux-primary focus:ring-edux-primary/30"
                                    >
                                        @foreach ($modulesList as $mod)
                                            <option value="{{ $mod->id }}">
                                                {{ $mod->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            <div class="flex gap-3">
                                <button type="button" class="edux-btn" wire:click="analyzeCsv">
                                    Analisar CSV
                                </button>
                                <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="importLessons">
                                    Importar
                                </button>
                            </div>

                            @error('csv') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            @error('mapping') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            @error('moduleOverride') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-3">
                            <p class="text-sm uppercase tracking-wide text-edux-primary">Mapeamento de colunas</p>
                            @if (empty($columns))
                                <p class="text-sm text-slate-500">Analise o CSV para configurar o mapeamento.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($columns as $idx => $name)
                                        <div class="flex items-center gap-3 text-sm">
                                            <div class="w-1/2 truncate font-semibold text-slate-700" title="{{ $name }}">
                                                {{ $name }}
                                            </div>
                                            <select
                                                wire:model.live="mapping.{{ $idx }}"
                                                class="w-1/2 rounded-lg border border-edux-line px-2 py-1 text-sm focus:border-edux-primary focus:ring-edux-primary/30"
                                            >
                                                <option value="">Ignorar</option>
                                                <option value="title">Titulo (obrigatorio)</option>
                                                <option value="content">Conteudo</option>
                                                <option value="video_url">Video URL</option>
                                                <option value="duration_minutes">Duracao (min)</option>
                                                <option value="position">Posicao</option>
                                                <option value="module">Modulo (nome)</option>
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm uppercase tracking-wide text-edux-primary">Pré-visualização</p>
                        @if (empty($previewRows))
                            <p class="text-sm text-slate-500">Sem prévia. Analise o CSV para visualizar.</p>
                        @else
                            <div class="overflow-auto rounded-xl border border-edux-line">
                                <table class="min-w-full text-sm text-slate-700">
                                    <thead class="bg-edux-background">
                                        <tr>
                                            @foreach ($columns as $name)
                                                <th class="px-3 py-2 text-left font-semibold">{{ $name }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($previewRows as $row)
                                            <tr class="border-t border-edux-line/60">
                                                @foreach ($columns as $idx => $name)
                                                    <td class="px-3 py-2">{{ $row[$idx] ?? '' }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($lessons as $lesson)
            <article class="rounded-2xl border border-white/40 bg-white p-4" wire:key="lesson-{{ $lesson->id }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex flex-col text-sm text-slate-500">
                            <button
                                type="button"
                                class="rounded-full bg-edux-background px-2 py-1 hover:text-edux-primary disabled:opacity-40"
                                wire:click="moveLesson({{ $lesson->id }}, 'up')"
                                wire:loading.attr="disabled"
                                wire:target="moveLesson"
                                aria-label="Mover aula para cima"
                            >
                                &uarr;
                            </button>
                            <button
                                type="button"
                                class="mt-1 rounded-full bg-edux-background px-2 py-1 hover:text-edux-primary disabled:opacity-40"
                                wire:click="moveLesson({{ $lesson->id }}, 'down')"
                                wire:loading.attr="disabled"
                                wire:target="moveLesson"
                                aria-label="Mover aula para baixo"
                            >
                                &darr;
                            </button>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500">Aula {{ $lesson->position }}</p>
                            <h4 class="text-lg font-semibold text-edux-primary">{{ $lesson->title }}</h4>
                            <p class="text-xs text-slate-500">
                                {{ $lesson->duration_minutes ? $lesson->duration_minutes.' min' : 'Sem duracao' }}
                                @if ($lesson->video_url)
                                    | Video anexado
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 text-sm font-semibold text-edux-primary">
                        <button type="button" wire:click="editLesson({{ $lesson->id }})" class="underline-offset-2 hover:underline">
                            Editar
                        </button>
                        <button
                            type="button"
                            class="text-red-500 underline-offset-2 hover:underline"
                            onclick="if(!confirm('Excluir esta aula?')) { event.stopImmediatePropagation(); return false; }"
                            wire:click="deleteLesson({{ $lesson->id }})"
                        >
                            Excluir
                        </button>
                    </div>
                </div>
                @php
                    $excerpt = \Illuminate\Support\Str::limit(strip_tags($lesson->content ?? ''), 160);
                @endphp
                @if ($excerpt)
                    <p class="mt-2 text-sm text-slate-600">{{ $excerpt }}</p>
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-500">Nenhuma aula cadastrada neste modulo.</p>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="absolute inset-0" wire:click="closeForm"></div>
            <div class="relative z-10 w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-edux-primary">Formulario</p>
                        <h3 class="text-2xl font-display text-edux-primary">
                            {{ $editingLesson ? 'Editar aula' : 'Nova aula' }}
                        </h3>
                    </div>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-edux-primary" wire:click="closeForm">
                        Fechar
                    </button>
                </div>
                <form wire:submit.prevent="saveLesson" class="mt-6 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm font-semibold text-slate-600 md:col-span-2">
                        <span>Titulo</span>
                        <input
                            type="text"
                            wire:model.defer="form.title"
                            required
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                        @error('form.title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-2 text-sm font-semibold text-slate-600">
                        <span>URL do video</span>
                        <input
                            type="url"
                            wire:model.defer="form.video_url"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                        @error('form.video_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-2 text-sm font-semibold text-slate-600">
                        <span>Duracao (min)</span>
                        <input
                            type="number"
                            min="1"
                            wire:model.defer="form.duration_minutes"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                        @error('form.duration_minutes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-2 text-sm font-semibold text-slate-600">
                        <span>Modulo da aula</span>
                        <select
                            wire:model.defer="form.module_id"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                            @foreach ($modulesList as $mod)
                                <option value="{{ $mod->id }}">
                                    Módulo {{ $mod->position }} · {{ $mod->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('form.module_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-2 text-sm font-semibold text-slate-600 md:col-span-2">
                        <span>Conteudo textual</span>
                        <textarea
                            rows="4"
                            wire:model.defer="form.content"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        ></textarea>
                        @error('form.content') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-2 text-sm font-semibold text-slate-600">
                        <span>Posicao</span>
                        <input
                            type="number"
                            min="1"
                            wire:model.defer="form.position"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        >
                        @error('form.position') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <div class="md:col-span-2 flex flex-wrap gap-3">
                        <button type="submit" class="edux-btn">
                            {{ $editingLesson ? 'Salvar aula' : 'Adicionar aula' }}
                        </button>
                        <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="closeForm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</section>
