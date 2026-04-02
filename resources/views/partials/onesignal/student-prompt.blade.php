<div
    class="fixed inset-0 z-[95] hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-[2px]"
    data-onesignal-prompt-root
    hidden
>
    <div
        data-onesignal-prompt-card
        class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80"
        role="dialog"
        aria-modal="true"
        aria-labelledby="onesignal-prompt-title"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-edux-background text-edux-primary">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.172V11a6 6 0 10-12 0v3.172a2 2 0 01-.586 1.414L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </span>
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.24em] text-edux-primary">Notificações</p>
                    <h2 id="onesignal-prompt-title" class="text-2xl font-display text-edux-primary">Quer receber avisos no navegador?</h2>
                    <p data-onesignal-status class="text-sm leading-6 text-slate-600" aria-live="polite">
                        Ative para receber avisos sobre aulas, recados e atualizações do seu curso.
                    </p>
                </div>
            </div>

            <button
                type="button"
                data-onesignal-prompt-dismiss="icon"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                aria-label="Fechar aviso de notificações"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 0 1 1.06 0L10 7.88l2.66-2.66a.75.75 0 1 1 1.06 1.06L11.06 8.94l2.66 2.66a.75.75 0 1 1-1.06 1.06L10 10l-2.66 2.66a.75.75 0 0 1-1.06-1.06l2.66-2.66-2.66-2.66a.75.75 0 0 1 0-1.06Z" />
                </svg>
            </button>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <button
                type="button"
                data-onesignal-prompt-trigger="1"
                class="edux-btn w-full justify-center"
            >
                Ativar notificações
            </button>
            <button
                type="button"
                data-onesignal-prompt-dismiss="secondary"
                class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                Agora não
            </button>
        </div>
    </div>
</div>
