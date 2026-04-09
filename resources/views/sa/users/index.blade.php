@extends('layouts.sa')

@section('title', 'Super Admin | Usuários')

@section('content')
    @php
        $tenantLabel = static fn ($tenant) => trim((string) ($tenant?->escola_nome ?? '')) ?: trim((string) ($tenant?->domain ?? 'Sem tenant'));
    @endphp

    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Área global</p>
                <h1 class="font-display text-3xl text-edux-primary">Usuários</h1>
                <p class="text-sm text-slate-600">Pesquise e gerencie contas de todas as escolas.</p>
            </div>
            <a href="{{ route('sa.users.create') }}" class="edux-btn">
                Novo usuário
            </a>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card space-y-4">
            <form method="GET" class="flex flex-col gap-3 xl:flex-row">
                <label class="flex-1 text-sm font-semibold text-slate-600">
                    <span class="sr-only">Buscar usuários</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Nome, e-mail, WhatsApp, escola ou ID"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
                <label class="text-sm font-semibold text-slate-600">
                    <span class="sr-only">Filtrar papel</span>
                    <select name="role" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30 xl:w-52">
                        <option value="all" @selected($role === 'all')>Todos os papéis</option>
                        <option value="admin" @selected($role === 'admin')>Administradores</option>
                        <option value="teacher" @selected($role === 'teacher')>Professores</option>
                        <option value="student" @selected($role === 'student')>Alunos</option>
                    </select>
                </label>
                <button type="submit" class="edux-btn xl:w-auto">Buscar</button>
            </form>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-slate-500">
                            <th class="pb-2">Nome</th>
                            <th class="pb-2">E-mail</th>
                            <th class="pb-2">Papel</th>
                            <th class="pb-2">Escola</th>
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
                                <td class="py-3">
                                    <div class="font-semibold text-slate-700">{{ $tenantLabel($user->systemSetting) }}</div>
                                    <p class="text-xs text-slate-500">{{ $user->systemSetting?->domain ?? 'Sem domínio' }}</p>
                                </td>
                                <td class="py-3">{{ $user->whatsapp ?? '—' }}</td>
                                <td class="py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('sa.users.edit', $user->id) }}" class="text-sm font-semibold text-edux-primary underline-offset-2 hover:underline">Editar</a>
                                        <form method="POST" action="{{ route('sa.users.destroy', $user->id) }}" onsubmit="return confirm('Excluir este usuário permanentemente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-500">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-sm text-slate-500">Nenhum usuário encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </section>
@endsection
