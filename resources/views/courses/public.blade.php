@extends('layouts.student')

@section('title', $course->title)
@section('hide_student_header', '1')
@section('hide_student_bottom_nav', '1')
@section('student_main_classes', 'mx-auto max-w-6xl px-2 py-6 md:py-8')

@section('content')
    @php
        $previewLessons = ($previewLessons ?? collect())->values();
        $previewPlayerItems = $previewLessons->map(fn (array $lesson) => [
            'id' => $lesson['id'] ?? null,
            'title' => $lesson['title'] ?? 'Aula',
            'player_type' => $lesson['player_type'] ?? 'none',
            'player_url' => $lesson['player_url'] ?? null,
            'thumb_url' => $lesson['thumb_url'] ?? null,
        ])->values();
        $heroImage = $course->coverImageUrl() ?: ($previewLessons->first()['thumb_url'] ?? null);
        $todayLabel = now()->format('d/m/Y');
        $hasDemoPlyr = $previewLessons->contains(fn (array $lesson) => in_array($lesson['player_type'] ?? 'none', ['youtube', 'video'], true));
        $totalLessonsCount = $course->modules->sum(fn ($module) => $module->lessons->count());
        $remainingLessonsCount = max($totalLessonsCount - $previewLessons->count(), 0);
        $remainingLessonsLabel = $remainingLessonsCount > 0
            ? ($remainingLessonsCount === 1 ? '1 aula restante' : $remainingLessonsCount . ' aulas restantes')
            : 'todo o conteúdo restante';
        $courseHoursLabel = $course->duration_minutes
            ? rtrim(rtrim(number_format($course->duration_minutes / 60, 1, ',', '.'), '0'), ',')
            : 'x';
        $systemSettings = \App\Models\SystemSetting::current();
        $metaAdsPixelId = trim((string) ($systemSettings->meta_ads_pixel ?? ''));
        $cartaEstagioImageUrl = $systemSettings->assetUrl('carta_estagio');
        $lpPrimaryCheckout = $course->checkouts->first();
        $lpPrimaryCheckoutValue = $lpPrimaryCheckout ? (float) $lpPrimaryCheckout->price : null;
        $hasMultipleCheckouts = $course->checkouts->count() > 1;
    @endphp

    <div class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-edux-primary shadow-lg">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 text-white">
            <p class="text-xs font-semibold tracking-wide sm:text-sm">Valor garantido para hoje ({{ $todayLabel }})</p>
            <a
                href="{{ $buyUrl ?? '#oferta' }}"
                data-checkout-link
                data-checkout-source="top_bar_cta"
                data-checkout-hours="{{ $lpPrimaryCheckout?->hours ?? '' }}"
                data-checkout-price="{{ $lpPrimaryCheckoutValue ?? '' }}"
                data-checkout-name="{{ $lpPrimaryCheckout?->nome ?? '' }}"
                class="inline-flex min-h-[42px] items-center justify-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-emerald-600"
            >
                Comprar Agora
            </a>
        </div>
    </div>

    <article class="pt-16 md:pt-20">
        <section id="oferta" class="lp-section space-y-4 pb-6 md:pb-8">
            <div class="flex items-center gap-4">
                @if ($heroImage)
                    <img src="{{ $heroImage }}" alt="{{ $course->title }}" class="h-20 w-20 shrink-0 rounded-xl object-cover ring-1 ring-edux-line md:h-24 md:w-24">
                @else
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl bg-edux-background text-xs font-semibold text-slate-500 ring-1 ring-edux-line md:h-24 md:w-24">
                        Curso
                    </div>
                @endif

                <div class="min-w-0">
                    <p class="text-xs uppercase tracking-[0.2em] text-edux-primary">Curso online</p>
                    <h1 class="mt-1 font-display text-2xl leading-tight text-edux-primary md:text-3xl">
                        {{ $course->title }}
                    </h1>
                    @if ($course->summary)
                        <p class="mt-2 text-sm leading-6 text-slate-600 md:text-base">{{ $course->summary }}</p>
                    @endif
                </div>
            </div>

            @if (! $course->summary && $course->description)
                <p class="text-sm leading-6 text-slate-600 md:text-base">{{ $course->summary ?: $course->description }}</p>
            @endif

        </section>

        <section class="lp-section space-y-5 pt-4 pb-8 md:pt-5" x-data="lpDemoLessons(@js($previewPlayerItems))">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-display text-edux-primary">Aulas demonstrativas</h2>
                    <p class="text-sm text-slate-600">Veja uma prévia real do conteúdo. Escolha uma aula abaixo para assistir sem sair desta página.</p>
                </div>
                <div class="hidden items-center gap-2 sm:flex">
                    <button type="button" @click="scroll(-1)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-edux-primary hover:bg-edux-primary/5" aria-label="Voltar">&lsaquo;</button>
                    <button type="button" @click="scroll(1)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-edux-primary hover:bg-edux-primary/5" aria-label="Avançar">&rsaquo;</button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-edux-line bg-black shadow-sm">
                <div class="aspect-video w-full">
                    <template x-if="active && active.player_type === 'youtube'">
                        <div class="plyr__video-embed h-full" x-ref="demoYoutubePlyr">
                            <iframe
                                :src="active.player_url"
                                allowfullscreen
                                allow="autoplay; encrypted-media"
                            ></iframe>
                        </div>
                    </template>

                    <template x-if="active && active.player_type === 'video'">
                        <video
                            x-ref="demoVideoPlyr"
                            :src="active.player_url"
                            class="h-full w-full bg-black"
                            controls
                            playsinline
                            preload="metadata"
                        ></video>
                    </template>

                    <template x-if="active && active.player_type === 'iframe'">
                        <iframe
                            :src="active.player_url"
                            class="h-full w-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            loading="lazy"
                        ></iframe>
                    </template>

                    <template x-if="!active || active.player_type === 'none'">
                        <div class="flex h-full items-center justify-center bg-slate-950 px-6 text-center text-sm font-semibold text-white/80">
                            Nenhum vídeo demonstrativo disponível para reprodução.
                        </div>
                    </template>
                </div>
            </div>

            <div x-ref="track" class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2">
                @forelse ($previewLessons as $preview)
                    <button
                        type="button"
                        @click="pick({{ $loop->index }})"
                        class="group w-[150px] shrink-0 snap-start overflow-hidden rounded-2xl border border-edux-line bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        :class="{ 'ring-2 ring-edux-primary ring-offset-1': activeIndex === {{ $loop->index }} }"
                    >
                        <div class="relative aspect-video bg-slate-200">
                            @if (!empty($preview['thumb_url']))
                                <img src="{{ $preview['thumb_url'] }}" alt="{{ $preview['title'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                            @else
                                <div class="flex h-full items-center justify-center text-sm font-semibold text-slate-500">Sem thumbnail</div>
                            @endif
                            <span class="absolute left-3 top-3 rounded-full bg-black/65 px-2 py-1 text-xs font-semibold text-white">Aula {{ $loop->iteration }}</span>
                            <span class="absolute bottom-3 right-3 inline-flex h-9 items-center justify-center rounded-full bg-white/95 px-3 text-xs font-bold text-edux-primary shadow">PLAY</span>
                        </div>
                        <div class="p-2.5">
                            <p class="max-h-8 overflow-hidden text-xs font-semibold leading-4 text-slate-800">{{ $preview['title'] }}</p>
                        </div>
                    </button>
                    @if ($loop->last)
                        <a
                            href="{{ $buyUrl ?? '#oferta' }}"
                            data-checkout-link
                            data-checkout-source="demo_unlock_cta"
                            data-checkout-hours="{{ $lpPrimaryCheckout?->hours ?? '' }}"
                            data-checkout-price="{{ $lpPrimaryCheckoutValue ?? '' }}"
                            data-checkout-name="{{ $lpPrimaryCheckout?->nome ?? '' }}"
                            class="group flex w-[150px] shrink-0 snap-start flex-col justify-between overflow-hidden rounded-2xl border border-edux-primary/20 bg-gradient-to-b from-edux-primary/5 via-white to-emerald-50 p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div class="space-y-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white shadow-sm">
                                    +
                                </span>
                                <p class="text-xs font-black leading-4 text-edux-primary">
                                    Matricule-se e libere {{ $remainingLessonsLabel }}
                                </p>
                                <p class="text-[11px] leading-4 text-slate-600">
                                    Continue de onde parou e tenha acesso ao curso completo, certificado e carta de estágio.
                                </p>
                            </div>
                            <span class="mt-3 inline-flex items-center justify-center rounded-xl bg-emerald-500 px-2.5 py-2 text-[11px] font-bold text-white transition group-hover:bg-emerald-600">
                                Liberar agora
                            </span>
                        </a>
                    @endif
                @empty
                    <div class="rounded-2xl border border-dashed border-edux-line bg-white p-5 text-sm text-slate-500">
                        Este curso ainda não possui aulas para exibir no carrossel.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="lp-section space-y-4 py-8">
            <h2 class="text-2xl font-display text-edux-primary">Conteúdo programático</h2>
            <div class="space-y-3">
                @foreach ($course->modules as $module)
                    <details class="rounded-2xl border border-edux-line/70 bg-white px-4 py-3">
                        <summary class="cursor-pointer list-none text-sm font-semibold text-edux-primary">
                            Módulo {{ $module->position }} - {{ $module->title }}
                        </summary>
                        <ul class="mt-3 space-y-1 border-t border-edux-line/60 pt-3 text-sm text-slate-600">
                            @foreach ($module->lessons as $lesson)
                                <li>- {{ $lesson->title }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="lp-section space-y-4 py-8" x-data="{ scroll(dir) { this.$refs.track.scrollBy({ left: (this.$refs.track.clientWidth * 0.9) * dir, behavior: 'smooth' }) } }">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Prévia do certificado</h2>
                    <p class="text-sm text-slate-600">Modelo visual igual ao certificado público de verificação, com o nome exibido como "Seu nome aqui".</p>
                </div>
                <div class="hidden items-center gap-2 sm:flex">
                    <button type="button" @click="scroll(-1)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-edux-primary hover:bg-edux-primary/5" aria-label="Voltar">&lsaquo;</button>
                    <button type="button" @click="scroll(1)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-edux-primary hover:bg-edux-primary/5" aria-label="Avançar">&rsaquo;</button>
                </div>
            </div>

            <div x-ref="track" class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2">
                <div class="w-[92%] shrink-0 snap-start overflow-hidden md:w-[calc(50%_-_0.5rem)]">
                    {!! $certificateFrontPreview !!}
                </div>
                <div class="w-[92%] shrink-0 snap-start overflow-hidden md:w-[calc(50%_-_0.5rem)]">
                    {!! $certificateBackPreview !!}
                </div>
            </div>
        </section>

        <section class="lp-section space-y-6 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Sua preparação completa inclui</h2>
                <p class="text-sm text-slate-600">Tudo que você precisa para estudar, concluir e comprovar sua formação.</p>
            </div>

            <div class="grid gap-4">
                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                        ✓
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 leading-none">{{ $courseHoursLabel }} horas de curso</p>
                        <p class="mt-1 text-xs text-slate-500">Carga horária para sua preparação prática e teórica.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                        ✓
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 leading-none">{{ $totalLessonsCount ?: 'x' }} aulas</p>
                        <p class="mt-1 text-xs text-slate-500">Aulas organizadas por módulos para você avançar no seu ritmo.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                        ✓
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 leading-none">Certificado de Conclusão</p>
                        <p class="mt-1 text-xs text-slate-500">Comprove sua conclusão e fortaleça seu currículo.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                        ✓
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 leading-none">Carta de Estágio</p>
                        <p class="mt-1 text-xs text-slate-500">Documento complementar para apoiar sua entrada no mercado.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section space-y-5 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Carta de estágio assinada para fortalecer sua apresentação</h2>
                <p class="text-sm text-slate-600">
                    Ao concluir sua matrícula, você também pode contar com a carta de estágio assinada para reforçar seu perfil em processos seletivos e abrir mais portas.
                </p>
            </div>

            <div class="grid gap-4 rounded-3xl border border-edux-line/70 bg-white p-1 shadow-sm md:grid-cols-[1.05fr_0.95fr]">
                <div class="overflow-hidden rounded-[1.15rem] bg-slate-100">
                    @if ($cartaEstagioImageUrl)
                        <img
                            src="{{ $cartaEstagioImageUrl }}"
                            alt="Modelo de carta de estágio"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        >
                    @else
                        <div class="flex h-full min-h-[260px] items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 p-6 text-center text-sm font-semibold text-slate-500">
                            Modelo de carta de estágio em configuração
                        </div>
                    @endif
                </div>

                <div class="rounded-[1.15rem] bg-gradient-to-br from-white via-white to-emerald-50/60 p-5 ring-1 ring-slate-100 md:p-6">
                    <div class="space-y-4">
                        <div class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                            Incluso no suporte ao aluno
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-xl font-black leading-tight text-slate-900">
                                Um documento que aumenta sua percepção de preparo
                            </h3>
                            <p class="text-sm leading-6 text-slate-600">
                                A carta de estágio assinada é um diferencial para apresentar seu compromisso com a formação e dar mais confiança a quem avalia seu perfil.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">1</span>
                                <p class="text-xs leading-5 text-slate-600">Formato profissional para você apresentar junto com seu currículo e candidaturas.</p>
                            </div>
                            <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">2</span>
                                <p class="text-xs leading-5 text-slate-600">Ajuda a comunicar sua seriedade e dedicação desde o início da sua jornada.</p>
                            </div>
                            <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">3</span>
                                <p class="text-xs leading-5 text-slate-600">Complementa seu material de apresentação para buscar oportunidades com mais segurança.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="lp-section space-y-5 py-8"
            x-data="{ scroll(dir) { if (!this.$refs.track) return; this.$refs.track.scrollBy({ left: (this.$refs.track.clientWidth * 0.9) * dir, behavior: 'smooth' }) } }"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Escolha seu checkout</h2>
                    <p class="text-sm text-slate-600">Selecione a opção ideal para liberar o acesso e avançar para o pagamento com um clique.</p>
                </div>

                @if ($hasMultipleCheckouts)
                    <div class="hidden items-center gap-2 sm:flex">
                        <button type="button" @click="scroll(-1)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-edux-primary hover:bg-edux-primary/5" aria-label="Voltar">&lsaquo;</button>
                        <button type="button" @click="scroll(1)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-edux-primary hover:bg-edux-primary/5" aria-label="Avançar">&rsaquo;</button>
                    </div>
                @endif
            </div>

            @if ($course->checkouts->isNotEmpty())
                <div
                    x-ref="track"
                    @class([
                        'pb-2',
                        'flex snap-x snap-mandatory gap-4 overflow-x-auto' => $hasMultipleCheckouts,
                        'grid gap-4' => ! $hasMultipleCheckouts,
                    ])
                >
                    @foreach ($course->checkouts as $checkout)
                        <article
                            @class([
                                'relative isolate overflow-hidden rounded-3xl border p-1',
                                'w-[94%] shrink-0 snap-start md:w-[calc(50%_-_0.5rem)]' => $hasMultipleCheckouts,
                                'w-full' => ! $hasMultipleCheckouts,
                                'border-edux-primary/20 bg-gradient-to-br from-white via-sky-50/80 to-emerald-50/70 shadow-[0_20px_50px_-28px_rgba(16,185,129,0.45)]' => $loop->first,
                                'border-edux-line/70 bg-gradient-to-br from-white via-white to-slate-50 shadow-sm' => ! $loop->first,
                            ])
                        >
                            <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-edux-primary/10 blur-2xl"></div>
                            <div class="pointer-events-none absolute -left-8 bottom-8 h-24 w-24 rounded-full bg-emerald-400/10 blur-2xl"></div>

                            <div class="relative flex h-full flex-col gap-4 rounded-[1.1rem] bg-white/90 p-4 md:p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-2">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                            Carga horária {{ $checkout->hours }}h
                                        </span>

                                        <div class="space-y-1">
                                            <h3 class="text-lg font-black leading-tight text-slate-900 md:text-xl">
                                                {{ $checkout->nome ?: ('Opção ' . $checkout->hours . 'h') }}
                                            </h3>
                                            <p class="text-xs leading-5 text-slate-600">
                                                {{ $checkout->descricao ?: 'Liberação do checkout com acesso imediato ao fluxo de pagamento e confirmação rápida.' }}
                                            </p>
                                        </div>
                                    </div>

                                    @if ($loop->first)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">
                                            Destaque
                                        </span>
                                    @endif
                                </div>

                                <div class="rounded-2xl bg-slate-950 p-4 text-white ring-1 ring-white/10">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70">Investimento</p>
                                    <div class="mt-2 flex items-end justify-between gap-3">
                                        <p class="text-3xl font-black leading-none md:text-4xl">
                                            R$ {{ number_format((float) $checkout->price, 2, ',', '.') }}
                                        </p>
                                        <p class="text-[11px] text-right text-white/70">
                                            pagamento seguro<br>liberação imediata
                                        </p>
                                    </div>
                                </div>

                                @if ($checkout->bonuses->isNotEmpty())
                                    <div class="space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bônus liberados sem custo</p>
                                        <div class="grid gap-2">
                                            @foreach ($checkout->bonuses as $bonus)
                                                <div class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3">
                                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-xs font-black text-white">✓</span>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-xs font-semibold text-slate-900">{{ $bonus->nome }}</p>
                                                        @if ($bonus->descricao)
                                                            <p class="mt-1 text-[11px] leading-4 text-slate-500">{{ $bonus->descricao }}</p>
                                                        @endif
                                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                                                Grátis
                                                            </span>
                                                            <span class="text-[11px] font-semibold text-slate-400 line-through">
                                                                de R$ {{ number_format((float) $bonus->preco, 2, ',', '.') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 p-3 text-xs text-slate-500">
                                        Esta opção não possui bônus extras cadastrados no momento.
                                    </div>
                                @endif

                                <div class="mt-auto space-y-2 pt-1">
                                    <a
                                        href="{{ $checkout->checkout_url }}"
                                        data-checkout-link
                                        data-checkout-source="checkout_card"
                                        data-checkout-id="{{ $checkout->id }}"
                                        data-checkout-hours="{{ $checkout->hours }}"
                                        data-checkout-price="{{ (float) $checkout->price }}"
                                        data-checkout-name="{{ $checkout->nome ?: ('Opção ' . $checkout->hours . 'h') }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="group relative inline-flex min-h-[58px] w-full items-center justify-center overflow-hidden rounded-2xl bg-emerald-500 px-4 py-3 text-center text-sm font-black text-white shadow-[0_16px_35px_-18px_rgba(16,185,129,0.95)] ring-1 ring-emerald-300 transition hover:-translate-y-0.5 hover:bg-emerald-600 hover:shadow-[0_22px_40px_-18px_rgba(16,185,129,0.85)]"
                                    >
                                        <span class="pointer-events-none absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 opacity-0 transition group-hover:opacity-100"></span>
                                        <span class="relative inline-flex items-center gap-2">
                                            Ir para o pagamento
                                            <span aria-hidden="true">→</span>
                                        </span>
                                    </a>
                                    <p class="text-[11px] leading-4 text-slate-500">
                                        Seus parâmetros de campanha são mantidos para rastrear a origem do tráfego com precisão.
                                    </p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($hasMultipleCheckouts)
                    <p class="text-xs text-slate-500">Deslize para comparar as opções de checkout lado a lado.</p>
                @endif
            @else
                <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-100">
                    Nenhuma opção de checkout disponível no momento.
                </div>
            @endif
        </section>

        @if ($course->owner)
            <section class="lp-section space-y-5 py-8">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Quem será seu professor</h2>
                    <p class="text-sm text-slate-600">Conheça quem preparou este conteúdo e vai conduzir sua jornada de aprendizado.</p>
                </div>

                <div class="relative overflow-hidden rounded-3xl border border-edux-line/70 bg-white p-1 shadow-sm">
                    <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-edux-primary/10 blur-2xl"></div>
                    <div class="pointer-events-none absolute -left-6 bottom-6 h-20 w-20 rounded-full bg-emerald-400/10 blur-2xl"></div>

                    <div class="relative grid gap-4 rounded-[1.2rem] bg-gradient-to-br from-white via-white to-slate-50 p-4 md:grid-cols-[160px_1fr] md:gap-6 md:p-6">
                        <div class="flex items-start justify-center md:justify-start">
                            @if ($course->owner->profilePhotoUrl())
                                <img
                                    src="{{ $course->owner->profilePhotoUrl() }}"
                                    alt="{{ $course->owner->preferredName() }}"
                                    class="h-28 w-28 rounded-2xl object-cover ring-1 ring-edux-line md:h-36 md:w-36"
                                >
                            @else
                                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-edux-primary/10 text-3xl font-black text-edux-primary ring-1 ring-edux-line md:h-36 md:w-36">
                                    {{ mb_strtoupper(mb_substr($course->owner->preferredName(), 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Professor responsável</p>
                                <h3 class="text-xl font-black leading-tight text-slate-900 md:text-2xl">
                                    {{ $course->owner->preferredName() }}
                                </h3>
                            </div>

                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <p class="text-xs leading-5 text-slate-600 md:text-sm md:leading-6">
                                    {{ $course->owner->qualification ?: 'Professor com experiência prática na área e foco em ensino direto, aplicável e acessível para iniciantes e profissionais em atualização.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="lp-section space-y-4 py-8">
            <h2 class="text-2xl font-display text-edux-primary">Perguntas frequentes</h2>
            @foreach ([
                ['title' => 'Por quanto tempo posso acessar o curso?', 'body' => 'Você pode acessar o curso por 2 anos. No momento da matrícula, também existe a opção de adquirir acesso vitalício.'],
                ['title' => 'Preciso fazer prova para ganhar o certificado?', 'body' => $course->finalTest ? 'Sim, tem um teste final bem prático para você provar que aprendeu e liberar o certificado.' : 'Não! Basta você concluir todas as aulas para ganhar seu certificado.'],
                ['title' => 'Posso fazer o curso no meu celular?', 'body' => 'Claro! Você pode assistir as aulas no celular, tablet ou computador, quando e onde quiser.'],
                ['title' => 'Preciso de internet rápida?', 'body' => 'Não precisa de internet super rápida. A plataforma funciona bem com internet normal. As aulas carregam direitinho.'],
                ['title' => 'Posso baixar as aulas para assistir depois?', 'body' => 'Você assiste online pela plataforma. Recomendamos uma conexão de internet estável para não perder o aprendizado.'],
                ['title' => 'Como funciona o certificado?', 'body' => 'Depois de terminar o curso' . ($course->finalTest ? ' e passar no teste' : '') . ', você recebe um certificado oficial. Se quiser, pode pagar um valor bem acessível para receber uma cópia impressa.'],
                ['title' => 'Vou precisar pagar algo a mais depois?', 'body' => 'Não! Tudo é gratuito. O único pagamento opcional é se você quiser o certificado impresso, que custa bem pouco.'],
                ['title' => 'Como faço para me inscrever?', 'body' => 'É muito fácil! Basta clicar em "inscreva-se grátis", criar sua conta com email e senha, e começar a aprender na hora.'],
                ['title' => 'Posso cancelar minha inscrição depois?', 'body' => 'Pode ficar tranquilo! Você pode parar quando quiser. Não há compromisso nem cobrança.'],
            ] as $faq)
                <details class="rounded-2xl border border-edux-line/70 p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-edux-primary">{{ $faq['title'] }}</summary>
                    <p class="mt-2 text-sm text-slate-600">{{ $faq['body'] }}</p>
                </details>
            @endforeach
        </section>
    </article>

    @push('styles')
        <style>
            .lp-section {
                position: relative;
            }

            .lp-section + .lp-section::before {
                content: '';
                position: absolute;
                left: 0;
                right: 0;
                top: 0;
                height: 1px;
                background: linear-gradient(to right, transparent, rgba(203, 213, 225, 0.95), transparent);
            }

            .lp-section + .lp-section::after {
                content: '';
                position: absolute;
                left: 2rem;
                right: 2rem;
                top: 0.25rem;
                height: 2.25rem;
                border-radius: 9999px;
                background: linear-gradient(to right, transparent, rgba(26, 115, 232, 0.08), transparent);
                filter: blur(16px);
                pointer-events: none;
            }
        </style>
        @if ($hasDemoPlyr)
            <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
        @endif
    @endpush

    @push('scripts')
        @if ($hasDemoPlyr)
            <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
        @endif
        <script>
            function lpDemoLessons(lessons = []) {
                return {
                    lessons,
                    activeIndex: 0,
                    youtubePlyr: null,
                    videoPlyr: null,
                    init() {
                        this.$nextTick(() => this.initPlyr());
                        this.$watch('activeIndex', () => {
                            this.$nextTick(() => this.initPlyr());
                        });
                    },
                    get active() {
                        return this.lessons[this.activeIndex] ?? null;
                    },
                    destroyPlyr() {
                        if (this.youtubePlyr) {
                            this.youtubePlyr.destroy();
                            this.youtubePlyr = null;
                        }
                        if (this.videoPlyr) {
                            this.videoPlyr.destroy();
                            this.videoPlyr = null;
                        }
                    },
                    initPlyr() {
                        this.destroyPlyr();

                        if (!window.Plyr || !this.active) {
                            return;
                        }

                        if (this.active.player_type === 'youtube' && this.$refs.demoYoutubePlyr) {
                            this.youtubePlyr = new Plyr(this.$refs.demoYoutubePlyr, {
                                youtube: {
                                    rel: 0,
                                    modestbranding: 1,
                                },
                            });
                        }

                        if (this.active.player_type === 'video' && this.$refs.demoVideoPlyr) {
                            this.videoPlyr = new Plyr(this.$refs.demoVideoPlyr);
                        }
                    },
                    pick(index) {
                        if (index < 0 || index >= this.lessons.length) return;
                        this.activeIndex = index;
                        const lesson = this.lessons[index] ?? null;
                        if (lesson && typeof window.lpMetaTrack === 'function') {
                            window.lpMetaTrack('LPDemoLessonSelect', {
                                lesson_index: index + 1,
                                lesson_title: lesson.title ?? 'Aula',
                                lesson_player_type: lesson.player_type ?? 'none',
                            });
                        }
                    },
                    scroll(dir) {
                        if (!this.$refs.track) return;
                        this.$refs.track.scrollBy({
                            left: (this.$refs.track.clientWidth * 0.85) * dir,
                            behavior: 'smooth',
                        });
                    },
                };
            }
        </script>
        @if ($metaAdsPixelId !== '')
            <script>
                !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(
                    window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js'
                );

                fbq('init', @js($metaAdsPixelId));
                fbq('track', 'PageView');
            </script>
        @endif
        <script>
            (() => {
                const courseMeta = @js([
                    'course_id' => $course->id,
                    'course_slug' => $course->slug,
                    'course_title' => $course->title,
                    'page_type' => 'catalogo_course_lp',
                ]);
                const primaryCheckoutValue = @js($lpPrimaryCheckoutValue);
                const rawSearchParams = new URLSearchParams(window.location.search);

                const queryParams = {};
                for (const [key, value] of rawSearchParams.entries()) {
                    if (!key || value === '') continue;
                    const safeKey = String(key).toLowerCase().replace(/[^a-z0-9_]/g, '_').slice(0, 40);
                    if (!safeKey) continue;
                    queryParams[`qp_${safeKey}`] = String(value).slice(0, 120);
                }

                if (typeof window.lpMetaTrack !== 'function') {
                    window.lpMetaTrack = function lpMetaTrack(eventName, extra = {}) {
                        if (!window.fbq) return;
                        try {
                            window.fbq('trackCustom', eventName, { ...courseMeta, ...queryParams, ...extra });
                        } catch (_) {}
                    };
                }

                if (typeof window.lpMetaTrackStandard !== 'function') {
                    window.lpMetaTrackStandard = function lpMetaTrackStandard(eventName, params = {}) {
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

                const withTrackingParams = (href) => {
                    if (shouldSkipHref(href)) return href;

                    let url;
                    try {
                        url = new URL(href, window.location.href);
                    } catch (_) {
                        return href;
                    }

                    rawSearchParams.forEach((value, key) => {
                        if (!url.searchParams.has(key)) {
                            url.searchParams.set(key, value);
                        }
                    });

                    if (!url.searchParams.has('edux_lp')) {
                        url.searchParams.set('edux_lp', '1');
                    }

                    if (!url.searchParams.has('edux_course_slug')) {
                        url.searchParams.set('edux_course_slug', String(courseMeta.course_slug));
                    }

                    return url.toString();
                };

                const prepareCheckoutLinks = () => {
                    document.querySelectorAll('a[data-checkout-link]').forEach((link) => {
                        const originalHref = link.getAttribute('href');
                        if (!originalHref || shouldSkipHref(originalHref)) return;

                        link.setAttribute('href', withTrackingParams(originalHref));

                        if (link.dataset.lpCheckoutBound === '1') return;
                        link.dataset.lpCheckoutBound = '1';

                        link.addEventListener('click', () => {
                            const checkoutName = link.dataset.checkoutName || '';
                            const checkoutSource = link.dataset.checkoutSource || 'checkout_cta';
                            const checkoutHours = Number(link.dataset.checkoutHours || 0) || null;
                            const checkoutPrice = Number(link.dataset.checkoutPrice || 0);
                            const isExternal = !shouldSkipHref(link.getAttribute('href'));

                            window.lpMetaTrack('LPCheckoutClick', {
                                checkout_source: checkoutSource,
                                checkout_name: checkoutName || undefined,
                                checkout_hours: checkoutHours ?? undefined,
                                checkout_price: Number.isFinite(checkoutPrice) && checkoutPrice > 0 ? checkoutPrice : undefined,
                            });

                            if (isExternal && Number.isFinite(checkoutPrice) && checkoutPrice > 0) {
                                window.lpMetaTrackStandard('InitiateCheckout', {
                                    currency: 'BRL',
                                    value: checkoutPrice,
                                    content_name: checkoutName || courseMeta.course_title,
                                    content_category: 'course',
                                });
                            }
                        });
                    });
                };

                const initSectionTracking = () => {
                    if (!('IntersectionObserver' in window)) return;

                    const seen = new Set();
                    const sections = Array.from(document.querySelectorAll('.lp-section'));
                    if (sections.length === 0) return;

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (!entry.isIntersecting || entry.intersectionRatio < 0.35) return;
                            const section = entry.target;
                            const key = section.id || section.dataset.lpSectionKey || section.dataset.lpSectionIndex;
                            if (!key || seen.has(key)) return;
                            seen.add(key);

                            const heading = section.querySelector('h1, h2');
                            window.lpMetaTrack('LPSectionView', {
                                section_key: key,
                                section_title: heading ? heading.textContent.trim().slice(0, 80) : undefined,
                                section_index: Number(section.dataset.lpSectionIndex || 0) || undefined,
                            });
                        });
                    }, { threshold: [0.35] });

                    sections.forEach((section, index) => {
                        section.dataset.lpSectionIndex = String(index + 1);
                        observer.observe(section);
                    });
                };

                const initFaqTracking = () => {
                    document.querySelectorAll('details').forEach((details, index) => {
                        if (details.dataset.lpFaqBound === '1') return;
                        details.dataset.lpFaqBound = '1';

                        details.addEventListener('toggle', () => {
                            if (!details.open) return;
                            const summary = details.querySelector('summary');
                            window.lpMetaTrack('LPFaqOpen', {
                                faq_index: index + 1,
                                faq_title: summary ? summary.textContent.trim().slice(0, 120) : undefined,
                            });
                        });
                    });
                };

                const boot = () => {
                    prepareCheckoutLinks();
                    initSectionTracking();
                    initFaqTracking();

                    window.lpMetaTrack('LPCourseView', {
                        page_path: window.location.pathname,
                        has_checkout: document.querySelectorAll('a[data-checkout-link]').length > 0,
                    });

                    if (primaryCheckoutValue !== null && primaryCheckoutValue !== undefined) {
                        window.lpMetaTrackStandard('ViewContent', {
                            content_name: courseMeta.course_title,
                            content_type: 'product',
                            content_category: 'course',
                            currency: 'BRL',
                            value: Number(primaryCheckoutValue),
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

    <!--<div class="fixed inset-x-0 bottom-20 z-40 border-t border-edux-line bg-white p-4 shadow-2xl md:hidden md:bottom-0">
        @auth
            <form method="POST" action="{{ route('learning.courses.enroll', $course) }}">
                @csrf
                <button type="submit" class="edux-btn w-full">Inscreva-se gratis</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="edux-btn w-full">Crie sua conta para se inscrever</a>
        @endauth
    </div>-->
@endsection
