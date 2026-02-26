@extends('layouts.app')

@section('title', 'Editar WhatsApp de atendimento')

@section('content')
    <section class="space-y-6">
        <header class="rounded-card bg-white p-6 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-edux-primary">Atendimento</p>
                <h1 class="font-display text-3xl text-edux-primary">Editar WhatsApp de atendimento</h1>
                <p class="text-slate-600 text-sm">Atualize identificação, número, ordem e status do canal de atendimento.</p>
            </div>
            <a href="{{ route('admin.support-whatsapp.index') }}" class="edux-btn bg-white text-edux-primary">
                Voltar para a lista
            </a>
        </header>

        <form method="POST" action="{{ route('admin.support-whatsapp.update', $supportWhatsappNumber) }}" class="rounded-card bg-white p-6 shadow-card space-y-5">
            @csrf
            @method('PUT')

            @include('admin.support-whatsapp-numbers.form', ['supportWhatsappNumber' => $supportWhatsappNumber])

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="edux-btn">Salvar alterações</button>
                <a href="{{ route('admin.support-whatsapp.index') }}" class="edux-btn bg-white text-edux-primary">Cancelar</a>
            </div>
        </form>
    </section>
@endsection

