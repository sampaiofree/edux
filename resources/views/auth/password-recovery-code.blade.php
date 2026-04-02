@extends('layouts.app')

@section('title', 'Digite o código')

@section('content')
    <div class="mx-auto max-w-md rounded-card bg-white p-8 shadow-card">
        <p class="text-xs font-semibold uppercase tracking-wide text-edux-primary">Passo 2</p>
        <h1 class="mt-2 font-display text-3xl text-edux-primary">Digite o código</h1>
        <p class="mt-2 text-sm text-slate-600">
            Confira o e-mail <span class="font-semibold text-slate-800">{{ $email }}</span> e digite o código que você recebeu.
        </p>

        <form method="POST" action="{{ route('password.recovery.code.verify') }}" class="mt-6 space-y-4">
            @csrf
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Código</span>
                <input
                    type="text"
                    name="code"
                    value="{{ old('code') }}"
                    required
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    autocomplete="one-time-code"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 text-center text-2xl tracking-[0.35em] focus:border-edux-primary focus:ring-edux-primary/30"
                >
            </label>

            <button type="submit" class="edux-btn w-full">
                Confirmar código
            </button>
        </form>

        <form method="POST" action="{{ route('password.recovery.resend') }}" class="mt-4">
            @csrf
            <button type="submit" class="w-full rounded-xl border border-edux-line px-4 py-3 text-sm font-semibold text-edux-primary transition hover:bg-edux-background">
                Reenviar código
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('password.recovery.request') }}" class="text-sm font-semibold text-edux-primary hover:underline">
                Usar outro e-mail
            </a>
        </div>
    </div>
@endsection
