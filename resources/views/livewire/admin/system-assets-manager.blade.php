<section class="rounded-card bg-white p-6 shadow-card space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm uppercase tracking-wide text-edux-primary">Identidade visual</p>
            <h2 class="text-2xl font-display text-edux-primary">Padrões do sistema</h2>
            <p class="text-sm text-slate-600">Envie arquivos base para manter todos os cursos com a mesma cara.</p>
        </div>
    </div>

    <section class="rounded-2xl border border-edux-line/60 bg-slate-50 p-4 space-y-4">
        <div>
            <h3 class="text-lg font-display text-edux-primary">Meta Ads Pixel</h3>
            <p class="text-xs text-slate-500">
                ID numérico do pixel usado na LP pública de cursos em <code>/catalogo/{slug}</code>.
            </p>
        </div>

        <form wire:submit.prevent="saveMetaAdsPixel" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Pixel ID</span>
                <input
                    type="text"
                    inputmode="numeric"
                    wire:model.defer="meta_ads_pixel"
                    placeholder="Ex.: 123456789012345"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('meta_ads_pixel')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-500">Aceita apenas números. Se deixar vazio, o pixel não será carregado.</p>
            </label>

            <button
                type="submit"
                class="edux-btn h-fit"
                wire:loading.attr="disabled"
                wire:target="saveMetaAdsPixel"
            >
                Salvar Pixel
            </button>
        </form>
    </section>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($fields as $key => $data)
            @php
                $column = $data['column'];
                $preview = $settings->assetUrl($column);
                $accept = $key === 'carta_estagio'
                    ? '.webp,.png,.jpg,.jpeg,image/webp,image/png,image/jpeg'
                    : 'image/*';
            @endphp

            <article class="rounded-2xl border border-edux-line/60 p-4 space-y-4" wire:key="system-asset-{{ $key }}">
                <div class="h-32 w-full overflow-hidden rounded-xl bg-edux-background flex items-center justify-center">
                    @if ($preview)
                        <img src="{{ $preview }}" alt="{{ $data['label'] }}" class="h-full w-full object-cover">
                    @else
                        <span class="text-xs text-slate-400">Sem imagem</span>
                    @endif
                </div>
                <div class="space-y-1">
                    <h3 class="text-lg font-display text-edux-primary">{{ $data['label'] }}</h3>
                    <p class="text-xs text-slate-500">{{ $data['hint'] }}</p>
                </div>
                <form wire:submit.prevent="save('{{ $key }}')" class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-600">
                        <span class="sr-only">Selecionar arquivo</span>
                        <input type="file" wire:model="uploads.{{ $key }}" accept="{{ $accept }}"
                            class="block w-full rounded-xl border border-dashed border-edux-line px-4 py-2 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-edux-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white focus:border-edux-primary focus:ring-edux-primary/30">
                    </label>
                    @error("uploads.$key")
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @if (session()->has("status_{$key}"))
                        <p class="text-xs text-emerald-600" wire:loading.remove wire:target="save('{{ $key }}'), uploads.{{ $key }}">
                            {{ session("status_{$key}") }}
                        </p>
                    @endif
                    <div class="flex items-center gap-2">
                        <button type="submit" class="edux-btn text-sm"
                            wire:loading.attr="disabled"
                            wire:target="save('{{ $key }}'), uploads.{{ $key }}">
                            Salvar
                        </button>
                        <span class="text-xs text-slate-500" wire:loading wire:target="save('{{ $key }}'), uploads.{{ $key }}">
                            Enviando...
                        </span>
                    </div>
                </form>
            </article>
        @endforeach
    </div>
</section>

