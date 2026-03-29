@extends('layouts.sa')

@section('title', 'Super Admin | Matrículas')

@section('content')
    @php
        $tenantLabel = static fn ($tenant) => trim((string) ($tenant?->escola_nome ?? '')) ?: trim((string) ($tenant?->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Área global</p>
            <h1 class="font-display text-3xl text-edux-primary">Matrículas</h1>
            <p class="text-sm text-slate-600">Acompanhe vínculos de alunos e cursos em todas as escolas.</p>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card space-y-4">
            <form method="GET" class="flex flex-col gap-3 md:flex-row">
                <label class="flex-1 text-sm font-semibold text-slate-600">
                    <span class="sr-only">Buscar matrículas</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Aluno, curso, escola ou ID"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
                <button type="submit" class="edux-btn w-full md:w-auto">Buscar</button>
            </form>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-slate-500">
                            <th class="pb-2">ID</th>
                            <th class="pb-2">Curso</th>
                            <th class="pb-2">Aluno</th>
                            <th class="pb-2">Escola</th>
                            <th class="pb-2">Acesso</th>
                            <th class="pb-2">Progresso</th>
                            <th class="pb-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($enrollments as $enrollment)
                            <tr>
                                <td class="py-3">#{{ $enrollment->id }}</td>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $enrollment->course?->title ?? 'Curso removido' }}</div>
                                    <p class="text-xs text-slate-500">ID #{{ $enrollment->course_id }}</p>
                                </td>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $enrollment->user?->preferredName() ?? 'Usuário removido' }}</div>
                                    <p class="text-xs text-slate-500">{{ $enrollment->user?->email ?? $enrollment->user?->whatsapp ?? 'Sem contato' }}</p>
                                </td>
                                <td class="py-3">
                                    <div class="font-semibold text-slate-700">{{ $tenantLabel($enrollment->systemSetting) }}</div>
                                    <p class="text-xs text-slate-500">{{ $enrollment->systemSetting?->domain ?? 'Sem domínio' }}</p>
                                </td>
                                <td class="py-3">
                                    @if (($enrollment->access_status?->value ?? $enrollment->access_status) === 'blocked')
                                        <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Bloqueado</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Ativo</span>
                                    @endif
                                </td>
                                <td class="py-3">{{ $enrollment->progress_percent ?? 0 }}%</td>
                                <td class="py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('sa.enrollments.edit', $enrollment->id) }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Editar</a>
                                        <form method="POST" action="{{ route('sa.enrollments.destroy', $enrollment->id) }}" onsubmit="return confirm('Excluir esta matrícula permanentemente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-500">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-slate-500">Nenhuma matrícula encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $enrollments->links() }}
        </div>
    </section>
@endsection
