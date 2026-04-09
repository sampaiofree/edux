<div @class([$class ?? null])>
    <h1 class="font-display text-3xl text-edux-primary">Crie sua conta</h1>
    <p class="mt-2 text-sm leading-6 text-slate-600">
        Informe seu nome e e-mail para receber o código de ativação.
    </p>

    <form method="POST" action="{{ route('signup.store') }}" class="mt-6 space-y-4">
        @csrf
        <label class="space-y-2 text-sm font-semibold text-slate-600">
            <span>Nome</span>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
        </label>

        <label class="space-y-2 text-sm font-semibold text-slate-600">
            <span>E-mail</span>
            <input type="email" name="email" value="{{ old('email') }}" required
                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
        </label>

        <button type="submit" class="edux-btn w-full">
            Receber código
        </button>
    </form>

    <div class="mt-4 text-center text-sm text-slate-600">
        Já tem conta?
        <a href="{{ route('login') }}" class="font-semibold text-edux-primary hover:underline">
            Entrar
        </a>
    </div>
</div>
