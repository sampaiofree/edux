<div class="space-y-6 rounded-card bg-white p-6 shadow-card">
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Nome completo</span>
                <input type="text" wire:model.defer="name" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @if (! $canRename)
                    <small class="text-xs text-slate-500">Você já alterou seu nome uma vez.</small>
                @endif
                @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>E-mail</span>
                <input type="email" wire:model.defer="email" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Nova senha (opcional)</span>
                <input type="password" wire:model.defer="password" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30" placeholder="Mínimo 8 caracteres">
                @error('password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Confirmar senha</span>
                <input type="password" wire:model.defer="password_confirmation" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            </label>
        </div>

        <div class="space-y-2 text-sm font-semibold text-slate-600">
            <span>Foto de perfil (máx. 500kb)</span>
            <input type="file" wire:model="profile_photo" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3">
            @error('profile_photo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            <div class="flex items-center gap-4">
                @if ($profile_photo)
                    <img src="{{ $profile_photo->temporaryUrl() }}" alt="Prévia" class="h-20 w-20 rounded-full border border-edux-line object-cover">
                @elseif ($currentPhotoUrl)
                    <img src="{{ $currentPhotoUrl }}" alt="Foto atual" class="h-20 w-20 rounded-full border border-edux-line object-cover">
                    <button type="button" class="text-xs font-semibold text-red-500 underline-offset-2 hover:underline" wire:click="removePhoto">Remover</button>
                @endif
                <div wire:loading wire:target="profile_photo" class="text-xs text-slate-500">Carregando imagem...</div>
            </div>
        </div>

        <div class="space-y-2 text-sm font-semibold text-slate-600">
            <span>Qualificação</span>
            <textarea id="qualification" wire:model.defer="qualification" rows="4" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30" placeholder="Descreva sua qualificação..."></textarea>
            @error('qualification') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="edux-btn w-full md:w-auto">Salvar alterações</button>
    </form>
</div>
