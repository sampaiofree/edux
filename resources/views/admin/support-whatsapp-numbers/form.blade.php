@php
    $supportWhatsappNumber ??= null;
    $isActiveOld = old('is_active', $supportWhatsappNumber?->is_active ?? true);
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Identificação</span>
        <input
            type="text"
            name="label"
            value="{{ old('label', $supportWhatsappNumber->label ?? '') }}"
            required
            placeholder="Ex.: Atendimento comercial"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        <p class="text-xs text-slate-400">Nome interno para o administrador identificar este número.</p>
        @error('label') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>

    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Número de WhatsApp</span>
        <input
            type="text"
            name="whatsapp"
            value="{{ old('whatsapp', $supportWhatsappNumber->whatsapp ?? '') }}"
            required
            placeholder="Ex.: +55 11 99999-9999"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        <p class="text-xs text-slate-400">Pode informar com máscara. O link do WhatsApp será gerado automaticamente.</p>
        @error('whatsapp') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
</div>

<label class="space-y-2 text-sm font-semibold text-slate-600 block">
    <span>Descrição (opcional)</span>
    <textarea
        rows="3"
        name="description"
        placeholder="Ex.: Atendimento em horário comercial / Suporte de acesso"
        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
    >{{ old('description', $supportWhatsappNumber->description ?? '') }}</textarea>
    @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
</label>

<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Ordem</span>
        <input
            type="number"
            name="position"
            min="1"
            value="{{ old('position', $supportWhatsappNumber->position ?? 1) }}"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        <p class="text-xs text-slate-400">Menor número aparece primeiro.</p>
        @error('position') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>

    <div class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Status</span>
        <label class="flex min-h-[52px] items-center gap-3 rounded-xl border border-edux-line px-4 py-3">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked((bool) $isActiveOld)
                class="rounded border-edux-line text-edux-primary focus:ring-edux-primary"
            >
            <span class="text-sm font-medium text-slate-700">Ativo para uso em atendimento</span>
        </label>
        @error('is_active') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>
</div>

