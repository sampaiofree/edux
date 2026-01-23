@extends('layouts.student')

@section('title', 'Minhas notificacoes')

@section('content')
    <div class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card space-y-2">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Notificacoes</p>
            <h1 class="font-display text-3xl text-edux-primary">Mensagens do EduX</h1>
            <p class="text-sm text-slate-600">Veja todo o historico de avisos, novidades e alertas enviados para voce.</p>
        </header>

        <section
            class="rounded-card bg-white p-6 shadow-card space-y-3"
            data-push-manager
            data-vapid-key="{{ config('webpush.vapid.public_key') }}"
            data-subscribe-url="{{ route('push.subscribe') }}"
            data-unsubscribe-url="{{ route('push.unsubscribe') }}"
        >
            <p class="text-sm uppercase tracking-wide text-edux-primary">Notificacoes push</p>
            <h2 class="text-xl font-display text-edux-primary">Ative avisos no navegador</h2>
            <p class="text-sm text-slate-600">Receba alertas mesmo com o navegador fechado.</p>
            <div class="flex flex-wrap gap-3">
                <button type="button" class="edux-btn" data-push-subscribe>Ativar notificacoes</button>
                <button type="button" class="edux-btn bg-white text-edux-primary hidden" data-push-unsubscribe>Desativar</button>
            </div>
            <p class="text-xs text-slate-500" data-push-status></p>
        </section>

        <livewire:student.notifications-feed />
    </div>
@endsection
