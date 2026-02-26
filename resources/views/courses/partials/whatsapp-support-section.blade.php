@php
    $supportWhatsappContact = $supportWhatsappContact ?? null;
@endphp

@if (is_array($supportWhatsappContact) && ! empty($supportWhatsappContact['link']))
    <section class="lp-section py-8">
        <div class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-white via-emerald-50/70 to-sky-50/60 p-5 shadow-sm md:p-6">
            <div class="grid gap-4 md:grid-cols-[1.2fr_0.8fr] md:items-center">
                <div class="space-y-3">
                    <div class="inline-flex items-center rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">
                        Atendimento no WhatsApp
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-display text-edux-primary">Precisa tirar dúvidas antes de começar?</h2>
                        <p class="text-sm leading-6 text-slate-700 md:text-base">
                            Nossa equipe pode ajudar com dúvidas sobre matrícula, acesso à plataforma e informações do curso.
                        </p>
                    </div>

                    <div class="grid gap-2 text-sm text-slate-700">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-emerald-600">✓</span>
                            <span>Atendimento para dúvidas de matrícula e acesso</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-emerald-600">✓</span>
                            <span>Suporte para começar pelo celular ou computador</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-emerald-600">✓</span>
                            <span>Pagamento seguro e liberação conforme confirmação</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Canal de atendimento</p>
                        <p class="mt-1 text-base font-black text-slate-900">
                            {{ $supportWhatsappContact['label'] ?? 'WhatsApp de atendimento' }}
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $supportWhatsappContact['whatsapp'] ?? '-' }}</p>

                        @if (! empty($supportWhatsappContact['description']))
                            <p class="mt-2 text-sm leading-5 text-slate-500">{{ $supportWhatsappContact['description'] }}</p>
                        @endif

                        <p class="mt-2 text-xs text-slate-400">
                            @if (! empty($supportWhatsappContact['is_rotating']))
                                Este curso usa rotatividade entre números ativos de atendimento.
                            @else
                                Este curso usa um número de atendimento específico.
                            @endif
                        </p>
                    </div>

                    <a
                        href="{{ $supportWhatsappContact['link'] }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex min-h-[52px] w-full items-center justify-center rounded-2xl bg-[#25D366] px-4 py-3 text-center text-sm font-black text-white shadow-[0_12px_30px_-18px_rgba(37,211,102,0.9)] transition hover:brightness-95"
                    >
                        Falar no WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>
@endif

