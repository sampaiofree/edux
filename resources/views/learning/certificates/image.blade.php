@extends('layouts.student')

@section('title', 'Imagem do certificado')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm uppercase tracking-wide text-edux-primary">Imagem do certificado</p>
            <h1 class="font-display text-3xl text-edux-primary">{{ $course->title }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                Abra a imagem abaixo para visualizar ou compartilhar no app.
            </p>
        </header>

        <div class="rounded-card bg-white p-4 shadow-card">
            <img
                src="{{ $imageFileUrl }}"
                alt="Imagem do certificado {{ $course->title }}"
                class="w-full rounded-3xl border border-edux-line bg-white shadow-card"
                loading="eager"
            >
        </div>

        <div class="rounded-card bg-white p-6 shadow-card flex flex-wrap gap-3">
            <a href="{{ route('learning.courses.certificate.show', [$course, $certificate]) }}" wire:navigate class="edux-btn bg-white text-edux-primary">Voltar ao certificado</a>
            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="edux-btn bg-white text-edux-primary">Abrir link público</a>
        </div>
    </section>
@endsection
