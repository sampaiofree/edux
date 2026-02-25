<section class="edux-card space-y-5 mt-6" wire:keydown.enter.prevent>
    <form wire:submit.prevent="save" class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Título</span>
                <input type="text" wire:model.defer="title" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @error('title') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Status</span>
                <select wire:model.defer="status" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="space-y-2 text-sm font-semibold text-slate-600">
            <span>Resumo curto</span>
            <input type="text" wire:model.defer="summary" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            @error('summary') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
        </label>

        <label class="space-y-2 text-sm font-semibold text-slate-600">
            <span>Descrição completa</span>
            <textarea wire:model.defer="description" rows="4" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"></textarea>
            @error('description') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
        </label>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Onde pode atuar (lista)</span>
                <textarea wire:model.defer="atuacao" rows="4" placeholder="Ex.: Escritórios; Empresas de comércio; Recepção; Apoio administrativo" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"></textarea>
                <p class="text-xs font-medium text-slate-500">Separe os itens com <span class="font-semibold">;</span> (ponto e vírgula).</p>
                @error('atuacao') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>O que faz na prática (lista)</span>
                <textarea wire:model.defer="oquefaz" rows="4" placeholder="Ex.: Organiza documentos; Atende clientes; Preenche planilhas; Dá apoio à equipe" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"></textarea>
                <p class="text-xs font-medium text-slate-500">Separe os itens com <span class="font-semibold">;</span> (ponto e vírgula).</p>
                @error('oquefaz') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Imagem de destaque</span>
                <input type="file" wire:model="cover_image" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3">
                @error('cover_image') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                @if ($cover_image)
                    <img src="{{ $cover_image->temporaryUrl() }}" alt="Prévia do curso" class="mt-2 rounded-xl border border-edux-line object-cover">
                @elseif ($course && $course->coverImageUrl())
                    <div class="relative mt-2">
                        <img src="{{ $course->coverImageUrl() }}" alt="Imagem atual" class="rounded-xl border border-edux-line object-cover">
                        <button type="button" wire:click="deleteCoverImage" class="absolute right-2 top-2 rounded-full bg-red-500 px-3 py-1 text-xs font-semibold text-white">Remover</button>
                    </div>
                @endif
            </label>
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Vídeo promocional (URL)</span>
                <input type="url" wire:model.defer="promo_video_url" placeholder="https://youtu.be/..." class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @error('promo_video_url') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Duração (minutos)</span>
                <input type="number" min="1" wire:model.defer="duration_minutes" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @error('duration_minutes') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Data de publicação</span>
                <input type="datetime-local" wire:model.defer="published_at" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @error('published_at') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </label>
            @if ($this->is_admin)
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Responsável</span>
                    <select wire:model.defer="owner_id" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}">{{ $owner->name }} ({{ $owner->role->label() }})</option>
                        @endforeach
                    </select>
                    @error('owner_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </label>
            @endif
        </div>

        @if ($this->is_admin)
            <div class="rounded-2xl border border-dashed border-edux-line p-4 space-y-4">
                <p class="font-semibold text-slate-700">Certificado · fundos personalizados</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm font-semibold text-slate-600">
                        <span>Fundo da frente</span>
                        <input type="file" wire:model="certificate_front_background" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3">
                        @error('certificate_front_background') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        @if ($branding?->front_background_url)
                            <div class="relative">
                                <img src="{{ $branding->front_background_url }}" alt="Frente atual" class="mt-2 rounded-xl border border-edux-line">
                                <button type="button" wire:click="deleteFrontBackground" class="absolute right-2 top-2 rounded-full bg-red-500 px-3 py-1 text-xs font-semibold text-white">Remover</button>
                            </div>
                        @endif
                    </label>
                    <label class="space-y-2 text-sm font-semibold text-slate-600">
                        <span>Fundo do verso</span>
                        <input type="file" wire:model="certificate_back_background" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3">
                        @error('certificate_back_background') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        @if ($branding?->back_background_url)
                            <div class="relative">
                                <img src="{{ $branding->back_background_url }}" alt="Verso atual" class="mt-2 rounded-xl border border-edux-line">
                                <button type="button" wire:click="deleteBackBackground" class="absolute right-2 top-2 rounded-full bg-red-500 px-3 py-1 text-xs font-semibold text-white">Remover</button>
                            </div>
                        @endif
                    </label>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="edux-btn">
                💾 {{ $course ? 'Salvar alterações' : 'Criar curso' }}
            </button>
        <a href="{{ route('admin.dashboard') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
        </div>
    </form>
</section>
