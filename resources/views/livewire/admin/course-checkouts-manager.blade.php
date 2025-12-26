<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm uppercase tracking-wide text-edux-primary">Certificado</p>
            <h2 class="text-2xl font-display text-edux-primary">Checkouts do Certificado</h2>
            <p class="text-xs text-slate-500">Cadastre opcoes de carga horaria e links de checkout por curso.</p>
        </div>
        @if ($editingCheckout)
            <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="resetForm">
                Novo checkout
            </button>
        @endif
    </div>

    <form wire:submit.prevent="saveCheckout" class="grid gap-4 md:grid-cols-2">
        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Carga horaria (horas)</span>
            <input
                type="number"
                min="1"
                wire:model.live.debounce.400ms="hours"
                required
                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
            >
            @error('hours') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>

        <label class="space-y-1 text-sm font-semibold text-slate-600">
            <span>Valor</span>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">R$</span>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    wire:model.defer="price"
                    required
                    class="w-full rounded-xl border border-edux-line px-4 py-3 pl-10 focus:border-edux-primary focus:ring-edux-primary/30"
                >
            </div>
            @error('price') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>

        <label class="space-y-1 text-sm font-semibold text-slate-600 md:col-span-2">
            <span>Checkout URL</span>
            <input
                type="url"
                wire:model.defer="checkout_url"
                required
                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
            >
            @error('checkout_url') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </label>

        <label class="flex items-center gap-2 text-sm font-semibold text-slate-600 md:col-span-2">
            <input
                type="checkbox"
                wire:model.defer="is_active"
                class="rounded border-edux-line text-edux-primary focus:ring-edux-primary/50"
            >
            <span>Ativo</span>
        </label>

        <div class="flex flex-wrap gap-3 md:col-span-2">
            <button type="submit" class="edux-btn">
                {{ $editingCheckout ? 'Salvar checkout' : 'Adicionar checkout' }}
            </button>
            @if ($editingCheckout)
                <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="resetForm">
                    Cancelar edicao
                </button>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="text-slate-500">
                    <th class="py-2">Carga horaria</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th class="text-right">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($checkouts as $checkout)
                    <tr class="border-t border-edux-line/60" wire:key="checkout-{{ $checkout->id }}">
                        <td class="py-2">{{ $checkout->hours }}h</td>
                        <td>R$ {{ number_format($checkout->price, 2, ',', '.') }}</td>
                        <td>
                            <span @class([
                                'px-2 py-1 rounded-full text-xs font-semibold',
                                'bg-emerald-100 text-emerald-700' => $checkout->is_active,
                                'bg-slate-100 text-slate-600' => ! $checkout->is_active,
                            ])>
                                {{ $checkout->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="py-2 text-right">
                            <div class="flex flex-wrap justify-end gap-3 text-sm font-semibold text-edux-primary">
                                <button type="button" wire:click="editCheckout({{ $checkout->id }})" class="underline-offset-2 hover:underline">
                                    Editar
                                </button>
                                @if ($checkout->is_active)
                                    <button type="button" wire:click="deactivateCheckout({{ $checkout->id }})" class="text-red-500 underline-offset-2 hover:underline">
                                        Desativar
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">Inativo</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-4 text-center text-slate-500">Nenhum checkout cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
