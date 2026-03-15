@extends('layouts.app')

@section('title', 'Webhooks de Pagamento')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Pagamentos</p>
                <h1 class="font-display text-3xl text-edux-primary">Webhooks multi-gateway</h1>
                <p class="text-slate-600 text-sm">Gerencie links de entrada, mapeamentos e eventos recebidos.</p>
            </div>
            <a href="{{ route('admin.webhooks.create') }}" class="edux-btn">
                Novo link
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
                        placeholder="Nome ou UUID"
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
                            <th class="pb-2">Nome</th>
                            <th class="pb-2">Endpoint</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Eventos</th>
                            <th class="pb-2 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($links as $link)
                            <tr>
                                <td class="py-3">#{{ $link->id }}</td>
                                <td class="py-3">
                                    <div class="font-semibold text-edux-primary">{{ $link->name }}</div>
                                    <p class="text-xs text-slate-500">Seguranca: {{ $link->security_mode ?: 'sem assinatura' }}</p>
                                </td>
                                <td class="py-3">
                                    <code class="text-xs">{{ route('api.webhooks.in', ['endpoint_uuid' => $link->endpoint_uuid]) }}</code>
                                </td>
                                <td class="py-3">
                                    @if ($link->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Ativo</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">Inativo</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div>Total: {{ (int) ($link->events_count ?? 0) }}</div>
                                    <p class="text-xs text-amber-700">Pendentes: {{ (int) ($link->pending_events_count ?? 0) }}</p>
                                </td>
                                <td class="py-3 text-right space-x-3">
                                    <a href="{{ route('admin.webhooks.edit', $link) }}" class="text-edux-primary text-sm underline-offset-2 hover:underline">Configurar</a>
                                    <a href="{{ route('admin.webhooks.events.index', $link) }}" class="text-edux-primary text-sm underline-offset-2 hover:underline">Eventos</a>
                                    <form action="{{ route('admin.webhooks.destroy', $link) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-rose-500">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-sm text-slate-500">
                                    Nenhum link encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $links->links() }}
        </div>
    </section>
@endsection
