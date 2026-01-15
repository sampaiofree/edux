@extends('layouts.student')

@php
    $tab = $initialTab ?? 'painel';
@endphp

@section('content')
    <div class="mx-auto w-full max-w-md space-y-4 px-4 pt-4 pb-20">
        @if ($tab === 'painel')
            <livewire:student.panel-summary :user-id="$user->id" />
        @endif

        @if ($tab === 'cursos')
            <livewire:student.dashboard :user-id="$user->id" />
        @endif

        @if ($tab === 'vitrine')
            <livewire:student.catalog />
        @endif

        @if ($tab === 'notificacoes')
            <livewire:student.notifications-feed />
        @endif

        @if ($tab === 'suporte')
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-blue-600">Suporte</p>
                <h2 class="text-xl font-bold text-gray-900">Como podemos ajudar?</h2>
                <p class="mt-1 text-sm text-gray-600">Fale nos canais oficiais para tirar dúvidas e acompanhar novidades.</p>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <a href="{{ config('services.support.instagram') }}" target="_blank" rel="noopener" class="rounded-xl bg-[#E8F1FD] py-3 px-4 text-center font-semibold text-[#1A73E8] shadow-sm">
                        Instagram
                    </a>
                    <a href="{{ config('services.support.whatsapp') }}" target="_blank" rel="noopener" class="rounded-xl bg-[#E9F9EC] py-3 px-4 text-center font-semibold text-green-700 shadow-sm">
                        Grupo no WhatsApp
                    </a>
                </div>
            </div>
        @endif

        @if ($tab === 'conta')
            <div class="flex items-center justify-between rounded-2xl bg-white p-4 shadow-sm">
                <div>
                    <p class="text-xs uppercase tracking-wide text-blue-600">Conta</p>
                    <p class="text-base font-bold text-gray-900">{{ $user->preferredName() }}</p>
                    <p class="text-sm text-gray-600">{{ $user->email }}</p>
                </div>
                <a href="{{ route('account.edit') }}" class="rounded-xl bg-[#FBC02D] px-3 py-2 font-bold text-black shadow">
                    Editar
                </a>
            </div>
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <p class="text-sm font-semibold text-gray-800">Precisa de ajuda?</p>
                <p class="mt-1 text-sm text-gray-600">Acesse suporte ou atualize seus dados acima.</p>
            </div>
        @endif
    </div>

    
@endsection
