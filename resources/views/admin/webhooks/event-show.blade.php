@extends('layouts.app')

@section('title', 'Detalhe do Evento')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Pagamentos</p>
                <h1 class="font-display text-3xl text-edux-primary">Evento #{{ $event->id }}</h1>
                <p class="text-slate-600 text-sm">Status: <strong>{{ $event->processing_status?->value ?? $event->processing_status }}</strong> | Motivo: {{ $event->processing_reason ?? '-' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.webhooks.events.index', $webhookLink) }}" class="edux-btn bg-white text-edux-primary">Voltar</a>
                <form method="POST" action="{{ route('admin.webhooks.events.destroy', [$webhookLink, $event]) }}">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="edux-btn bg-red-500 text-white"
                        onclick="return confirm('Excluir este evento? Esta acao nao pode ser desfeita.')"
                    >
                        Excluir
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.webhooks.events.replay', [$webhookLink, $event]) }}">
                    @csrf
                    <button type="submit" class="edux-btn">Replay</button>
                </form>
            </div>
        </header>

        @if (session('status'))
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </p>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-card bg-white p-6 shadow-card space-y-3">
                <h2 class="font-semibold text-edux-primary">Resumo</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Evento externo</dt><dd>{{ $event->external_event_code ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Acao interna</dt><dd>{{ $event->internal_action?->value ?? $event->internal_action ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd>{{ $event->buyer_email ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Transacao externa</dt><dd>{{ $event->external_tx_id ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Produto externo</dt><dd>{{ $event->external_product_id ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Valor</dt><dd>{{ $event->amount !== null ? number_format((float) $event->amount, 2, ',', '.') : '-' }} {{ $event->currency ?? '' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Ocorrido em</dt><dd>{{ $event->occurred_at?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Recebido em</dt><dd>{{ $event->received_at?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-card bg-white p-6 shadow-card space-y-3">
                <h2 class="font-semibold text-edux-primary">Payload bruto</h2>
                <pre class="overflow-auto rounded-xl bg-slate-900 p-4 text-xs text-slate-100">{{ json_encode($event->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </section>
        </div>

        <section class="rounded-card bg-white p-6 shadow-card space-y-3">
            <h2 class="font-semibold text-edux-primary">Logs de processamento / Diff</h2>
            <div class="space-y-3">
                @forelse ($event->logs as $log)
                    <article class="rounded-xl border border-edux-line p-4">
                        <p class="text-xs text-slate-500">{{ $log->created_at?->format('d/m/Y H:i:s') }} • {{ strtoupper($log->level) }} • {{ $log->step }}</p>
                        <p class="mt-1 text-sm text-slate-800">{{ $log->message }}</p>
                        @if ($log->context)
                            <pre class="mt-3 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-slate-500">Sem logs para este evento.</p>
                @endforelse
            </div>
        </section>
    </section>
@endsection
