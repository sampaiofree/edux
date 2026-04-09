<div @class([$class ?? null])>
    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-edux-primary">Passo 2</p>
    <h1 class="mt-2 font-display text-3xl text-edux-primary">Digite o código</h1>
    <p class="mt-2 text-sm leading-6 text-slate-600">
        Enviamos um código para <span class="font-semibold text-slate-800">{{ $email }}</span>.
    </p>

    <form method="POST" action="{{ route('signup.code.verify') }}" class="mt-6 space-y-4">
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

    <form method="POST" action="{{ route('signup.resend') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full rounded-xl border border-edux-line px-4 py-3 text-sm font-semibold text-edux-primary transition hover:bg-edux-background">
            Reenviar código
        </button>
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('signup.create') }}" class="text-sm font-semibold text-edux-primary hover:underline">
            Usar outro e-mail
        </a>
    </div>
</div>
