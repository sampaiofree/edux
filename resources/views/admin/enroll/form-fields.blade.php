@php
    $enrollment = $enrollment ?? null;
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Curso</span>
        <select
            name="course_id"
            required
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
            <option value="">Selecione um curso</option>
            @foreach ($courses as $course)
                <option value="{{ $course->id }}" @selected((string) old('course_id', $enrollment?->course_id) === (string) $course->id)>
                    #{{ $course->id }} - {{ $course->title }}
                </option>
            @endforeach
        </select>
        @error('course_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>

    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Aluno</span>
        <select
            name="user_id"
            required
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
            <option value="">Selecione um aluno</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $enrollment?->user_id) === (string) $user->id)>
                    #{{ $user->id }} - {{ $user->preferredName() }} ({{ $user->email ?? $user->whatsapp ?? 'Sem contato' }})
                </option>
            @endforeach
        </select>
        @error('user_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Status de acesso</span>
        <select
            name="access_status"
            required
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
            <option value="active" @selected(old('access_status', $enrollment?->access_status?->value ?? 'active') === 'active')>Ativo</option>
            <option value="blocked" @selected(old('access_status', $enrollment?->access_status?->value ?? 'active') === 'blocked')>Bloqueado</option>
        </select>
        @error('access_status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>

    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Bloqueado em</span>
        <input
            type="datetime-local"
            name="access_blocked_at"
            value="{{ old('access_blocked_at', $enrollment?->access_blocked_at?->format('Y-m-d\\TH:i')) }}"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        @error('access_blocked_at') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Motivo do bloqueio</span>
        <input
            type="text"
            name="access_block_reason"
            value="{{ old('access_block_reason', $enrollment?->access_block_reason ?? '') }}"
            placeholder="Ex.: revoke, refund, chargeback"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        @error('access_block_reason') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>

    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span class="flex items-center gap-2">
            <input
                type="checkbox"
                name="manual_override"
                value="1"
                @checked(old('manual_override', (bool) ($enrollment?->manual_override ?? false)))
                class="rounded border border-edux-line text-edux-primary focus:ring-edux-primary/40"
            >
            Override manual (admin libera acesso)
        </span>
        <span class="text-xs font-normal text-slate-500">
            Se marcado, o acesso permanece ativo ate um admin remover o override.
        </span>
        @error('manual_override') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Progresso (%)</span>
        <input
            type="number"
            min="0"
            max="100"
            name="progress_percent"
            required
            value="{{ old('progress_percent', $enrollment?->progress_percent ?? 0) }}"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        @error('progress_percent') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>

    <label class="space-y-2 text-sm font-semibold text-slate-600">
        <span>Concluido em</span>
        <input
            type="datetime-local"
            name="completed_at"
            value="{{ old('completed_at', $enrollment?->completed_at?->format('Y-m-d\\TH:i')) }}"
            class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
        >
        @error('completed_at') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </label>
</div>
