<div @class([$class ?? null])>
    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-edux-primary">Passo 3</p>
    <h1 class="mt-2 font-display text-3xl text-edux-primary">Crie sua senha</h1>
    <p class="mt-2 text-sm leading-6 text-slate-600">
        Conta: <span class="font-semibold text-slate-800">{{ $email }}</span>
    </p>

    <form method="POST" action="{{ route('signup.activate') }}" class="mt-6 space-y-4">
        @csrf
        <label class="space-y-2 text-sm font-semibold text-slate-600">
            <span>Senha</span>
            <input type="password" name="password" required
                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
        </label>

        <label class="space-y-2 text-sm font-semibold text-slate-600">
            <span>Confirmar senha</span>
            <input type="password" name="password_confirmation" required
                class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
        </label>

        <button type="submit" class="edux-btn w-full">
            Ativar conta
        </button>
    </form>
</div>
