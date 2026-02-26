@extends('layouts.app')

@section('title', 'Kavoo')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Kavoo</p>
                <h1 class="font-display text-3xl text-edux-primary">Registros comerciais</h1>
                <p class="text-slate-600 text-sm">Visualize, edite ou exclua os eventos recebidos pela integração Kavoo.</p>
            </div>
            <a href="{{ route('admin.kavoo.create') }}" class="edux-btn">
                Novo registro
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
                        placeholder="Código, cliente, produto ou status"
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
                            <th class="pb-2">Transação</th>
                            <th class="pb-2">Cliente</th>
                            <th class="pb-2">Produto</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Horario (SP)</th>
                            <th class="pb-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($kavoos as $kavoo)
                            @php
                                $linkedUser = $kavoo->customerEmailOwner ?? $kavoo->customerPhoneOwner;
                                $occurredAtSp = $kavoo->occurredAtSaoPaulo();
                                $createdAtSp = $kavoo->createdAtSaoPaulo();
                            @endphp
                            <tr>
                                <td class="py-3">#{{ $kavoo->id }}</td>
                                <td class="py-3">{{ $kavoo->transaction_code ?? '-' }}</td>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $kavoo->customer_name ?? '-' }}</div>
                                    @if ($linkedUser)
                                        <p class="text-xs text-slate-500">
                                            <a href="{{ route('admin.users.edit', $linkedUser) }}" class="hover:underline">
                                                {{ $linkedUser->preferredName() }}
                                            </a>
                                            <span class="text-slate-400">
                                                {{ $linkedUser->email ?? $linkedUser->whatsapp }}
                                            </span>
                                        </p>
                                    @endif
                                </td>
                                <td class="py-3">{{ $kavoo->item_product_name ?? '-' }}</td>
                                <td class="py-3">{{ $kavoo->status_code ?? '-' }}</td>
                                <td class="py-3">
                                    <div class="font-semibold text-slate-800">
                                        {{ $occurredAtSp?->format('d/m/Y H:i') ?? '-' }}
                                    </div>
                                    <p class="text-xs text-slate-500">Transacao (payload)</p>
                                    <p class="text-xs text-slate-400">
                                        Registro local: {{ $createdAtSp?->format('d/m/Y H:i') ?? '-' }}
                                    </p>
                                </td>
                                <td class="py-3 text-right space-x-3">
                                    <a href="{{ route('admin.kavoo.edit', $kavoo) }}" class="text-edux-primary text-sm underline-offset-2 hover:underline">Editar</a>
                                    <form action="{{ route('admin.kavoo.destroy', $kavoo) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-rose-500">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-slate-500">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $kavoos->links() }}
        </div>
    </section>
@endsection
