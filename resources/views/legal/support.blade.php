@extends('layouts.public-lesson')

@section('title', 'Suporte')

@section('content')
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[28px] bg-gradient-to-br from-emerald-950 via-slate-900 to-slate-800 px-6 py-8 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200/80">Suporte</p>
            <h1 class="mt-3 font-['Poppins'] text-3xl font-bold leading-tight">
                Atendimento da {{ $schoolName }}
            </h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                Esta página reúne os canais públicos de atendimento da escola ativa no domínio
                <span class="font-semibold text-white">{{ $displayDomain }}</span>.
            </p>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.2fr,0.8fr]">
            <div class="space-y-4 rounded-[24px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Canais disponíveis</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Utilize os contatos abaixo para falar com a equipe da escola.
                    </p>
                </div>

                @if ($supportNumbers->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($supportNumbers as $supportNumber)
                            <a
                                href="{{ $supportNumber->whatsappLink() }}"
                                target="_blank"
                                rel="noreferrer"
                                class="block rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 transition hover:border-emerald-200 hover:bg-emerald-50"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ $supportNumber->label ?: 'WhatsApp de atendimento' }}
                                        </p>
                                        <p class="text-sm text-emerald-700">{{ $supportNumber->whatsapp }}</p>
                                        @if (filled($supportNumber->description))
                                            <p class="text-sm leading-6 text-slate-600">{{ $supportNumber->description }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white">
                                        WhatsApp
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                        Esta escola ainda não publicou números de WhatsApp de atendimento neste domínio.
                    </div>
                @endif
            </div>

            <aside class="space-y-4">
                <section class="rounded-[24px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Contato por e-mail</h2>
                    @if ($contactEmail)
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            @if ($contactName)
                                {{ $contactName }}<br>
                            @endif
                            <a href="mailto:{{ $contactEmail }}" class="font-medium text-cyan-700 underline-offset-4 hover:underline">
                                {{ $contactEmail }}
                            </a>
                        </p>
                    @else
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Esta escola ainda não configurou um e-mail público de atendimento.
                        </p>
                    @endif
                </section>

                <section class="rounded-[24px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Dados da escola</h2>
                    <dl class="mt-3 space-y-3 text-sm text-slate-600">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Escola</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $schoolName }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Domínio</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $displayDomain }}</dd>
                        </div>
                        @if ($settings->owner)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Responsável</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $settings->owner->name }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="rounded-[24px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Privacidade</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Para entender como os dados são tratados neste ambiente, consulte a política de privacidade da escola.
                    </p>
                    <a
                        href="{{ route('legal.privacy') }}"
                        class="mt-4 inline-flex rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                    >
                        Ver política de privacidade
                    </a>
                </section>
            </aside>
        </section>
    </div>
@endsection
