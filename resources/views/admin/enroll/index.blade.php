@extends('layouts.app')

@section('title', 'Matriculas')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Matriculas</p>
                <h1 class="font-display text-3xl text-edux-primary">Matriculas dos alunos</h1>
                <p class="text-slate-600 text-sm">Visualize, edite ou exclua matriculas da plataforma.</p>
            </div>
            <a href="{{ route('admin.enroll.create') }}" class="edux-btn">
                Nova matricula
            </a>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card space-y-4">
            <form method="GET" class="flex flex-col gap-3 md:flex-row">
                <label class="flex-1 text-sm font-semibold text-slate-600">
                    <span class="sr-only">Buscar</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Aluno, curso, e-mail ou ID"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
                <button type="submit" class="edux-btn w-full md:w-auto">Buscar</button>
            </form>

            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </p>
            @endif

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-500 text-xs uppercase tracking-wide">
                            <th class="pb-2">ID</th>
                            <th class="pb-2">Curso</th>
                            <th class="pb-2">Aluno</th>
                            <th class="pb-2">Progresso</th>
                            <th class="pb-2">Acesso</th>
                            <th class="pb-2">Concluido em</th>
                            <th class="pb-2">Criado em</th>
                            <th class="pb-2 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($enrollments as $enrollment)
                            <tr>
                                <td class="py-3">#{{ $enrollment->id }}</td>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">
                                        {{ $enrollment->course?->title ?? '-' }}
                                    </div>
                                    <p class="text-xs text-slate-500">ID #{{ $enrollment->course_id ?? '-' }}</p>
                                </td>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">
                                        {{ $enrollment->user?->preferredName() ?? '-' }}
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        {{ $enrollment->user?->email ?? $enrollment->user?->whatsapp ?? '-' }}
                                    </p>
                                </td>
                                <td class="py-3">{{ $enrollment->progress_percent ?? 0 }}%</td>
                                <td class="py-3">
                                    @if (($enrollment->access_status?->value ?? $enrollment->access_status) === 'blocked')
                                        <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Bloqueado</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Ativo</span>
                                    @endif
                                    @if ($enrollment->manual_override)
                                        <p class="mt-1 text-xs text-edux-primary">Override manual</p>
                                    @endif
                                </td>
                                <td class="py-3">{{ $enrollment->completed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="py-3">{{ $enrollment->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="py-3 text-right space-x-3">
                                    <a href="{{ route('admin.enroll.edit', $enrollment) }}" class="text-edux-primary text-sm underline-offset-2 hover:underline">Editar</a>
                                    <form action="{{ route('admin.enroll.destroy', $enrollment) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-rose-500">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-sm text-slate-500">
                                    Nenhuma matricula encontrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $enrollments->links() }}
        </div>
    </section>
@endsection
