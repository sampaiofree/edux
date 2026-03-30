@extends('layouts.student')

@section('title', 'Minhas notificacoes')

@section('content')
    <div class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card space-y-2">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Notificacoes</p>
            <h1 class="font-display text-3xl text-edux-primary">Mensagens do EduX</h1>
            <p class="text-sm text-slate-600">Veja todo o historico de avisos, novidades e alertas enviados para voce.</p>
        </header>

        <section class="rounded-card bg-white p-6 shadow-card space-y-3">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Notificacoes push</p>
            <h2 class="text-xl font-display text-edux-primary">Receba avisos pelo app da sua escola</h2>
            <p class="text-sm text-slate-600">
                As notificacoes em tempo real sao enviadas pelo app oficial da escola. Aqui voce continua encontrando todo o historico das mensagens publicadas.
            </p>
            <p class="text-xs text-slate-500">
                Se estiver usando o app iOS ou Android, confirme nas configuracoes do aparelho que as notificacoes estao permitidas para esta escola.
            </p>
        </section>

        <livewire:student.notifications-feed />
    </div>
@endsection
