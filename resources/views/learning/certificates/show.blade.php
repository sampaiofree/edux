@extends('layouts.student')

@section('title', 'Certificado emitido')

@section('content')
    @php
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($publicUrl);
    @endphp

    <section class="space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <header class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Certificado emitido</p>
            <h1 class="font-display text-3xl text-edux-primary">{{ $course->title }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                Número <strong>{{ $certificate->number }}</strong>
                · Emitido em {{ $certificate->issued_at->format('d/m/Y') }}
            </p>
        </header>

        <div class="rounded-card bg-white p-6 shadow-card flex flex-wrap gap-3">
            <a href="{{ $imageUrl }}" class="edux-btn">Abrir imagem</a>
            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="edux-btn bg-white text-edux-primary">Abrir link público</a>
            <a href="{{ route('dashboard') }}" wire:navigate class="edux-btn bg-white text-edux-primary">Voltar para dashboard</a>
        </div>

        @if (auth()->user()->name_change_available)
            <div class="rounded-card border border-amber-200 bg-amber-50 p-4 text-amber-800">
                <p class="font-semibold">Nome incorreto?</p>
                <p class="text-sm">
                    Você ainda pode alterar o nome uma única vez para atualizar o certificado.
                    <a href="{{ route('account.edit') }}" wire:navigate class="font-semibold underline">Atualizar agora</a>.
                </p>
            </div>
        @endif

        <div class="space-y-4">
            <div class="rounded-3xl border border-edux-line bg-white p-4 shadow-card">
                {!! $frontContent ?? $certificate->front_content !!}
            </div>
            <div class="rounded-3xl border border-edux-line bg-white p-4 shadow-card">
                {!! $backContent ?? $certificate->back_content !!}
            </div>
        </div>

        <div class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center gap-6">
            <div class="flex-1 space-y-2">
                <p class="text-sm uppercase tracking-wide text-edux-primary">Validação pública</p>
                <p class="text-slate-600">
                    Qualquer pessoa pode confirmar a autenticidade deste certificado acessando:
                    <strong>{{ $publicUrl }}</strong>
                </p>
            </div>
            <div class="flex flex-col items-center gap-2">
                <img src="{{ $qrUrl }}" alt="QR code do certificado" class="h-40 w-40 rounded-2xl border border-edux-line bg-white p-3 shadow-card">
                <span class="text-xs text-slate-500 text-center">Escaneie para validar</span>
            </div>
        </div>
    </section>
@endsection
