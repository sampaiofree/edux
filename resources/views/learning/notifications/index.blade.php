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
            <h2 class="text-xl font-display text-edux-primary">Receba avisos no navegador</h2>
            <p class="text-sm text-slate-600">
                Ative as notificacoes para receber avisos sobre aulas, recados e atualizacoes sem precisar ficar entrando na plataforma o tempo todo.
            </p>
            <p class="text-xs text-slate-500">
                Se voce fechou o pedido antes, pode ativar novamente por aqui quando quiser.
            </p>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Se voce estiver no iPhone:</p>
                <p class="mt-1">
                    abra este site no Safari, toque em Compartilhar, escolha <span class="font-semibold">Adicionar a Tela de Inicio</span> e depois abra pelo icone instalado para ativar as notificacoes.
                </p>
            </div>
            <button
                type="button"
                data-onesignal-manual-trigger="1"
                class="edux-btn"
                hidden
            >
                Ativar notificacoes
            </button>
        </section>

        <livewire:student.notifications-feed />
    </div>
@endsection
