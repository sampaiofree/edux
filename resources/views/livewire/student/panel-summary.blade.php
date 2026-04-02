<section class="space-y-6">
    @if ($pushEnabled)
        <div
            data-onesignal-prompt-card
            class="rounded-2xl border border-edux-line/60 bg-white p-4 shadow-sm"
        >
            <div class="flex items-start gap-3">
                <span class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-edux-background text-edux-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.172V11a6 6 0 10-12 0v3.172a2 2 0 01-.586 1.414L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </span>
                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wide text-edux-primary">Notificações</p>
                    <h2 class="text-lg font-bold text-slate-900">Receba avisos importantes</h2>
                    <p data-onesignal-status class="text-sm text-slate-600">
                        Ative para receber novidades sobre aulas, recados e atualizações do seu curso.
                    </p>
                </div>
            </div>

            <button
                type="button"
                data-onesignal-prompt-trigger="1"
                class="edux-btn mt-4"
            >
                Ativar notificações
            </button>
        </div>
    @endif

    <div class="mb-8">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <a href="{{ route('dashboard', ['tab' => 'cursos']) }}" wire:navigate class="flex flex-col items-center gap-2 rounded-2xl border border-edux-line/60 bg-white p-4 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-edux-background text-edux-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 6.75h16M4 12h16M6 17.25h8" />
                    </svg>
                </span>
                <span class="text-sm font-semibold">Meus cursos</span> 
            </a>

            <a href="{{ route('dashboard', ['tab' => 'vitrine']) }}" wire:navigate class="flex flex-col items-center gap-2 rounded-2xl border border-edux-line/60 bg-white p-4 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-edux-background text-edux-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M6 6h12l-1 12H7L6 6zM9 6V4a3 3 0 016 0v2" />
                    </svg>
                </span>
                <span class="text-sm font-semibold">+Cursos</span>
            </a>

            <a href="{{ route('dashboard', ['tab' => 'notificacoes']) }}" wire:navigate class="flex flex-col items-center gap-2 rounded-2xl border border-edux-line/60 bg-white p-4 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-edux-background text-edux-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.172V11a6 6 0 10-12 0v3.172a2 2 0 01-.586 1.414L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </span>
                <span class="text-sm font-semibold">Notificacoes</span>
            </a>

            <a href="{{ route('dashboard', ['tab' => 'suporte']) }}" wire:navigate class="flex flex-col items-center gap-2 rounded-2xl border border-edux-line/60 bg-white p-4 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-edux-background text-edux-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 21l-8-4.5V7.5L12 3l8 4.5v9L12 21z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 11v2m0-6v.01" />
                    </svg>
                </span>
                <span class="text-sm font-semibold">Suporte</span>
            </a>

            <a href="{{ route('dashboard', ['tab' => 'conta']) }}" wire:navigate class="flex flex-col items-center gap-2 rounded-2xl border border-edux-line/60 bg-white p-4 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-edux-background text-edux-primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 12a4 4 0 100-8 4 4 0 000 8zM4.5 20a7.5 7.5 0 0115 0" />
                    </svg>
                </span>
                <span class="text-sm font-semibold">Conta</span>
            </a>
        </div>
    </div>
</section>
