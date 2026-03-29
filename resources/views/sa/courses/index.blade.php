@extends('layouts.sa')

@section('title', 'Super Admin | Cursos')

@section('content')
    @php
        $tenantLabel = static fn ($tenant) => trim((string) ($tenant?->escola_nome ?? '')) ?: trim((string) ($tenant?->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Área global</p>
            <h1 class="font-display text-3xl text-edux-primary">Cursos</h1>
            <p class="text-sm text-slate-600">Visualize e edite cursos de qualquer escola sem depender do escopo tenant atual.</p>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card space-y-4">
            <form method="GET" class="flex flex-col gap-3 xl:flex-row">
                <label class="flex-1 text-sm font-semibold text-slate-600">
                    <span class="sr-only">Buscar cursos</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Título, slug, responsável, escola ou ID"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
                <label class="text-sm font-semibold text-slate-600">
                    <span class="sr-only">Filtrar status</span>
                    <select name="status" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30 xl:w-52">
                        <option value="all" @selected($status === 'all')>Todos os status</option>
                        <option value="draft" @selected($status === 'draft')>Rascunho</option>
                        <option value="published" @selected($status === 'published')>Publicado</option>
                        <option value="archived" @selected($status === 'archived')>Arquivado</option>
                    </select>
                </label>
                <button type="submit" class="edux-btn xl:w-auto">Buscar</button>
            </form>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-slate-500">
                            <th class="pb-2">Curso</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Responsável</th>
                            <th class="pb-2">Escola</th>
                            <th class="pb-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($courses as $course)
                            <tr>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $course->title }}</div>
                                    <p class="text-xs text-slate-500">ID #{{ $course->id }} • {{ $course->slug }}</p>
                                </td>
                                <td class="py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-3 py-0.5 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $course->status === 'draft',
                                        'bg-emerald-100 text-emerald-800' => $course->status === 'published',
                                        'bg-slate-200 text-slate-700' => $course->status === 'archived',
                                    ])>
                                        {{ ucfirst($course->status) }}
                                    </span>
                                </td>
                                <td class="py-3">{{ $course->owner?->name ?? '—' }}</td>
                                <td class="py-3">
                                    <div class="font-semibold text-slate-700">{{ $tenantLabel($course->systemSetting) }}</div>
                                    <p class="text-xs text-slate-500">{{ $course->systemSetting?->domain ?? 'Sem domínio' }}</p>
                                </td>
                                <td class="py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('sa.courses.edit', $course->id) }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Editar</a>
                                        <form method="POST" action="{{ route('sa.courses.destroy', $course->id) }}" onsubmit="return confirm('Excluir este curso permanentemente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-500">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-sm text-slate-500">Nenhum curso encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $courses->links() }}
        </div>
    </section>
@endsection
