@php
    $layout = auth()->check() && auth()->user()->isStudent()
        ? 'layouts.student'
        : 'layouts.app';
@endphp

@extends($layout)

@section('title', 'Minha conta')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Configuracoes</p>
            <h1 class="font-display text-3xl text-edux-primary">Perfil e seguranca</h1>
            <p class="text-slate-600">Atualize seus dados basicos, qualificacao e foto de perfil.</p>
        </header>

        <livewire:account.profile-form />
    </section>
@endsection


