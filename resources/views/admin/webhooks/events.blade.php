@extends('layouts.app')

@section('title', 'Eventos de Webhook')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Pagamentos</p>
                <h1 class="font-display text-3xl text-edux-primary">Eventos - {{ $webhookLink->name }}</h1>
                <p class="text-slate-600 text-sm">Acompanhe processamento, pendencias e inconsistencias.</p>
            </div>
            <a href="{{ route('admin.webhooks.edit', $webhookLink) }}" class="edux-btn bg-white text-edux-primary">Voltar para configuracao</a>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card space-y-4">
            <form method="GET" class="grid gap-3 md:grid-cols-4">
                <input type="search" name="search" value="{{ $search }}" placeholder="Buscar por evento/email/tx/produto" class="rounded-xl border border-edux-line px-4 py-3 md:col-span-2">
                <select name="status" class="rounded-xl border border-edux-line px-4 py-3">
                    <option value="">Todos status</option>
                    <option value="queued" @selected($status === 'queued')>queued</option>
                    <option value="processed" @selected($status === 'processed')>processed</option>
                    <option value="ignored" @selected($status === 'ignored')>ignored</option>
                    <option value="pending" @selected($status === 'pending')>pending</option>
                    <option value="failed" @selected($status === 'failed')>failed</option>
                </select>
                <button type="submit" class="edux-btn">Filtrar</button>
            </form>

            <div class="overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase text-slate-500">
                            <th class="pb-2">ID</th>
                            <th class="pb-2">Evento</th>
                            <th class="pb-2">Acao</th>
                            <th class="pb-2">Email</th>
                            <th class="pb-2">Produto</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Recebido</th>
                            <th class="pb-2 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($events as $event)
                            <tr>
                                <td class="py-2">#{{ $event->id }}</td>
                                <td class="py-2">{{ $event->external_event_code ?? '-' }}</td>
                                <td class="py-2">{{ $event->internal_action?->value ?? $event->internal_action ?? '-' }}</td>
                                <td class="py-2">{{ $event->buyer_email ?? '-' }}</td>
                                <td class="py-2">{{ $event->external_product_id ?? '-' }}</td>
                                <td class="py-2">{{ $event->processing_status?->value ?? $event->processing_status }}</td>
                                <td class="py-2">{{ $event->received_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                <td class="py-2">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.webhooks.events.show', [$webhookLink, $event]) }}" class="text-edux-primary text-sm hover:underline">Abrir</a>
                                        <form method="POST" action="{{ route('admin.webhooks.events.destroy', [$webhookLink, $event]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-sm text-red-600 hover:underline"
                                                onclick="return confirm('Excluir este evento? Esta acao nao pode ser desfeita.')"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-sm text-slate-500">Nenhum evento encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $events->links() }}
        </div>
    </section>
@endsection
