@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
    @php
        $exportQuery = array_filter([
            'search' => $search,
            'created_from' => $createdFrom,
            'created_to' => $createdTo,
            'course_id' => $courseId,
        ], static fn ($value): bool => $value !== null && $value !== '');
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Equipe e alunos</p>
                <h1 class="font-display text-3xl text-edux-primary">Usuários cadastrados</h1>
                <p class="text-slate-600 text-sm">Pesquise, visualize e edite qualquer perfil da plataforma.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="edux-btn">
                Novo usuário
            </a>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card space-y-4">
            <form method="GET" class="grid gap-3 lg:grid-cols-5">
                <label class="text-sm font-semibold text-slate-600 lg:col-span-2">
                    <span>Buscar</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Nome, e-mail ou WhatsApp"
                        class="mt-1 w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>

                <label class="text-sm font-semibold text-slate-600">
                    <span>Cadastro inicial</span>
                    <input
                        type="date"
                        name="created_from"
                        value="{{ $createdFrom }}"
                        class="mt-1 w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>

                <label class="text-sm font-semibold text-slate-600">
                    <span>Cadastro final</span>
                    <input
                        type="date"
                        name="created_to"
                        value="{{ $createdTo }}"
                        class="mt-1 w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>

                <label class="text-sm font-semibold text-slate-600">
                    <span>Curso matriculado</span>
                    <select
                        name="course_id"
                        class="mt-1 w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                        <option value="">Todos os cursos</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected($courseId === $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="flex flex-wrap gap-3 lg:col-span-5">
                    <button type="submit" class="edux-btn w-full md:w-auto">Buscar</button>
                    <a href="{{ route('admin.users.index') }}" class="edux-btn w-full bg-white text-edux-primary md:w-auto">
                        Limpar filtros
                    </a>
                    <a href="{{ route('admin.users.export', $exportQuery) }}" class="edux-btn w-full bg-white text-edux-primary md:w-auto">
                        Exportar CSV
                    </a>
                </div>
            </form>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-500 text-xs uppercase tracking-wide">
                            <th class="pb-2">Nome</th>
                            <th class="pb-2">E-mail</th>
                            <th class="pb-2">Papel</th>
                            <th class="pb-2">WhatsApp</th>
                            <th class="pb-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $user->preferredName() }}</div>
                                    <p class="text-xs text-slate-500">ID #{{ $user->id }}</p>
                                </td>
                                <td class="py-3">{{ $user->email }}</td>
                                <td class="py-3">{{ $user->role->label() }}</td>
                                <td class="py-3">{{ $user->whatsapp ?? '—' }}</td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-edux-primary text-sm underline-offset-2 hover:underline">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-sm text-slate-500">
                                    Nenhum usuário encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 md:flex-row md:items-center md:justify-between">
                <p class="text-sm font-medium text-slate-600">
                    Total de registros: {{ $users->total() }}
                </p>

                {{ $users->links() }}
            </div>
        </div>
    </section>
@endsection
