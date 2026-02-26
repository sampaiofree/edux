<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-edux-line/60 p-4 shadow-sm">
            <p class="text-xs uppercase text-slate-500">Modulos publicados</p>
            <p class="mt-2 text-3xl font-display text-edux-primary">{{ $metrics['modules'] }}</p>
            <p class="text-xs text-slate-500">Defina titulos claros e use a ordem para conduzir o aluno.</p>
        </article>
        <article class="rounded-2xl border border-edux-line/60 p-4 shadow-sm">
            <p class="text-xs uppercase text-slate-500">Aulas cadastradas</p>
            <p class="mt-2 text-3xl font-display text-edux-primary">{{ $metrics['lessons'] }}</p>
            <p class="text-xs text-slate-500">Apos criar um modulo, adicione aulas para destrinchar o conteudo.</p>
        </article>
        <article class="rounded-2xl border border-edux-line/60 p-4 shadow-sm">
            <p class="text-xs uppercase text-slate-500">Duracao total</p>
            <p class="mt-2 text-3xl font-display text-edux-primary">
                {{ $metrics['duration'] ? $metrics['duration'].' min' : 'Sem estimativa' }}
            </p>
            <p class="text-xs text-slate-500">Estimativa baseada na duracao informada em cada aula.</p>
        </article>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm uppercase tracking-wide text-edux-primary">Conteudo</p>
            <h2 class="text-2xl font-display text-edux-primary">Modulos do curso</h2>
            <p class="text-xs text-slate-500">Reordene sempre que precisar e mantenha tudo alinhado por aqui.</p>
        </div>
        <button type="button" class="edux-btn" wire:click="newModule">
            {{ $editingModule ? 'Editar modulo' : 'Novo modulo' }}
        </button>
    </div>

    <div class="space-y-4">
        @forelse ($modules as $module)
            <article
                class="rounded-2xl border border-edux-line/70 p-5"
                wire:key="module-{{ $module->id }}"
                x-data="{ open: false }"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex flex-col text-sm text-slate-500">
                            <button
                                type="button"
                                class="rounded-full bg-edux-background px-2 py-1 hover:text-edux-primary disabled:opacity-40"
                                wire:click="moveModule({{ $module->id }}, 'up')"
                                wire:loading.attr="disabled"
                                wire:target="moveModule"
                                aria-label="Mover modulo para cima"
                            >
                                &uarr;
                            </button>
                            <button
                                type="button"
                                class="mt-1 rounded-full bg-edux-background px-2 py-1 hover:text-edux-primary disabled:opacity-40"
                                wire:click="moveModule({{ $module->id }}, 'down')"
                                wire:loading.attr="disabled"
                                wire:target="moveModule"
                                aria-label="Mover modulo para baixo"
                            >
                                &darr;
                            </button>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500">Modulo {{ $module->position }}</p>
                            <h3 class="text-xl font-semibold text-edux-primary">{{ $module->title }}</h3>
                            <p class="text-sm text-slate-600">
                                {{ $module->description ?? 'Sem descricao cadastrada.' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $module->lessons->count() }} aulas registradas
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3 text-sm font-semibold text-edux-primary">
                        <button type="button" wire:click="editModule({{ $module->id }})" class="underline-offset-2 hover:underline">
                            Editar
                        </button>
                        <button
                            type="button"
                            class="text-red-500 underline-offset-2 hover:underline"
                            onclick="if(!confirm('Remover modulo e aulas relacionadas?')) { event.stopImmediatePropagation(); return false; }"
                            wire:click="deleteModule({{ $module->id }})"
                        >
                            Excluir
                        </button>
                        <button type="button" class="underline-offset-2 hover:underline" @click="open = !open">
                            <span x-text="open ? 'Fechar aulas' : 'Gerenciar aulas'"></span>
                        </button>
                    </div>
                </div>

                <div class="mt-4" x-show="open" x-collapse>
                    <livewire:admin.lessons-manager
                        :module-id="$module->id"
                        :key="'lessons-'.$module->id.'-'.$lessonsComponentsVersion"
                    />
                </div>
            </article>
        @empty
            <p class="text-sm text-slate-500">Nenhum modulo cadastrado. Clique em novo modulo para comecar.</p>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="absolute inset-0" wire:click="closeForm"></div>
            <div class="relative z-10 w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-edux-primary">Formulario</p>
                        <h3 class="text-2xl font-display text-edux-primary">
                            {{ $editingModule ? 'Editar modulo' : 'Novo modulo' }}
                        </h3>
                    </div>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-edux-primary" wire:click="closeForm">
                        Fechar
                    </button>
                </div>
                <form wire:submit.prevent="saveModule" class="mt-6 space-y-4">
                    <label class="space-y-2 text-sm font-semibold text-slate-600">
                        <span>Titulo</span>
                        <input
                            type="text"
                            wire:model.defer="form.title"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            required
                        >
                        @error('form.title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-2 text-sm font-semibold text-slate-600">
                        <span>Descricao</span>
                        <textarea
                            rows="3"
                            wire:model.defer="form.description"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                        ></textarea>
                        @error('form.description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
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
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="edux-btn">
                            {{ $editingModule ? 'Salvar modulo' : 'Adicionar modulo' }}
                        </button>
                        <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="closeForm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
