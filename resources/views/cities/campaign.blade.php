@extends('layouts.student')

@section('title', 'Liberação para ' . $cityDisplayName . ' • Cursos Profissionalizantes')
@section('hide_student_header', '1')
@section('hide_student_footer', '1')
@section('hide_student_bottom_nav', '1')
@section('student_main_classes', 'mx-auto max-w-6xl px-2 pt-4 pb-10 md:pt-6 md:pb-12')

@section('content')
    @php
        $countdownEndsAtLabel = \Carbon\Carbon::parse($countdownExpiresAtIso)->format('d/m/Y H:i');
        $coursesCountLabel = $coursesCount === 1 ? '1 curso disponível' : $coursesCount . ' cursos disponíveis';
    @endphp

    <article class="space-y-6 md:space-y-8">
        <section id="comunicado-cidade" class="city-campaign-section overflow-hidden rounded-3xl">
            @if ($isClosed)
                <div class="bg-edux-primary px-4 py-3 text-white md:px-5">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1">Iniciativa social independente</span>
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1">Sem vínculo com governo</span>
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1">Capacitação profissional online</span>
                    </div>
                </div>
            @endif

            @if (! $isClosed)
                <div class="flex flex-col overflow-hidden rounded-3xl border-2 border-slate-200 bg-white md:flex-row">
                    <div class="flex-1 bg-white p-8 md:p-12">
                        <div class="mb-8 flex flex-wrap items-center gap-2">
                            <span class="rounded bg-amber-400 px-3 py-1 text-[10px] font-black uppercase text-amber-950">
                                Exclusivo: {{ $cityDisplayName }}
                            </span>
                            <span class="rounded bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase text-slate-600">
                                {{ $coursesCountLabel }}
                            </span>
                        </div>

                        <p class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-edux-primary">
                            Liberação local de cursos profissionalizantes
                        </p>

                        <h1 class="mb-6 text-4xl font-black leading-none tracking-tighter text-slate-900 md:text-5xl">
                            PROFISSÃO AO SEU ALCANCE.
                        </h1>

                        <ul class="mb-8 space-y-4">
                            <li class="flex items-center gap-3 font-bold text-slate-700">
                                <span class="text-blue-600">✓</span> Inscrição simplificada pelo CPF
                            </li>
                            <li class="flex items-center gap-3 font-bold text-slate-700">
                                <span class="text-blue-600">✓</span> Certificado incluso no valor
                            </li>
                            <li class="flex items-center gap-3 font-bold text-slate-700">
                                <span class="text-blue-600">✓</span> Estude onde e quando quiser
                            </li>
                        </ul>

                        @if ($showEmploymentDisclaimer)
                            <div class="text-[11px] font-medium text-slate-400">
                                Aviso: Iniciativa social de capacitação online. Não garantimos emprego, garantimos o conhecimento para você conquistar um.
                            </div>
                        @endif
                    </div>

                    <div class="w-full bg-edux-primary p-8 text-white md:w-[380px] md:p-12">
                        <div class="mb-6 text-center">
                            <p class="text-xs font-bold uppercase opacity-70">Valor Social Liberado</p>
                            <div class="text-5xl font-black tracking-tighter md:text-6xl">
                                {{ $globalLowestCheckoutPriceLabel ?: 'Consultar' }}
                            </div>
                            <p class="mt-1 text-sm opacity-80">menor valor entre os cursos disponíveis</p>
                        </div>

                        <div class="mb-6 rounded-2xl border border-white/20 bg-white/10 p-3">
                            <div class="flex items-center justify-between gap-2 text-xs font-bold uppercase tracking-wide">
                                <span class="opacity-80">Liberação ativa</span>
                                <span class="rounded bg-emerald-300/20 px-2 py-1 text-emerald-100 ring-1 ring-emerald-200/20">
                                    {{ $countdownHours }}h
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-white/80">Encerra em {{ $countdownEndsAtLabel }}</p>

                            <div
                                class="mt-3 grid grid-cols-3 gap-2"
                                data-city-countdown
                                data-countdown-expires="{{ $countdownExpiresAtUnix }}"
                                data-countdown-reload-url="{{ request()->fullUrl() }}"
                            >
                                <div class="countdown-slot countdown-slot-soft">
                                    <span data-countdown-hours>00</span>
                                    <small>horas</small>
                                </div>
                                <div class="countdown-slot countdown-slot-soft">
                                    <span data-countdown-minutes>00</span>
                                    <small>min</small>
                                </div>
                                <div class="countdown-slot countdown-slot-soft">
                                    <span data-countdown-seconds>00</span>
                                    <small>seg</small>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <a
                                href="#cursos-cidade"
                                data-city-cta
                                data-city-cta-source="hero_primary"
                                data-city-cta-variant="hero_split_v1"
                                class="block w-full rounded-xl bg-white py-4 text-center text-sm font-black uppercase tracking-tighter text-edux-primary transition hover:bg-slate-100"
                            >
                                Escolher meu curso
                            </a>
                            <a
                                href="#como-funciona-cidade"
                                data-city-cta
                                data-city-cta-source="hero_secondary"
                                class="block w-full rounded-xl border border-white/30 bg-white/10 py-3 text-center text-xs font-bold uppercase tracking-wide text-white transition hover:bg-white/15"
                            >
                                Como funciona
                            </a>
                            <p class="text-center text-[10px] opacity-60">
                                O valor social é mantido por tempo limitado para residentes locais.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="grid gap-5 p-4 md:grid-cols-[1.1fr_0.9fr] md:p-6">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-rose-700 ring-1 ring-rose-200">
                                Janela encerrada
                            </span>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">
                                Liberação de {{ $countdownHours }}h finalizada
                            </span>
                        </div>

                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-edux-primary">Comunicado local para {{ $cityDisplayName }}</p>
                        <h1 class="font-display text-3xl leading-tight text-slate-900 md:text-4xl">
                            Inscrições encerradas para {{ $cityDisplayName }}
                        </h1>
                        <p class="text-base leading-7 text-slate-700">
                            A janela desta liberação foi encerrada. Você ainda pode entrar na lista de espera e acompanhar uma próxima abertura para {{ $cityDisplayName }}.
                        </p>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a
                                href="{{ $waitlistUrl }}"
                                data-waitlist-link
                                class="inline-flex min-h-[54px] items-center justify-center rounded-2xl bg-edux-primary px-5 py-3 text-center text-sm font-black text-white shadow-[0_16px_35px_-18px_rgba(26,115,232,0.85)] transition hover:opacity-95"
                            >
                                Entrar na lista de espera
                            </a>
                            <a
                                href="{{ $catalogUrl }}"
                                data-city-cta
                                data-city-cta-source="closed_catalog"
                                class="inline-flex min-h-[54px] items-center justify-center rounded-2xl border border-blue-200 bg-white px-5 py-3 text-center text-sm font-semibold text-edux-primary transition hover:bg-blue-50"
                            >
                                Ver catálogo de cursos
                            </a>
                        </div>

                        <p id="waitlist-feedback" class="hidden rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200">
                            Lista de espera em configuração. O link definitivo será definido em breve.
                        </p>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid gap-4 md:grid-cols-[1fr_220px] md:items-center">
                                <div>
                                    <p class="text-sm font-black text-slate-900">Não perca a próxima liberação</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        A lista de espera ajuda você a receber aviso quando uma nova janela for aberta para sua cidade.
                                    </p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white p-2">
                                    <svg viewBox="0 0 280 200" class="h-auto w-full" fill="none" aria-hidden="true">
                                        <rect x="20" y="22" width="240" height="156" rx="18" fill="#F8FAFC" stroke="#CBD5E1" />
                                        <path d="M100 44h80" stroke="#94A3B8" stroke-width="4" stroke-linecap="round" />
                                        <path d="M108 156h64" stroke="#94A3B8" stroke-width="4" stroke-linecap="round" />
                                        <path d="M112 56c0 26 28 28 28 44s-28 18-28 44" stroke="#F59E0B" stroke-width="6" stroke-linecap="round" />
                                        <path d="M168 56c0 26-28 28-28 44s28 18 28 44" stroke="#F59E0B" stroke-width="6" stroke-linecap="round" />
                                        <circle cx="214" cy="70" r="18" fill="#DBEAFE" />
                                        <path d="M206 70h16M214 62v16" stroke="#1D4ED8" stroke-width="3" stroke-linecap="round" />
                                        <rect x="46" y="52" width="42" height="96" rx="10" fill="#EFF6FF" stroke="#BFDBFE" />
                                        <path d="M56 76h22M56 92h22M56 108h16" stroke="#1A73E8" stroke-width="3" stroke-linecap="round" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status da liberação</p>
                                <h2 class="mt-1 text-lg font-black leading-tight text-slate-900">Consulta local encerrada</h2>
                            </div>
                            <x-ui.color-icon name="info" tone="amber" />
                        </div>

                        <div class="mt-4 space-y-2">
                            <div class="city-status-row">
                                <span>Situação</span>
                                <strong><span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-rose-700 ring-1 ring-rose-200">Encerrada</span></strong>
                            </div>
                            <div class="city-status-row">
                                <span>Cidade</span>
                                <strong>{{ $cityDisplayName }}</strong>
                            </div>
                            <div class="city-status-row">
                                <span>Cursos cadastrados</span>
                                <strong>{{ $coursesCount }}</strong>
                            </div>
                            <div class="city-status-row">
                                <span>Valor social a partir de</span>
                                <strong>{{ $globalLowestCheckoutPriceLabel ?: 'Consultar' }}</strong>
                            </div>
                        </div>

                        <ul class="mt-4 space-y-2 text-sm text-slate-700">
                            <li class="flex items-start gap-2">
                                <x-ui.color-icon name="users" tone="blue" size="sm" class="h-7 w-7 rounded-lg" />
                                <span>Você pode receber aviso de nova liberação.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <x-ui.color-icon name="hourglass" tone="amber" size="sm" class="h-7 w-7 rounded-lg" />
                                <span>A janela é limitada por tempo no seu acesso.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <x-ui.color-icon name="briefcase" tone="green" size="sm" class="h-7 w-7 rounded-lg" />
                                <span>Enquanto isso, você pode explorar o catálogo normal.</span>
                            </li>
                        </ul>
                    </aside>
                </div>
            @endif
        </section>

        @if (! $isClosed)
            <section id="como-funciona-cidade" class="city-campaign-section city-campaign-deferred rounded-3xl border border-edux-line/70 bg-white p-4 shadow-sm md:p-6">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Como funciona a liberação em {{ $cityDisplayName }}</h2>
                    <p class="text-sm text-slate-600 md:text-base">Passo a passo simples para você escolher um curso e seguir para a página oficial de matrícula.</p>
                </div>

                <ol class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white md:grid md:grid-cols-3 md:divide-x md:divide-slate-200">
                    <li class="border-b border-slate-200 p-4 md:border-b-0">
                        <div class="flex items-start gap-3">
                            <div class="relative shrink-0">
                                <x-ui.color-icon name="list-check" tone="blue" size="sm" />
                                <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-edux-primary px-1 text-[10px] font-black text-white">1</span>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900">Ver os cursos liberados</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Veja a lista abaixo com os cursos disponíveis para sua cidade.</p>
                            </div>
                        </div>
                    </li>
                    <li class="border-b border-slate-200 p-4 md:border-b-0">
                        <div class="flex items-start gap-3">
                            <div class="relative shrink-0">
                                <x-ui.color-icon name="shield-check" tone="green" size="sm" />
                                <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-edux-primary px-1 text-[10px] font-black text-white">2</span>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900">Abrir a página oficial do curso</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Cada botão leva para detalhes, conteúdo e opções de matrícula.</p>
                            </div>
                        </div>
                    </li>
                    <li class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="relative shrink-0">
                                <x-ui.color-icon name="book-open" tone="amber" size="sm" />
                                <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-edux-primary px-1 text-[10px] font-black text-white">3</span>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900">Escolher matrícula e começar</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Na página do curso você decide sua matrícula e inicia a preparação.</p>
                            </div>
                        </div>
                    </li>
                </ol>
            </section>

            <section id="cursos-cidade" class="city-campaign-section city-campaign-deferred py-4">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 md:text-2xl">Cursos Disponíveis</h2>
                        <p class="mt-1 text-sm text-slate-500">Lista local para {{ $cityDisplayName }}, organizada pelo menor valor social.</p>
                    </div>
                    <span class="rounded-full bg-slate-900 px-3 py-1 text-[10px] font-bold uppercase text-white">
                        {{ $coursesCount }} {{ $coursesCount === 1 ? 'Ativo' : 'Ativos' }}
                    </span>
                </div>

                @if ($courses->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($courses as $courseItem)
                            <article class="flex flex-col overflow-hidden rounded-[2rem] border-2 border-slate-100 bg-white transition hover:scale-[1.02] hover:shadow-xl">
                                <div class="relative h-32 w-full bg-slate-200">
                                    @if (!empty($courseItem['cover_image_url']))
                                        <img
                                            src="{{ $courseItem['cover_image_url'] }}"
                                            alt="{{ $courseItem['title'] }}"
                                            loading="lazy"
                                            decoding="async"
                                            fetchpriority="low"
                                            width="640"
                                            height="256"
                                            class="h-full w-full object-cover"
                                        >
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200">
                                            <x-ui.color-icon name="briefcase" tone="blue" class="h-12 w-12 rounded-2xl" />
                                        </div>
                                    @endif

                                    <div class="absolute right-3 top-3 rounded-lg bg-white/90 px-2 py-1 text-xs font-black italic text-slate-900 backdrop-blur-md">
                                        {{ $courseItem['lowest_checkout_price_label'] ?: 'Consultar' }}
                                    </div>
                                </div>

                                <div class="p-5">
                                    <h3 class="text-lg font-black leading-tight text-slate-900">{{ $courseItem['title'] }}</h3>

                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1">
                                            <x-ui.color-icon name="smartphone" tone="indigo" size="sm" class="h-5 w-5 rounded-md" />
                                            Online
                                        </span>
                                        @if (!empty($courseItem['duration_hours_label']))
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1">
                                                <x-ui.color-icon name="clock" tone="blue" size="sm" class="h-5 w-5 rounded-md" />
                                                {{ $courseItem['duration_hours_label'] }}h
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-xs italic text-slate-500 line-clamp-2">
                                        {{ $courseItem['summary'] ?: 'Curso profissionalizante com aulas online, conteúdo prático e valor social de matrícula.' }}
                                    </p>

                                    <a
                                        href="{{ $courseItem['course_url'] }}"
                                        data-city-cta
                                        data-city-cta-source="course_row"
                                        data-city-course-id="{{ $courseItem['id'] }}"
                                        data-city-course-slug="{{ $courseItem['slug'] }}"
                                        data-city-course-title="{{ $courseItem['title'] }}"
                                        data-city-course-price="{{ $courseItem['lowest_checkout_value'] ?? '' }}"
                                        data-city-course-price-label="{{ $courseItem['lowest_checkout_price_label'] ?? '' }}"
                                        data-city-course-position="{{ $loop->iteration }}"
                                        class="mt-4 flex w-full items-center justify-center rounded-2xl bg-slate-900 py-4 text-xs font-black uppercase tracking-widest text-white shadow-xl transition hover:bg-slate-800"
                                    >
                                        Ver detalhes
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                        Nenhum curso publicado está disponível no momento para listar nesta página.
                    </div>
                @endif
            </section>

            <section class="city-campaign-section city-campaign-deferred overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-0 md:grid-cols-[1.02fr_0.98fr]">
                    <div class="order-2 p-5 md:order-1 md:p-6">
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-200">
                            <x-ui.color-icon name="badge-check" tone="green" size="sm" class="h-6 w-6 rounded-md" />
                            Certificado
                        </div>

                        <h2 class="text-2xl font-black leading-tight text-slate-900 md:text-3xl">
                            Certificado para fortalecer seu currículo
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-slate-600 md:text-base">
                            Ao concluir o curso, você recebe certificado para comprovar seu aprendizado e apresentar melhor sua formação em processos seletivos.
                        </p>

                        <ul class="mt-4 space-y-2">
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <x-ui.color-icon name="check-circle" tone="green" size="sm" class="mt-0.5 h-6 w-6 rounded-md" />
                                <span>Ajuda a organizar e comprovar sua capacitação.</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <x-ui.color-icon name="briefcase" tone="blue" size="sm" class="mt-0.5 h-6 w-6 rounded-md" />
                                <span>Fortalece sua apresentação profissional junto ao currículo.</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <x-ui.color-icon name="smartphone" tone="indigo" size="sm" class="mt-0.5 h-6 w-6 rounded-md" />
                                <span>Você faz tudo online e acompanha pela página do curso.</span>
                            </li>
                        </ul>

                        <p class="mt-4 text-xs leading-5 text-slate-500">
                            Consulte os detalhes e regras do certificado na página oficial do curso escolhido.
                        </p>

                        <a
                            href="#cursos-cidade"
                            data-city-cta
                            data-city-cta-source="certificate_section"
                            class="mt-4 inline-flex min-h-[46px] items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800"
                        >
                            Escolher curso com certificado
                        </a>
                    </div>

                    <div class="order-1 bg-slate-100 md:order-2">
                        <img
                            src="https://jempreendedor.com/img/home_page/certificadoNovo2.webp"
                            alt="Exemplo de certificado"
                            loading="lazy"
                            decoding="async"
                            fetchpriority="low"
                            width="1200"
                            height="900"
                            class="h-full w-full object-cover"
                        >
                    </div>
                </div>
            </section>

            <section class="city-campaign-section city-campaign-deferred rounded-3xl border border-amber-100 bg-gradient-to-br from-white via-white to-amber-50/50 p-4 shadow-sm md:p-6">
                <div class="grid gap-4 md:grid-cols-[1.1fr_0.9fr] md:items-start">
                    <div>
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-800 ring-1 ring-amber-200">
                            <x-ui.color-icon name="file-text" tone="amber" size="sm" class="h-6 w-6 rounded-md" />
                            Carta de estágio
                        </div>

                        <h2 class="text-2xl font-black leading-tight text-slate-900 md:text-3xl">
                            Carta de estágio para apoiar sua apresentação profissional
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-slate-600 md:text-base">
                            Além do curso e do certificado, a carta de estágio pode ajudar você a apresentar seu interesse e dedicação aos estudos ao buscar oportunidades.
                        </p>

                        <div class="mt-4 space-y-2">
                            <div class="flex items-start gap-3 rounded-2xl bg-white p-3 ring-1 ring-amber-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">1</span>
                                <p class="text-sm leading-6 text-slate-700">Documento complementar para anexar com currículo e candidaturas.</p>
                            </div>
                            <div class="flex items-start gap-3 rounded-2xl bg-white p-3 ring-1 ring-amber-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">2</span>
                                <p class="text-sm leading-6 text-slate-700">Ajuda a mostrar compromisso com sua formação e preparação.</p>
                            </div>
                            <div class="flex items-start gap-3 rounded-2xl bg-white p-3 ring-1 ring-amber-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">3</span>
                                <p class="text-sm leading-6 text-slate-700">Pode fortalecer sua apresentação, mas não garante vaga nem contratação.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-amber-100 bg-white p-4 shadow-sm">
                        <div class="overflow-hidden rounded-2xl border border-amber-100 bg-amber-50">
                            @if (!empty($cartaEstagioImageUrl))
                                <img
                                    src="{{ $cartaEstagioImageUrl }}"
                                    alt="Modelo de carta de estágio"
                                    loading="lazy"
                                    decoding="async"
                                    fetchpriority="low"
                                    width="1200"
                                    height="900"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="flex min-h-[180px] items-center justify-center bg-gradient-to-br from-amber-50 to-slate-50 px-4 text-center text-sm font-semibold text-slate-500">
                                    Imagem da carta de estágio em configuração
                                </div>
                            @endif
                        </div>

                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Resumo rápido</p>

                        <div class="mt-3 space-y-2">
                            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <x-ui.color-icon name="briefcase" tone="green" size="sm" class="h-7 w-7 rounded-lg" />
                                <div>
                                    <p class="text-sm font-black text-slate-900">Ajuda na apresentação</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Útil para complementar seu material em candidaturas.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <x-ui.color-icon name="info" tone="amber" size="sm" class="h-7 w-7 rounded-lg" />
                                <div>
                                    <p class="text-sm font-black text-slate-900">Não é promessa de emprego</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">A contratação depende do processo seletivo de cada empresa.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <x-ui.color-icon name="list-check" tone="blue" size="sm" class="h-7 w-7 rounded-lg" />
                                <div>
                                    <p class="text-sm font-black text-slate-900">Confira na página do curso</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Veja regras, detalhes e orientações do curso que você escolher.</p>
                                </div>
                            </div>
                        </div>

                        <a
                            href="#cursos-cidade"
                            data-city-cta
                            data-city-cta-source="carta_estagio_section"
                            class="mt-4 inline-flex min-h-[46px] w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
                        >
                            Ver cursos e detalhes
                        </a>
                    </div>
                </div>
            </section>

            <section class="city-campaign-section city-campaign-deferred rounded-2xl border border-blue-100 bg-blue-50/50 p-4 md:p-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-black text-slate-900">Comunicado social independente para {{ $cityDisplayName }}</p>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 ring-1 ring-slate-200">
                                <x-ui.color-icon name="building" tone="blue" size="sm" class="h-6 w-6 rounded-md" />
                                Sem vínculo com governo
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 ring-1 ring-slate-200">
                                <x-ui.color-icon name="wallet" tone="amber" size="sm" class="h-6 w-6 rounded-md" />
                                Valor social
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 ring-1 ring-slate-200">
                                <x-ui.color-icon name="briefcase" tone="green" size="sm" class="h-6 w-6 rounded-md" />
                                Preparação profissional
                            </span>
                        </div>
                    </div>

                    <a
                        href="#cursos-cidade"
                        data-city-cta
                        data-city-cta-source="footer_primary"
                        class="inline-flex min-h-[48px] items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-edux-primary transition hover:bg-blue-50"
                    >
                        Escolher curso agora
                    </a>
                </div>
            </section>
        @else
            <section class="city-campaign-section city-campaign-deferred rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
                <div class="space-y-2">
                    <h2 class="text-xl font-display text-edux-primary">O que fazer agora</h2>
                    <p class="text-sm text-slate-600 md:text-base">Entre na lista de espera para acompanhar uma nova abertura ou siga para o catálogo geral enquanto aguarda.</p>
                </div>

                <ul class="mt-4 space-y-2 text-sm text-slate-700">
                    <li class="flex items-start gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                        <x-ui.color-icon name="users" tone="blue" size="sm" class="h-7 w-7 rounded-lg" />
                        <span>Você pode receber aviso de nova liberação para {{ $cityDisplayName }}.</span>
                    </li>
                    <li class="flex items-start gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                        <x-ui.color-icon name="hourglass" tone="amber" size="sm" class="h-7 w-7 rounded-lg" />
                        <span>A janela é limitada por tempo e fecha automaticamente no seu acesso.</span>
                    </li>
                    <li class="flex items-start gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                        <x-ui.color-icon name="briefcase" tone="green" size="sm" class="h-7 w-7 rounded-lg" />
                        <span>Você ainda pode explorar cursos pelo catálogo e voltar depois.</span>
                    </li>
                </ul>
            </section>
        @endif
    </article>
@endsection

@push('styles')
    <style>
        .city-campaign-section {
            position: relative;
        }

        .city-campaign-section + .city-campaign-section::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: -1rem;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(203, 213, 225, 0.95), transparent);
        }

        .city-campaign-deferred {
            content-visibility: auto;
            contain-intrinsic-size: 720px;
        }

        #cursos-cidade.city-campaign-deferred {
            contain-intrinsic-size: 1280px;
        }

        .city-status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            border-radius: 0.9rem;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #fff;
            padding: 0.65rem 0.8rem;
            font-size: 0.85rem;
            line-height: 1.25rem;
        }

        .city-status-row > span {
            color: #475569;
            font-weight: 600;
        }

        .city-status-row > strong {
            color: #0f172a;
            font-weight: 800;
            text-align: right;
        }

        .countdown-slot {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.15rem;
            min-height: 4.25rem;
            border-radius: 0.9rem;
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        .countdown-slot span {
            font-weight: 900;
            font-size: 1.25rem;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .countdown-slot small {
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.62rem;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.72);
        }

        .countdown-slot-soft {
            min-height: 3.75rem;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(2px);
        }

        .countdown-slot-soft small {
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.56rem;
        }

        @media (min-width: 768px) {
            .countdown-slot span {
                font-size: 1.5rem;
            }
        }
    </style>
@endpush

@push('scripts')
    @if ($metaAdsPixelId !== '')
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];f.__eduxMetaPixelLoader=function(){
            if(f.__eduxMetaPixelRequested)return;f.__eduxMetaPixelRequested=!0;t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}}(
                window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js'
            );

            fbq('init', @js($metaAdsPixelId));
            fbq('track', 'PageView');

            (() => {
                const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                const saveData = Boolean(connection && connection.saveData);
                const effectiveType = String(connection?.effectiveType || '').toLowerCase();
                const isSlowConnection = effectiveType.includes('2g') || effectiveType.includes('3g');
                const loadPixel = () => window.__eduxMetaPixelLoader?.();

                const loadOnce = () => {
                    if (window.__eduxMetaPixelRequested) return;
                    loadPixel();
                };

                if (saveData || isSlowConnection) {
                    window.addEventListener('pointerdown', loadOnce, { once: true, passive: true });
                    window.addEventListener('touchstart', loadOnce, { once: true, passive: true });
                    window.addEventListener('scroll', loadOnce, { once: true, passive: true });
                    window.addEventListener('keydown', loadOnce, { once: true });
                    window.setTimeout(loadOnce, saveData ? 3500 : 2200);
                    return;
                }

                if ('requestIdleCallback' in window) {
                    window.requestIdleCallback(loadOnce, { timeout: 1200 });
                    return;
                }

                window.setTimeout(loadOnce, 400);
            })();
        </script>
    @endif

    <script>
        (() => {
            const campaignMeta = @js([
                'page_type' => $cityTrackingPageType ?? 'city_campaign_catalog',
                'city_slug' => $citySlug,
                'city_name' => $cityDisplayName,
                'window_status' => $isClosed ? 'closed' : 'open',
                'courses_count' => $coursesCount,
            ]);
            const campaignState = @js([
                'is_closed' => $isClosed,
                'is_fresh_countdown' => $isFreshCountdown,
                'seconds_remaining' => $secondsRemaining,
                'expires_at_unix' => $countdownExpiresAtUnix,
            ]);
            const rawSearchParams = new URLSearchParams(window.location.search);

            const queryParams = {};
            for (const [key, value] of rawSearchParams.entries()) {
                if (!key || value === '') continue;
                const safeKey = String(key).toLowerCase().replace(/[^a-z0-9_]/g, '_').slice(0, 40);
                if (!safeKey) continue;
                queryParams[`qp_${safeKey}`] = String(value).slice(0, 120);
            }

            if (typeof window.cityMetaTrack !== 'function') {
                window.cityMetaTrack = function cityMetaTrack(eventName, extra = {}) {
                    window.eduxFirstPartyTrack?.(
                        eventName,
                        { ...campaignMeta, ...queryParams, ...extra },
                        { source: 'meta_custom', pageType: campaignMeta.page_type }
                    );

                    if (!window.fbq) return;
                    try {
                        window.fbq('trackCustom', eventName, { ...campaignMeta, ...queryParams, ...extra });
                    } catch (_) {}
                };
            }

            if (typeof window.cityMetaTrackStandard !== 'function') {
                window.cityMetaTrackStandard = function cityMetaTrackStandard(eventName, params = {}) {
                    window.eduxFirstPartyTrack?.(
                        eventName,
                        { ...campaignMeta, ...queryParams, ...params },
                        { source: 'meta_standard', pageType: campaignMeta.page_type }
                    );

                    if (!window.fbq) return;
                    try {
                        window.fbq('track', eventName, params);
                    } catch (_) {}
                };
            }

            const shouldSkipHref = (href) => {
                if (!href) return true;
                const value = String(href).trim().toLowerCase();
                return value.startsWith('#') || value.startsWith('javascript:') || value.startsWith('mailto:') || value.startsWith('tel:');
            };

            const shouldKeepDefaultNavigation = (event, link) => {
                return Boolean(
                    event.defaultPrevented ||
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.altKey ||
                    (typeof event.button === 'number' && event.button !== 0) ||
                    ((link.getAttribute('target') || '').toLowerCase() === '_blank')
                );
            };

            const withViewContentPrefiredFlag = (href) => {
                if (shouldSkipHref(href)) return href;

                try {
                    const url = new URL(href, window.location.href);
                    if (!url.searchParams.has('edux_vc_prefired')) {
                        url.searchParams.set('edux_vc_prefired', '1');
                    }
                    return url.toString();
                } catch (_) {
                    return href;
                }
            };

            const buildCourseViewContentPayload = (link) => {
                const courseId = String(link.dataset.cityCourseId || '').trim();
                const courseSlug = String(link.dataset.cityCourseSlug || '').trim();
                const courseTitle = String(link.dataset.cityCourseTitle || '').trim();
                const coursePosition = Number(link.dataset.cityCoursePosition || 0) || undefined;
                const coursePrice = Number(link.dataset.cityCoursePrice || 0);
                const hasPrice = Number.isFinite(coursePrice) && coursePrice > 0;
                const contentId = courseId || courseSlug || courseTitle || 'course';

                const payload = {
                    content_name: courseTitle || 'Curso',
                    content_type: 'product',
                    content_category: 'course',
                    content_ids: [contentId],
                    city_slug: campaignMeta.city_slug,
                    city_name: campaignMeta.city_name,
                    source_page: campaignMeta.page_type,
                    course_position: coursePosition,
                };

                if (hasPrice) {
                    payload.currency = 'BRL';
                    payload.value = coursePrice;
                    payload.contents = [{
                        id: contentId,
                        quantity: 1,
                        item_price: coursePrice,
                    }];
                }

                return payload;
            };

            const bindCityCtas = () => {
                document.querySelectorAll('[data-city-cta]').forEach((link) => {
                    if (link.dataset.cityCtaBound === '1') return;
                    link.dataset.cityCtaBound = '1';

                    link.addEventListener('click', (event) => {
                        const ctaSource = link.dataset.cityCtaSource || 'city_cta';
                        const href = link.getAttribute('href') || '';
                        const isCourseRow = ctaSource === 'course_row' || ctaSource === 'course_row_v2';

                        if (isCourseRow) {
                            window.cityMetaTrackStandard('ViewContent', buildCourseViewContentPayload(link));
                        }

                        window.cityMetaTrack('CityCampaignCtaClick', {
                            cta_source: ctaSource,
                            course_id: link.dataset.cityCourseId || undefined,
                            course_slug: link.dataset.cityCourseSlug || undefined,
                            course_title: link.dataset.cityCourseTitle || undefined,
                            course_position: Number(link.dataset.cityCoursePosition || 0) || undefined,
                            course_price: Number(link.dataset.cityCoursePrice || 0) || undefined,
                        });

                        if (!isCourseRow || shouldSkipHref(href) || shouldKeepDefaultNavigation(event, link)) {
                            return;
                        }

                        event.preventDefault();
                        const nextHref = withViewContentPrefiredFlag(href);
                        window.setTimeout(() => {
                            window.location.assign(nextHref);
                        }, 100);
                    });
                });
            };

            const bindWaitlistPlaceholder = () => {
                const link = document.querySelector('[data-waitlist-link]');
                if (!link) return;

                link.addEventListener('click', (event) => {
                    const href = (link.getAttribute('href') || '').trim();
                    const isPlaceholder = href === '#';

                    window.cityMetaTrack('CityCampaignWaitlistClick', {
                        placeholder: isPlaceholder,
                    });

                    if (isPlaceholder) {
                        event.preventDefault();
                        document.getElementById('waitlist-feedback')?.classList.remove('hidden');
                        return;
                    }

                    window.cityMetaTrackStandard('Lead', {
                        content_name: 'City Campaign Waitlist',
                        content_category: 'waitlist',
                        city_slug: campaignMeta.city_slug,
                        city_name: campaignMeta.city_name,
                        source_page: campaignMeta.page_type,
                    });

                    if (shouldSkipHref(href) || shouldKeepDefaultNavigation(event, link)) {
                        return;
                    }

                    event.preventDefault();
                    window.setTimeout(() => {
                        window.location.assign(href);
                    }, 100);
                });
            };

            const initSectionTracking = () => {
                if (!('IntersectionObserver' in window)) return;

                const seen = new Set();
                const sections = Array.from(document.querySelectorAll('.city-campaign-section'));
                if (!sections.length) return;

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting || entry.intersectionRatio < 0.35) return;
                        const section = entry.target;
                        const key = section.id || section.dataset.sectionKey || section.dataset.sectionIndex;
                        if (!key || seen.has(key)) return;
                        seen.add(key);
                        const heading = section.querySelector('h1, h2');
                        window.cityMetaTrack('CityCampaignSectionView', {
                            section_key: key,
                            section_title: heading ? heading.textContent.trim().slice(0, 80) : undefined,
                        });
                    });
                }, { threshold: [0.35] });

                sections.forEach((section, index) => {
                    section.dataset.sectionIndex = String(index + 1);
                    observer.observe(section);
                });
            };

            const scheduleNonCriticalSectionTracking = () => {
                const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                const saveData = Boolean(connection && connection.saveData);
                const effectiveType = String(connection?.effectiveType || '').toLowerCase();
                const isSlowConnection = effectiveType.includes('2g') || effectiveType.includes('3g');

                if (saveData) {
                    return;
                }

                const run = () => initSectionTracking();

                if ('requestIdleCallback' in window) {
                    window.requestIdleCallback(run, { timeout: isSlowConnection ? 3000 : 1500 });
                    return;
                }

                window.setTimeout(run, isSlowConnection ? 1800 : 800);
            };

            const initCountdown = () => {
                const root = document.querySelector('[data-city-countdown]');
                if (!root || campaignState.is_closed) return;

                const reloadUrl = root.dataset.countdownReloadUrl || window.location.href;
                const hoursEl = root.querySelector('[data-countdown-hours]');
                const minutesEl = root.querySelector('[data-countdown-minutes]');
                const secondsEl = root.querySelector('[data-countdown-seconds]');

                const render = () => {
                    const now = Math.floor(Date.now() / 1000);
                    const diff = Number(campaignState.expires_at_unix) - now;

                    if (diff <= 0) {
                        if (hoursEl) hoursEl.textContent = '00';
                        if (minutesEl) minutesEl.textContent = '00';
                        if (secondsEl) secondsEl.textContent = '00';
                        window.location.href = reloadUrl;
                        return false;
                    }

                    const hours = Math.floor(diff / 3600);
                    const minutes = Math.floor((diff % 3600) / 60);
                    const seconds = diff % 60;

                    if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
                    if (minutesEl) minutesEl.textContent = String(minutes).padStart(2, '0');
                    if (secondsEl) secondsEl.textContent = String(seconds).padStart(2, '0');

                    return true;
                };

                if (!render()) return;

                const intervalId = window.setInterval(() => {
                    if (!render()) {
                        window.clearInterval(intervalId);
                    }
                }, 1000);
            };

            const boot = () => {
                bindCityCtas();
                bindWaitlistPlaceholder();
                initCountdown();
                scheduleNonCriticalSectionTracking();

                window.cityMetaTrack('CityCampaignView', {
                    seconds_remaining: campaignState.is_closed ? undefined : campaignState.seconds_remaining,
                });

                if (campaignState.is_closed) {
                    window.cityMetaTrack('CityCampaignClosedView');
                } else if (campaignState.is_fresh_countdown) {
                    window.cityMetaTrack('CityCampaignCountdownStarted', {
                        seconds_remaining: campaignState.seconds_remaining,
                    });
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot, { once: true });
            } else {
                boot();
            }
        })();
    </script>
@endpush
