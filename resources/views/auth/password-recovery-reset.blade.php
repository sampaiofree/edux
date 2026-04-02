@extends('layouts.app')

@section('title', 'Nova senha')

@section('content')
    <div class="mx-auto max-w-md rounded-card bg-white p-8 shadow-card">
        <p class="text-xs font-semibold uppercase tracking-wide text-edux-primary">Passo 3</p>
        <h1 class="mt-2 font-display text-3xl text-edux-primary">Crie sua nova senha</h1>
        <p class="mt-2 text-sm text-slate-600">
            Agora escolha uma nova senha para a conta <span class="font-semibold text-slate-800">{{ $email }}</span>.
        </p>

        <form method="POST" action="{{ route('password.recovery.update') }}" class="mt-6 space-y-4">
            @csrf
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Nova senha</span>
                <input type="password" name="password" required
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            </label>

            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Confirmar nova senha</span>
                <input type="password" name="password_confirmation" required
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            </label>

            <button type="submit" class="edux-btn w-full">
                Atualizar senha
            </button>
        </form>
    </div>
@endsection
