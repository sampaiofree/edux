@extends('layouts.student')

@section('title', 'Minhas notificacoes')

@section('content')
    <div class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card space-y-2">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Notificacoes</p>
            <h1 class="font-display text-3xl text-edux-primary">Mensagens do EduX</h1>
            <p class="text-sm text-slate-600">Veja todo o historico de avisos, novidades e alertas enviados para voce.</p>
        </header>

        <livewire:student.notifications-feed />
    </div>
@endsection
