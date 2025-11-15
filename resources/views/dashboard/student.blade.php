@extends('layouts.app')

@section('title', 'Área do aluno')

@section('content')
    <div x-data="{ tab: 'painel' }" class="space-y-6">
        <nav class="rounded-card bg-white p-3 shadow-card flex flex-wrap gap-2 text-sm font-semibold text-edux-primary">
            <template x-for="(item, key) in [
                { id: 'painel', label: 'Painel' },
                { id: 'meus', label: 'Meus cursos' },
                { id: 'vitrine', label: 'Vitrine' },
                { id: 'notificacoes', label: 'Notificações' },
                { id: 'suporte', label: 'Suporte' },
            ]" :key="item.id">
                <button type="button"
                    class="rounded-full px-4 py-2 transition"
                    :class="tab === item.id ? 'bg-edux-primary text-white shadow' : 'bg-edux-background text-edux-primary'"
                    x-text="item.label"
                    @click="tab = item.id"></button>
            </template>
        </nav>

        <div x-show="tab === 'painel'" x-cloak>
            <livewire:student.panel-summary :user-id="$user->id" />
        </div>

        <div x-show="tab === 'meus'" x-cloak>
            <livewire:student.dashboard :user-id="$user->id" />
        </div>

        <div x-show="tab === 'vitrine'" x-cloak>
            <livewire:student.catalog /> 
        </div>

        <div x-show="tab === 'notificacoes'" x-cloak>
            <livewire:student.notifications-feed />
        </div>

        <div x-show="tab === 'suporte'" x-cloak>
            <section class="space-y-4">
                <div class="rounded-card bg-white p-6 shadow-card">
                    <p class="text-sm uppercase tracking-wide text-edux-primary">Suporte</p>
                    <h2 class="text-2xl font-display text-edux-primary">Como podemos ajudar?</h2>
                    <p class="text-slate-600 text-sm">Entre nos nossos canais oficiais para tirar dúvidas e acompanhar novidades.</p>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <a href="{{ config('services.support.instagram') }}" target="_blank" rel="noopener" class="edux-btn flex items-center justify-center gap-2">
                            📷 Instagram
                        </a>
                        <a href="{{ config('services.support.whatsapp') }}" target="_blank" rel="noopener" class="edux-btn flex items-center justify-center gap-2">
                            💬 Grupo no WhatsApp
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
