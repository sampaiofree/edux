@extends('layouts.app')

@section('title', 'Entrar')

@section('content')
    <div class="mx-auto max-w-md rounded-card bg-white p-8 shadow-card">
        <h1 class="font-display text-3xl text-edux-primary">Acesse sua conta</h1>
       
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            </label>

            <label class="space-y-2 text-sm font-semibold text-slate-600">
                <span>Senha</span>
                <input type="password" name="password" required
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
            </label>

            <div class="text-right">
                <a href="{{ route('password.recovery.request') }}" class="text-sm font-semibold text-edux-primary hover:underline">
                    Recuperar senha
                </a>
            </div>

            <input type="hidden" name="remember" value="0">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    @checked((string) old('remember', config('auth.remember_by_default', true) ? '1' : '0') === '1')
                    class="rounded border-edux-line text-edux-primary focus:ring-edux-primary/30"
                >
                <span>Lembrar sessão</span>
            </label>

            <button type="submit" class="edux-btn w-full">
                👉 Entrar
            </button>
        </form>
    </div>
@endsection
