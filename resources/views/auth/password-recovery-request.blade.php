@extends('layouts.app')

@section('title', 'Recuperar senha')

@section('content')
    <div class="mx-auto max-w-md rounded-card bg-white p-8 shadow-card">
        <p class="text-xs font-semibold uppercase tracking-wide text-edux-primary">Recuperar senha</p>
        <h1 class="mt-2 font-display text-3xl text-edux-primary">Receber código por e-mail</h1>
        <p class="mt-2 text-sm text-slate-600">Digite seu e-mail para receber um código e criar uma nova senha.</p>

        <form method="POST" action="{{ route('password.recovery.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            </label>

            <button type="submit" class="edux-btn w-full">
                Enviar código
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm font-semibold text-edux-primary hover:underline">
                Voltar para o login
            </a>
        </div>
    </div>
@endsection
