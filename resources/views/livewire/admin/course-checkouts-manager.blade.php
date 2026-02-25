<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm uppercase tracking-wide text-edux-primary">Certificado</p>
            <h2 class="text-2xl font-display text-edux-primary">Checkout</h2>
            <p class="text-xs text-slate-500">Gerencie checkouts e bonus relacionados a cada opcao.</p>
        </div>

        <button type="button" class="edux-btn" wire:click="openCreateCheckoutModal">
            Novo checkout
        </button>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-edux-line/70 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">Carga horaria</th>
                    <th class="px-4 py-3">Preco</th>
                    <th class="px-4 py-3 text-right">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($checkouts as $checkout)
                    <tr class="border-t border-edux-line/60" wire:key="checkout-row-{{ $checkout->id }}">
                        <td class="px-4 py-3 align-top">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900">{{ $checkout->nome ?: 'Sem nome' }}</p>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-emerald-100 text-emerald-700' => $checkout->is_active,
                                        'bg-slate-100 text-slate-600' => ! $checkout->is_active,
                                    ])>
                                        {{ $checkout->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </div>
                                @if ($checkout->descricao)
                                    <p class="max-w-lg text-xs leading-4 text-slate-500">{{ $checkout->descricao }}</p>
                                @endif
                                <p class="text-xs text-slate-400">
                                    {{ $checkout->bonuses->count() }} {{ $checkout->bonuses->count() === 1 ? 'bonus' : 'bonus' }}
                                </p>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top text-slate-700">{{ $checkout->hours }}h</td>
                        <td class="px-4 py-3 align-top font-semibold text-edux-primary">
                            R$ {{ number_format($checkout->price, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 align-top text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="editCheckout({{ $checkout->id }})"
                                    class="inline-flex items-center rounded-lg border border-edux-line bg-white px-3 py-1.5 text-xs font-semibold text-edux-primary hover:bg-edux-primary/5"
                                >
                                    Editar
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteCheckout({{ $checkout->id }})"
                                    onclick="return confirm('Excluir este checkout e todos os bonus relacionados?')"
                                    class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                >
                                    Excluir
                                </button>

                                <button
                                    type="button"
                                    wire:click="openBonusListModal({{ $checkout->id }})"
                                    class="inline-flex items-center rounded-lg bg-edux-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-edux-primary/90"
                                >
                                    Bonus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                            Nenhum checkout cadastrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showCheckoutModal)
        <div
            class="fixed inset-0 z-50 flex items-start justify-center overflow-auto bg-black/60 px-4 py-10"
            wire:click="closeCheckoutModal"
        >
            <div
                class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5"
                x-on:click.stop
            >
                <div class="flex items-center justify-between border-b border-edux-line/50 pb-4">
                    <div>
                        <p class="text-sm uppercase tracking-wide text-edux-primary">Checkout</p>
                        <h3 class="font-display text-2xl text-slate-900">
                            {{ $editingCheckout ? 'Editar checkout' : 'Novo checkout' }}
                        </h3>
                        <p class="text-xs text-slate-500">Preencha nome, carga horaria, valor e link de pagamento.</p>
                    </div>
                    <button type="button" wire:click="closeCheckoutModal" class="text-slate-500 hover:text-slate-800">
                        <span class="sr-only">Fechar</span>
                        &times;
                    </button>
                </div>

                <form wire:submit.prevent="saveCheckout" class="mt-6 grid gap-4 md:grid-cols-2">
                    <label class="space-y-1 text-sm font-semibold text-slate-600 md:col-span-2">
                        <span>Nome</span>
                        <input
                            type="text"
                            wire:model.defer="nome"
                            required
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            placeholder="Ex.: Certificado 200h"
                        >
                        @error('nome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <label class="space-y-1 text-sm font-semibold text-slate-600 md:col-span-2">
                        <span>Descricao</span>
                        <textarea
                            wire:model.defer="descricao"
                            rows="3"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            placeholder="Texto opcional para descrever esta opcao."
                        ></textarea>
                        @error('descricao') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

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
                        <span>Preco</span>
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

                    <div class="flex flex-wrap justify-end gap-3 border-t border-edux-line/50 pt-4 md:col-span-2">
                        <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="closeCheckoutModal">
                            Cancelar
                        </button>
                        <button type="submit" class="edux-btn">
                            {{ $editingCheckout ? 'Salvar checkout' : 'Criar checkout' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showBonusListModal && $selectedBonusCheckout)
        <div
            class="fixed inset-0 z-50 flex items-start justify-center overflow-auto bg-black/60 px-4 py-10"
            wire:click="closeBonusListModal"
        >
            <div
                class="w-full max-w-5xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5"
                x-on:click.stop
            >
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-edux-line/50 pb-4">
                    <div>
                        <p class="text-sm uppercase tracking-wide text-edux-primary">Checkout Bonus</p>
                        <h3 class="font-display text-2xl text-slate-900">Bonus do checkout</h3>
                        <p class="text-xs text-slate-500">
                            {{ $selectedBonusCheckout->nome ?: ('Checkout ' . $selectedBonusCheckout->hours . 'h') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="edux-btn" wire:click="openCreateBonusModal({{ $selectedBonusCheckout->id }})">
                            Criar bonus
                        </button>
                        <button type="button" wire:click="closeBonusListModal" class="text-slate-500 hover:text-slate-800">
                            <span class="sr-only">Fechar</span>
                            &times;
                        </button>
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto rounded-2xl border border-edux-line/60">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Nome</th>
                                <th class="px-4 py-3">Descricao</th>
                                <th class="px-4 py-3">Preco</th>
                                <th class="px-4 py-3 text-right">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($selectedBonusCheckout->bonuses as $bonus)
                                <tr class="border-t border-edux-line/60" wire:key="bonus-list-row-{{ $bonus->id }}">
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $bonus->nome }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $bonus->descricao ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-edux-primary">
                                        R$ {{ number_format($bonus->preco, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button
                                                type="button"
                                                wire:click="editBonus({{ $bonus->id }})"
                                                class="inline-flex items-center rounded-lg border border-edux-line bg-white px-3 py-1.5 text-xs font-semibold text-edux-primary hover:bg-edux-primary/5"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="deleteBonus({{ $bonus->id }})"
                                                onclick="return confirm('Excluir este bonus?')"
                                                class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                            >
                                                Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Nenhum bonus cadastrado para este checkout.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($showBonusFormModal)
        <div
            class="fixed inset-0 z-[60] flex items-start justify-center overflow-auto bg-black/60 px-4 py-12"
            wire:click="closeBonusFormModal"
        >
            <div
                class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5"
                x-on:click.stop
            >
                <div class="flex items-center justify-between border-b border-edux-line/50 pb-4">
                    <div>
                        <p class="text-sm uppercase tracking-wide text-edux-primary">Checkout Bonus</p>
                        <h3 class="font-display text-2xl text-slate-900">
                            {{ $editingBonus ? 'Editar bonus' : 'Criar bonus' }}
                        </h3>
                        @if ($selectedBonusCheckout)
                            <p class="text-xs text-slate-500">
                                Vinculado a: {{ $selectedBonusCheckout->nome ?: ($selectedBonusCheckout->hours . 'h') }}
                            </p>
                        @endif
                    </div>
                    <button type="button" wire:click="closeBonusFormModal" class="text-slate-500 hover:text-slate-800">
                        <span class="sr-only">Fechar</span>
                        &times;
                    </button>
                </div>

                <form wire:submit.prevent="saveBonus" class="mt-6 space-y-4">
                    <input type="hidden" wire:model="bonusCheckoutId">
                    @error('bonusCheckoutId') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                    <label class="block space-y-1 text-sm font-semibold text-slate-600">
                        <span>Nome</span>
                        <input
                            type="text"
                            wire:model.defer="bonus_nome"
                            required
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            placeholder="Ex.: Carta de Estagio Premium"
                        >
                        @error('bonus_nome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <label class="block space-y-1 text-sm font-semibold text-slate-600">
                        <span>Descricao</span>
                        <textarea
                            wire:model.defer="bonus_descricao"
                            rows="3"
                            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                            placeholder="Explique o que esta incluso neste bonus."
                        ></textarea>
                        @error('bonus_descricao') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <label class="block space-y-1 text-sm font-semibold text-slate-600">
                        <span>Preco</span>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">R$</span>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model.defer="bonus_preco"
                                required
                                class="w-full rounded-xl border border-edux-line px-4 py-3 pl-10 focus:border-edux-primary focus:ring-edux-primary/30"
                            >
                        </div>
                        @error('bonus_preco') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </label>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-edux-line/50 pt-4">
                        <button type="button" class="edux-btn bg-white text-edux-primary" wire:click="closeBonusFormModal">
                            Cancelar
                        </button>
                        <button type="submit" class="edux-btn">
                            {{ $editingBonus ? 'Salvar bonus' : 'Criar bonus' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
