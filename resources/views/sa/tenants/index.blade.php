@extends('layouts.sa')

@section('title', 'Super Admin | Escolas')

@section('content')
    @php
        $tenantLabel = static fn ($tenant) => trim((string) ($tenant?->escola_nome ?? '')) ?: trim((string) ($tenant?->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Área global</p>
                <h1 class="font-display text-3xl text-edux-primary">Escolas e tenants</h1>
                <p class="text-sm text-slate-600">Visualize todas as escolas cadastradas, com domínio, responsável e volume de dados por tenant.</p>
            </div>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card space-y-4">
            <form method="GET" class="flex flex-col gap-3 xl:flex-row">
                <label class="flex-1 text-sm font-semibold text-slate-600">
                    <span class="sr-only">Buscar escolas</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Nome da escola, domínio, responsável ou ID"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
                <button type="submit" class="edux-btn xl:w-auto">Buscar</button>
            </form>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-slate-500">
                            <th class="pb-2">Escola</th>
                            <th class="pb-2">Domínio</th>
                            <th class="pb-2">Responsável</th>
                            <th class="pb-2 text-center">Admins</th>
                            <th class="pb-2 text-center">Alunos</th>
                            <th class="pb-2 text-center">Cursos</th>
                            <th class="pb-2 text-center">Matrículas</th>
                            <th class="pb-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $tenantLabel($tenant) }}</div>
                                    <p class="text-xs text-slate-500">ID #{{ $tenant->id }}</p>
                                </td>
                                <td class="py-3">
                                    <div class="font-medium text-slate-700">{{ $tenant->domain ?? 'Sem domínio' }}</div>
                                </td>
                                <td class="py-3">
                                    <div class="font-medium text-slate-700">{{ $tenant->owner?->preferredName() ?? 'Sem responsável' }}</div>
                                    <p class="text-xs text-slate-500">{{ $tenant->owner?->email ?? '—' }}</p>
                                </td>
                                <td class="py-3 text-center">{{ $tenant->admins_count }}</td>
                                <td class="py-3 text-center">{{ $tenant->students_count }}</td>
                                <td class="py-3 text-center">{{ $tenant->courses_count }}</td>
                                <td class="py-3 text-center">{{ $tenant->enrollments_count }}</td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('sa.tenants.edit', $tenant->id) }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-sm text-slate-500">Nenhuma escola encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tenants->links() }}
        </div>
    </section>
@endsection
