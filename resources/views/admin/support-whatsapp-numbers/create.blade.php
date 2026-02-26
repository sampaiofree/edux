@extends('layouts.app')

@section('title', 'Novo WhatsApp de atendimento')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Atendimento</p>
                <h1 class="font-display text-3xl text-edux-primary">Cadastrar WhatsApp de atendimento</h1>
                <p class="text-slate-600 text-sm">Adicione um número para uso em atendimento comercial, suporte ou orientação ao aluno.</p>
            </div>
            <a href="{{ route('admin.support-whatsapp.index') }}" class="edux-btn bg-white text-edux-primary">
                Voltar para a lista
            </a>
        </header>

        <form method="POST" action="{{ route('admin.support-whatsapp.store') }}" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf

            @include('admin.support-whatsapp-numbers.form', ['supportWhatsappNumber' => null])

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Salvar número</button>
                <a href="{{ route('admin.support-whatsapp.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection

