<section class="space-y-6">
    <div class="rounded-card bg-white p-6 shadow-card space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Comunicacao</p>
                <h1 class="text-2xl font-display text-edux-primary">Notificacoes publicadas</h1>
                <p class="text-xs text-slate-500">Cadastre comunicados para toda a base de alunos.</p>
            </div>
            <button type="button" class="edux-btn" wire:click="newNotification">
                + Nova notificacao
            </button>
        </div>

        <div class="space-y-3">
            @forelse ($notifications as $notification)
                <article class="rounded-2xl border border-edux-line/70 p-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500">{{ optional($notification->published_at)->format('d/m/Y H:i') ?? 'Rascunho' }}</p>
                        <h3 class="text-lg font-semibold text-edux-primary">{{ $notification->title }}</h3>
                    </div>
                    <div class="flex gap-2 text-sm font-semibold">
                        <button type="button" wire:click="edit({{ $notification->id }})" class="edux-btn bg-white text-edux-primary">Editar</button>
                        <button type="button" onclick="if(!confirm('Remover notificacao?')) return;" wire:click="delete({{ $notification->id }})" class="edux-btn bg-red-500 text-white">Excluir</button>
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-500">Nenhuma notificacao cadastrada.</p>
            @endforelse
        </div>

        {{ $notifications->links() }}
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="absolute inset-0" wire:click="closeModal"></div>
            <div class="relative z-10 w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-edux-primary">Formulario</p>
                        <h2 class="text-2xl font-display text-edux-primary">
                            {{ $editing ? 'Editar notificacao' : 'Nova notificacao' }}
                        </h2>
                    </div>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-edux-primary" wire:click="closeModal">
                        Fechar
                    </button>
                </div>

                <form wire:submit.prevent="save" class="mt-6 grid gap-4 md:grid-cols-2">
                    <label class="space-y-1 text-sm font-semibold text-slate-600">
                        <span>Titulo</span>
                        <input type="text" wire:model.defer="title" class="w-full rounded-xl border border-edux-line px-4 py-3">
                        @error('title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 text-sm font-semibold text-slate-600">
                        <span>Publicar em</span>
                        <input type="datetime-local" wire:model.defer="published_at" class="w-full rounded-xl border border-edux-line px-4 py-3">
                        @error('published_at') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <label class="md:col-span-2 space-y-1 text-sm font-semibold text-slate-600">
                        <span>Mensagem</span>
                        <textarea wire:model.defer="body" rows="4" class="w-full rounded-xl border border-edux-line px-4 py-3"></textarea>
                        @error('body') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <label class="space-y-1 text-sm font-semibold text-slate-600">
                        <span>Imagem</span>
                        <input type="file" wire:model="image" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3">
                        @error('image') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" class="mt-2 rounded-xl border border-edux-line object-cover">
                        @elseif ($editing?->image_path)
                            <img src="{{ asset('storage/'.$editing->image_path) }}" class="mt-2 rounded-xl border border-edux-line object-cover">
                        @endif
                    </label>

                    <label class="space-y-1 text-sm font-semibold text-slate-600">
                        <span>Video (URL)</span>
                        <input type="url" wire:model.defer="video_url" class="w-full rounded-xl border border-edux-line px-4 py-3">
                        @error('video_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <label class="space-y-1 text-sm font-semibold text-slate-600">
                        <span>Texto do botao</span>
                        <input type="text" wire:model.defer="button_label" class="w-full rounded-xl border border-edux-line px-4 py-3">
                        @error('button_label') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1 text-sm font-semibold text-slate-600">
                        <span>Link do botao</span>
                        <input type="url" wire:model.defer="button_url" class="w-full rounded-xl border border-edux-line px-4 py-3">
                        @error('button_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <div class="md:col-span-2 flex flex-wrap gap-3">
                        <button type="submit" class="edux-btn">
                            {{ $editing ? 'Salvar alteracoes' : 'Adicionar notificacao' }}
                        </button>
                        <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="closeModal">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</section>
