@extends('layouts.sa')

@section('title', 'Super Admin | Editar matrícula')

@section('content')
    @php
        $tenantLabel = static fn ($tenant) => trim((string) ($tenant?->escola_nome ?? '')) ?: trim((string) ($tenant?->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Matrícula global</p>
                <h1 class="font-display text-3xl text-edux-primary">Editar matrícula #{{ $enrollment->id }}</h1>
                <p class="text-sm text-slate-600">Ajuste tenant, curso, aluno e estado de acesso mantendo a consistência entre os vínculos.</p>
            </div>
            <a href="{{ route('sa.enrollments.index') }}" class="edux-btn bg-white text-edux-primary">Voltar para a lista</a>
        </header>

        <form method="POST" action="{{ route('sa.enrollments.update', $enrollment->id) }}" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Escola / tenant</span>
                    <select name="system_setting_id" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) old('system_setting_id', $enrollment->system_setting_id) === (string) $tenant->id)>
                                {{ $tenantLabel($tenant) }} — ID #{{ $tenant->id }}
                            </option>
                        @endforeach
                    </select>
                    @error('system_setting_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Curso</span>
                    <select name="course_id" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        <option value="">Selecione um curso</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('course_id', $enrollment->course_id) === (string) $course->id)>
                                #{{ $course->id }} — {{ $course->title }} — {{ $tenantLabel($course->systemSetting) }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Aluno</span>
                    <select name="user_id" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        <option value="">Selecione um usuário</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('user_id', $enrollment->user_id) === (string) $user->id)>
                                #{{ $user->id }} — {{ $user->preferredName() }} — {{ $tenantLabel($user->systemSetting) }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Status de acesso</span>
                    <select name="access_status" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                        <option value="active" @selected(old('access_status', $enrollment->access_status?->value ?? $enrollment->access_status) === 'active')>Ativo</option>
                        <option value="blocked" @selected(old('access_status', $enrollment->access_status?->value ?? $enrollment->access_status) === 'blocked')>Bloqueado</option>
                    </select>
                    @error('access_status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Bloqueado em</span>
                    <input type="datetime-local" name="access_blocked_at" value="{{ old('access_blocked_at', $enrollment->access_blocked_at?->format('Y-m-d\\TH:i')) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('access_blocked_at') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Motivo do bloqueio</span>
                    <input type="text" name="access_block_reason" value="{{ old('access_block_reason', $enrollment->access_block_reason) }}" placeholder="Ex.: revoke, refund, chargeback" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('access_block_reason') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span class="flex items-center gap-2">
                        <input type="checkbox" name="manual_override" value="1" @checked(old('manual_override', (bool) $enrollment->manual_override)) class="rounded border border-edux-line text-edux-primary focus:ring-edux-primary/40">
                        Override manual
                    </span>
                    <span class="text-xs font-normal text-slate-500">Mantém o acesso ativo até que um admin remova o override.</span>
                    @error('manual_override') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Progresso (%)</span>
                    <input type="number" min="0" max="100" name="progress_percent" value="{{ old('progress_percent', $enrollment->progress_percent ?? 0) }}" required class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('progress_percent') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-semibold text-slate-600">
                    <span>Concluído em</span>
                    <input type="datetime-local" name="completed_at" value="{{ old('completed_at', $enrollment->completed_at?->format('Y-m-d\\TH:i')) }}" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                    @error('completed_at') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="rounded-2xl border border-edux-line/60 bg-edux-background/70 p-4 text-sm text-slate-600">
                <p class="font-semibold text-edux-primary">Contexto atual</p>
                <p class="mt-2">Curso atual: {{ $enrollment->course?->title ?? 'Curso removido' }}</p>
                <p class="mt-1">Usuário atual: {{ $enrollment->user?->preferredName() ?? 'Usuário removido' }}</p>
                <p class="mt-1">Escola atual: {{ $tenantLabel($enrollment->systemSetting) }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Salvar alterações</button>
                <a href="{{ route('sa.enrollments.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
