@extends('layouts.app')

@section('title', 'WhatsApp de Atendimento')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Atendimento</p>
                <h1 class="font-display text-3xl text-edux-primary">WhatsApp de atendimento</h1>
                <p class="text-slate-600 text-sm">Cadastre números de WhatsApp para uso em suporte, comercial ou atendimento ao aluno.</p>
            </div>
            <a href="{{ route('admin.support-whatsapp.create') }}" class="edux-btn">
                Novo número
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
                        placeholder="Buscar por nome, número ou descrição"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                </label>
                <button type="submit" class="edux-btn w-full md:w-auto">Buscar</button>
            </form>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-500 text-xs uppercase tracking-wide">
                            <th class="pb-2">ID</th>
                            <th class="pb-2">Identificação</th>
                            <th class="pb-2">WhatsApp</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Ordem</th>
                            <th class="pb-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($numbers as $number)
                            <tr>
                                <td class="py-3">#{{ $number->id }}</td>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $number->label }}</div>
                                    <p class="text-xs text-slate-500">{{ $number->description ?: 'Sem descrição cadastrada.' }}</p>
                                </td>
                                <td class="py-3">
                                    <div>{{ $number->whatsapp }}</div>
                                    <a href="{{ $number->whatsappLink() }}" target="_blank" rel="noopener" class="text-xs text-emerald-600 hover:underline">
                                        Abrir no WhatsApp
                                    </a>
                                </td>
                                <td class="py-3">
                                    <span @class([
                                        'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold',
                                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $number->is_active,
                                        'border-slate-200 bg-slate-50 text-slate-600' => ! $number->is_active,
                                    ])>
                                        {{ $number->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="py-3">{{ $number->position }}</td>
                                <td class="py-3 text-right space-x-3">
                                    <a href="{{ route('admin.support-whatsapp.edit', $number) }}" class="text-edux-primary text-sm underline-offset-2 hover:underline">Editar</a>
                                    <form action="{{ route('admin.support-whatsapp.destroy', $number) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-rose-500" onclick="return confirm('Remover este número de atendimento?')">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-sm text-slate-500">
                                    Nenhum número de atendimento cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $numbers->links() }}
        </div>
    </section>
@endsection

