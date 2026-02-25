@extends('layouts.student')

@section('title', $course->title)
@section('hide_student_header', '1')
@section('hide_student_bottom_nav', '1')
@section('student_main_classes', 'mx-auto max-w-6xl px-2 pt-6 pb-32 md:py-8')

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
        $studentCountValue = (int) ($studentCount ?? 0);
        $studentCountLabel = $studentCountValue > 0 ? number_format($studentCountValue, 0, ',', '.') : null;
        $primaryCheckoutName = $lpPrimaryCheckout
            ? ($lpPrimaryCheckout->nome ?: ('Opção ' . $lpPrimaryCheckout->hours . 'h'))
            : 'Matrícula';
        $primaryCheckoutPriceLabel = $lpPrimaryCheckoutValue !== null
            ? 'R$ ' . number_format($lpPrimaryCheckoutValue, 2, ',', '.')
            : null;
        $primaryCtaHref = ($buyUrl ?? '#matricula') === '#oferta' ? '#matricula' : ($buyUrl ?? '#matricula');
        $stickyCheckout = $course->checkouts->sortBy(fn ($checkout) => (float) $checkout->price)->first();
        $stickyCheckoutValue = $stickyCheckout ? (float) $stickyCheckout->price : null;
        $stickyCheckoutPriceLabel = $stickyCheckoutValue !== null
            ? 'R$ ' . number_format($stickyCheckoutValue, 2, ',', '.')
            : ($primaryCheckoutPriceLabel ?: 'Consultar');
        $stickyCheckoutName = $stickyCheckout
            ? ($stickyCheckout->nome ?: ('Opção ' . $stickyCheckout->hours . 'h'))
            : $primaryCheckoutName;
        $stickyCtaHref = $stickyCheckout?->checkout_url ?: $primaryCtaHref;
        $lpCityDisplayName = isset($cityDisplayName) && is_string($cityDisplayName) ? trim($cityDisplayName) : '';
        $lpHasCityContext = (bool) ($hasCityContext ?? false) && $lpCityDisplayName !== '';
        $cityHeroTitle = $lpHasCityContext
            ? ($course->title . ' com valor social para ' . $lpCityDisplayName)
            : ($course->title . ' com valor social de matrícula');
        $cityHeroBadgeLabel = $lpHasCityContext ? ('Atendimento para ' . $lpCityDisplayName) : null;
        $citySupportTitle = $lpHasCityContext
            ? ('Atendimento com valor social disponível para ' . $lpCityDisplayName)
            : 'Você pode começar hoje';
        $cityFinalCtaTitle = $lpHasCityContext
            ? ('Se você está em ' . $lpCityDisplayName . ', aproveite o valor social e escolha sua matrícula agora.')
            : 'Aproveite o valor social e escolha sua matrícula agora.';
        $cityStickyFooterLine = $lpHasCityContext
            ? ('Atendimento para ' . $lpCityDisplayName . ' • sem vínculo com governo')
            : 'Valor social • sem vínculo com governo • curso online';
    @endphp

    <article class="pb-8" data-lp-variant="v4">
        <section id="oferta" class="lp-section pb-6 md:pb-8">
            <div class="grid gap-4 md:grid-cols-[1.1fr_0.9fr] md:items-start">
                <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-black uppercase tracking-wide text-white">Comunicado de liberação local</span>
                        <span class="inline-flex items-center rounded-full bg-edux-primary px-3 py-1 text-xs font-bold text-white">Valor social</span>
                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">Sem vínculo com governo</span>
                        @if ($cityHeroBadgeLabel)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900 ring-1 ring-amber-200">{{ $cityHeroBadgeLabel }}</span>
                        @endif
                        @if ($studentCountLabel)
                            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $studentCountLabel }} matrículas</span>
                        @endif
                    </div>

                    <div class="mt-5 flex items-start gap-4">
                        @if ($heroImage)
                            <img src="{{ $heroImage }}" alt="{{ $course->title }}" class="h-24 w-24 shrink-0 rounded-2xl object-cover ring-1 ring-slate-200 md:h-28 md:w-28">
                        @else
                            <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xs font-semibold text-slate-500 ring-1 ring-slate-200 md:h-28 md:w-28">
                                Curso
                            </div>
                        @endif

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-edux-primary">Liberação local de capacitação profissional</p>
                            <h1 class="mt-2 font-display text-3xl leading-tight tracking-tight text-slate-900 md:text-4xl">
                                {{ $cityHeroTitle }}
                            </h1>
                            <p class="mt-3 text-sm leading-6 text-slate-600 md:text-base">
                                {{ $course->summary ?: ($course->description ? \Illuminate\Support\Str::limit(strip_tags((string) $course->description), 190) : 'Capacitação profissional online para ajudar você a fortalecer currículo e prática com linguagem simples.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <x-ui.color-icon name="clock" tone="blue" size="sm" />
                                <span>{{ $courseHoursLabel }} horas de conteúdo</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <x-ui.color-icon name="play-circle" tone="indigo" size="sm" />
                                <span>{{ $totalLessonsCount ?: 'x' }} aulas online</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <x-ui.color-icon name="badge-check" tone="green" size="sm" />
                                <span>Certificado incluso no valor</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <x-ui.color-icon name="file-text" tone="amber" size="sm" />
                                <span>Carta de estágio como apoio ao currículo</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-2 sm:grid-cols-[1fr_auto]">
                        <a
                            href="{{ $primaryCtaHref }}"
                            data-checkout-link
                            data-checkout-source="hero_primary_v4"
                            data-checkout-hours="{{ $lpPrimaryCheckout?->hours ?? '' }}"
                            data-checkout-price="{{ $lpPrimaryCheckoutValue ?? '' }}"
                            data-checkout-name="{{ $primaryCheckoutName }}"
                            class="inline-flex min-h-[52px] items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-center text-sm font-black text-white shadow-lg transition hover:bg-slate-800"
                        >
                            Quero me matricular agora
                        </a>
                        <a
                            href="#matricula"
                            data-lp-cta-source="hero_secondary_v4"
                            class="inline-flex min-h-[52px] items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-center text-sm font-bold text-slate-900 transition hover:bg-slate-50"
                        >
                            Ver opções de matrícula
                        </a>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-slate-600">
                        Iniciativa social independente, sem vínculo com governo. O curso ajuda na sua preparação profissional, mas não garante contratação.
                    </p>
                </div>

                <aside class="rounded-3xl border border-slate-800 bg-slate-950 p-4 text-white shadow-[0_24px_50px_-25px_rgba(15,23,42,0.9)] md:sticky md:top-6 md:p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/65">Consulta de matrícula</p>
                    <div class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                        @if ($lpHasCityContext)
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">Cidade informada: {{ $lpCityDisplayName }}</p>
                        @endif
                        <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/65">Valor social da matrícula</p>
                        <p class="mt-2 text-3xl font-black leading-none md:text-4xl">{{ $primaryCheckoutPriceLabel ?: 'Consultar' }}</p>
                        <p class="mt-2 text-sm text-white/75">
                            @if ($lpPrimaryCheckout)
                                {{ $primaryCheckoutName }}{{ $lpPrimaryCheckout?->hours ? ' • ' . $lpPrimaryCheckout->hours . 'h' : '' }}
                            @else
                                Compare as opções abaixo.
                            @endif
                        </p>
                        <p class="mt-1 text-xs leading-5 text-white/60">Pagamento único da matrícula (não é mensalidade).</p>
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2">
                            <span class="text-white/70">Formato</span>
                            <strong class="text-white">100% online</strong>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2">
                            <span class="text-white/70">Acesso</span>
                            <strong class="text-white">Celular e computador</strong>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2">
                            <span class="text-white/70">Pagamento</span>
                            <strong class="text-white">Seguro</strong>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <a
                            href="{{ $primaryCtaHref }}"
                            data-checkout-link
                            data-checkout-source="sticky_panel_primary_v4"
                            data-checkout-hours="{{ $lpPrimaryCheckout?->hours ?? '' }}"
                            data-checkout-price="{{ $lpPrimaryCheckoutValue ?? '' }}"
                            data-checkout-name="{{ $primaryCheckoutName }}"
                            class="inline-flex min-h-[52px] w-full items-center justify-center rounded-2xl bg-white px-4 py-3 text-center text-sm font-black text-slate-900 transition hover:bg-slate-100"
                        >
                            Ir para matrícula
                        </a>
                        <a
                            href="#matricula"
                            data-lp-cta-source="sticky_panel_secondary_v4"
                            class="inline-flex min-h-[48px] w-full items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            Comparar opções
                        </a>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-white/65">Acesso após confirmação do pagamento. Iniciativa social independente, sem vínculo com governo.</p>
                </aside>
            </div>
        </section>

        <section class="lp-section lp-deferred space-y-4 py-8">
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-display text-slate-900">{{ $citySupportTitle }}</h2>
                        <p class="text-sm text-slate-600 md:text-base">Informações rápidas para facilitar sua decisão e sua matrícula.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">
                        Atendimento simplificado
                    </span>
                </div>

                <div class="mt-4 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-slate-50">
                    <div class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700">
                        <x-ui.color-icon name="sparkles" tone="blue" size="sm" />
                        <span class="font-semibold">Não precisa experiência para começar</span>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700">
                        <x-ui.color-icon name="smartphone" tone="indigo" size="sm" />
                        <span class="font-semibold">Pode estudar pelo celular ou computador</span>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700">
                        <x-ui.color-icon name="play-circle" tone="blue" size="sm" />
                        <span class="font-semibold">Assista as aulas no seu ritmo</span>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700">
                        <x-ui.color-icon name="badge-check" tone="green" size="sm" />
                        <span class="font-semibold">Certificado ao concluir</span>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700">
                        <x-ui.color-icon name="file-text" tone="amber" size="sm" />
                        <span class="font-semibold">Carta de estágio como apoio à apresentação profissional</span>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700">
                        <x-ui.color-icon name="wallet" tone="green" size="sm" />
                        <span class="font-semibold">Pagamento único da matrícula (não é mensalidade)</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section lp-deferred space-y-4 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Como funciona sua matrícula</h2>
                <p class="text-sm text-slate-600 md:text-base">Passo a passo rápido para decidir e começar.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm md:p-3">
                <div class="grid gap-2 md:grid-cols-3">
                    <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="relative shrink-0">
                            <x-ui.color-icon name="list-check" tone="blue" size="sm" />
                            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-900 px-1 text-[10px] font-black text-white">1</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Veja as opções liberadas</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Compare os valores e escolha a opção de matrícula.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="relative shrink-0">
                            <x-ui.color-icon name="shield-check" tone="green" size="sm" />
                            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-900 px-1 text-[10px] font-black text-white">2</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Finalize no pagamento seguro</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Você será direcionado para concluir a matrícula com segurança.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="relative shrink-0">
                            <x-ui.color-icon name="book-open" tone="amber" size="sm" />
                            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-900 px-1 text-[10px] font-black text-white">3</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Comece a estudar</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Acesse as aulas, pratique e conclua para receber seu certificado.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section lp-deferred space-y-5 pt-4 pb-8 md:pt-5" x-data="lpDemoLessons(@js($previewPlayerItems))">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-display text-edux-primary">Experimente o curso antes de se matricular</h2>
                    <p class="text-sm text-slate-600 md:text-base">Assista uma amostra real e veja como as aulas são explicadas antes de decidir.</p>
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
                            href="{{ $primaryCtaHref }}"
                            data-checkout-link
                            data-checkout-source="demo_unlock_cta_v4"
                            data-checkout-hours="{{ $lpPrimaryCheckout?->hours ?? '' }}"
                            data-checkout-price="{{ $lpPrimaryCheckoutValue ?? '' }}"
                            data-checkout-name="{{ $lpPrimaryCheckout?->nome ?? '' }}"
                            class="group flex w-[150px] shrink-0 snap-start flex-col justify-between overflow-hidden rounded-2xl border border-edux-primary/20 bg-gradient-to-b from-edux-primary/5 via-white to-emerald-50 p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div class="space-y-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white shadow-sm">
                                    +
                                </span>
                                <p class="text-sm font-black leading-5 text-edux-primary">
                                    Liberar o curso completo ({{ $remainingLessonsLabel }})
                                </p>
                                <p class="text-sm leading-5 text-slate-600">
                                    Continue de onde parou e tenha acesso ao curso completo, certificado e carta de estágio.
                                </p>
                            </div>
                            <span class="mt-3 inline-flex items-center justify-center rounded-xl bg-emerald-500 px-2.5 py-2 text-sm font-bold text-white transition group-hover:bg-emerald-600">
                                Liberar curso completo
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

        <section class="lp-section lp-deferred space-y-4 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">O que você vai aprender</h2>
                <p class="text-sm text-slate-600 md:text-base">Currículo organizado em módulos para você estudar com clareza e acompanhar sua evolução.</p>
            </div>
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
            <div class="flex justify-start">
                <a
                    href="#matricula"
                    class="inline-flex min-h-[48px] items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
                >
                    Ir para matrícula
                </a>
            </div>
        </section>

        <section class="lp-section lp-deferred space-y-4 py-8" x-data="{ scroll(dir) { this.$refs.track.scrollBy({ left: (this.$refs.track.clientWidth * 0.9) * dir, behavior: 'smooth' }) } }">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Documentos de apoio e comprovação</h2>
                    <p class="text-sm text-slate-600 md:text-base">Veja a prévia do certificado e entenda como a carta de estágio pode apoiar sua apresentação profissional.</p>
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

            <div class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-3 shadow-sm md:grid-cols-[1.05fr_0.95fr] md:p-4">
                <div class="overflow-hidden rounded-2xl bg-slate-100">
                    @if ($cartaEstagioImageUrl)
                        <img
                            src="{{ $cartaEstagioImageUrl }}"
                            alt="Modelo de carta de estágio"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        >
                    @else
                        <div class="flex h-full min-h-[240px] items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 p-6 text-center text-sm font-semibold text-slate-500">
                            Modelo de carta de estágio em configuração
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="space-y-3">
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-800">
                            Apoio à apresentação profissional
                        </span>
                        <h3 class="text-lg font-black leading-tight text-slate-900">Carta de estágio para apoiar sua apresentação profissional</h3>
                        <p class="text-sm leading-6 text-slate-600">
                            A carta de estágio é um documento complementar para mostrar dedicação aos estudos e apoiar sua apresentação em candidaturas. Ela não garante vaga nem contratação.
                        </p>
                        <ul class="space-y-2 text-sm text-slate-700">
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-emerald-600">✓</span><span>Complementa currículo e candidaturas.</span></li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-emerald-600">✓</span><span>Ajuda a mostrar compromisso com a formação.</span></li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-emerald-600">✓</span><span>Material adicional junto com certificado.</span></li>
                        </ul>
                        <a
                            href="#matricula"
                            data-lp-cta-source="documents_section_cta_v4"
                            class="inline-flex min-h-[48px] w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-center text-sm font-black text-white transition hover:bg-slate-800"
                        >
                            Ver matrícula com certificado e carta
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section lp-deferred space-y-6 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">O que está incluído na sua matrícula</h2>
                <p class="text-sm text-slate-600 md:text-base">Tudo para estudar, concluir o curso e fortalecer sua apresentação profissional.</p>
            </div>

            <div class="grid gap-4">
                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <x-ui.color-icon name="clock" tone="blue" />
                    <div>
                        <p class="text-sm font-black leading-none text-slate-900">{{ $courseHoursLabel }} horas de curso</p>
                        <p class="mt-1 text-sm text-slate-500">Carga horária para aprender teoria e prática com calma.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <x-ui.color-icon name="play-circle" tone="indigo" />
                    <div>
                        <p class="text-sm font-black leading-none text-slate-900">{{ $totalLessonsCount ?: 'x' }} aulas</p>
                        <p class="mt-1 text-sm text-slate-500">Aulas organizadas por módulos para avançar no seu ritmo.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <x-ui.color-icon name="badge-check" tone="green" />
                    <div>
                        <p class="text-sm font-black leading-none text-slate-900">Certificado incluso no valor</p>
                        <p class="mt-1 text-sm text-slate-500">Comprove a conclusão do curso e fortaleça seu currículo.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <x-ui.color-icon name="file-text" tone="amber" />
                    <div>
                        <p class="text-sm font-black leading-none text-slate-900">Carta de estágio</p>
                        <p class="mt-1 text-sm text-slate-500">Documento complementar para apoiar sua apresentação em processos seletivos.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <x-ui.color-icon name="shield-check" tone="green" />
                    <div>
                        <p class="text-sm font-black leading-none text-slate-900">Suporte ao aluno e pagamento seguro</p>
                        <p class="mt-1 text-sm text-slate-500">Matrícula em ambiente seguro e acesso após confirmação do pagamento.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section lp-deferred space-y-5 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Certificado e carta ajudam sua apresentação profissional</h2>
                <p class="text-sm text-slate-600 md:text-base">
                    O curso inclui documentos que ajudam você a organizar melhor sua apresentação. Eles fortalecem sua preparação, mas não garantem vaga ou contratação.
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
                        <div class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">
                            Dossiê de apresentação
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-xl font-black leading-tight text-slate-900">
                                Use junto com seu currículo para apresentar melhor sua formação
                            </h3>
                            <p class="text-sm leading-6 text-slate-600">
                                A carta de estágio assinada ajuda a apresentar melhor seu perfil em candidaturas. Ela pode fortalecer sua apresentação, mas não garante vaga nem contratação.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">1</span>
                                <p class="text-sm leading-6 text-slate-600">Formato profissional para apresentar junto com currículo e candidaturas.</p>
                            </div>
                            <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">2</span>
                                <p class="text-sm leading-6 text-slate-600">Ajuda a mostrar seriedade e dedicação no começo da sua jornada.</p>
                            </div>
                            <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-edux-primary text-xs font-black text-white">3</span>
                                <p class="text-sm leading-6 text-slate-600">Complementa seu material para buscar oportunidades com mais segurança.</p>
                            </div>
                        </div>

                        <a
                            href="#matricula"
                            data-lp-cta-source="carta_estagio_section_v4"
                            class="inline-flex min-h-[48px] w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-center text-sm font-black text-white transition hover:bg-slate-800"
                        >
                            Quero matrícula com documentos inclusos
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section id="matricula" class="lp-section lp-deferred space-y-5 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Escolha a forma de matrícula que cabe no seu momento</h2>
                <p class="text-sm text-slate-600 md:text-base">Compare as opções, veja o valor e siga para o pagamento com segurança.</p>
            </div>

            @if ($course->checkouts->isNotEmpty())
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="hidden md:block">
                        <div class="grid grid-cols-[1.6fr_0.7fr_0.8fr_1.1fr_0.9fr] gap-3 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-600">
                            <div>Opção</div>
                            <div>Carga</div>
                            <div>Valor</div>
                            <div>Bônus</div>
                            <div class="text-right">Ação</div>
                        </div>

                        <div class="divide-y divide-slate-200">
                            @foreach ($course->checkouts as $checkout)
                                <div @class([
                                    'grid grid-cols-[1.6fr_0.7fr_0.8fr_1.1fr_0.9fr] gap-3 px-5 py-4',
                                    'bg-emerald-50/40' => $loop->first,
                                ])>
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-black text-slate-900">{{ $checkout->nome ?: ('Opção ' . $checkout->hours . 'h') }}</p>
                                            @if ($loop->first)
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                                                    Recomendada
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm leading-5 text-slate-600">
                                            {{ $checkout->descricao ?: 'Opção de matrícula com pagamento seguro e acesso após confirmação.' }}
                                        </p>
                                    </div>
                                    <div class="text-sm font-semibold text-slate-700">{{ $checkout->hours }}h</div>
                                    <div class="text-sm font-black text-slate-900">R$ {{ number_format((float) $checkout->price, 2, ',', '.') }}</div>
                                    <div class="text-sm text-slate-600">
                                        @if ($checkout->bonuses->isNotEmpty())
                                            <span class="font-semibold text-emerald-700">{{ $checkout->bonuses->count() }} bônus</span>
                                            <p class="mt-1 text-xs leading-4 text-slate-500">{{ \Illuminate\Support\Str::limit($checkout->bonuses->pluck('nome')->join(', '), 60) }}</p>
                                        @else
                                            <span class="text-slate-400">Sem bônus extras</span>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <a
                                            href="{{ $checkout->checkout_url }}"
                                            data-checkout-link
                                            data-checkout-source="checkout_compare_v4"
                                            data-checkout-id="{{ $checkout->id }}"
                                            data-checkout-hours="{{ $checkout->hours }}"
                                            data-checkout-price="{{ (float) $checkout->price }}"
                                            data-checkout-name="{{ $checkout->nome ?: ('Opção ' . $checkout->hours . 'h') }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-center text-sm font-black text-white transition hover:bg-slate-800"
                                        >
                                            Quero esta opção
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-3 p-3 md:hidden">
                        @foreach ($course->checkouts as $checkout)
                            <article @class([
                                'rounded-2xl border p-4',
                                'border-emerald-200 bg-emerald-50/40' => $loop->first,
                                'border-slate-200 bg-white' => ! $loop->first,
                            ])>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-black leading-tight text-slate-900">{{ $checkout->nome ?: ('Opção ' . $checkout->hours . 'h') }}</h3>
                                            @if ($loop->first)
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">Recomendada</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-slate-600">{{ $checkout->hours }}h • pagamento seguro</p>
                                    </div>
                                    <p class="text-lg font-black text-slate-900">R$ {{ number_format((float) $checkout->price, 2, ',', '.') }}</p>
                                </div>

                                <p class="mt-2 text-sm leading-5 text-slate-600">
                                    {{ $checkout->descricao ?: 'Escolha esta opção para seguir ao pagamento e concluir sua matrícula com segurança.' }}
                                </p>

                                @if ($checkout->bonuses->isNotEmpty())
                                    <details class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                                        <summary class="cursor-pointer text-sm font-semibold text-slate-900">Ver bônus incluídos ({{ $checkout->bonuses->count() }})</summary>
                                        <ul class="mt-2 space-y-2 text-sm text-slate-600">
                                            @foreach ($checkout->bonuses as $bonus)
                                                <li class="flex items-start gap-2">
                                                    <span class="mt-0.5 text-emerald-600">✓</span>
                                                    <span>{{ $bonus->nome }}@if($bonus->descricao) — {{ $bonus->descricao }}@endif</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif

                                <a
                                    href="{{ $checkout->checkout_url }}"
                                    data-checkout-link
                                    data-checkout-source="checkout_compare_v4"
                                    data-checkout-id="{{ $checkout->id }}"
                                    data-checkout-hours="{{ $checkout->hours }}"
                                    data-checkout-price="{{ (float) $checkout->price }}"
                                    data-checkout-name="{{ $checkout->nome ?: ('Opção ' . $checkout->hours . 'h') }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-3 inline-flex min-h-[50px] w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-center text-sm font-black text-white transition hover:bg-slate-800"
                                >
                                    Quero esta opção
                                </a>
                            </article>
                        @endforeach
                    </div>
                </div>

                <p class="text-sm text-slate-500">Você será direcionado para um ambiente seguro para concluir sua matrícula.</p>
            @else
                <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-100">
                    Nenhuma opção de matrícula disponível no momento.
                </div>
            @endif
        </section>

        @if ($course->owner)
            <section class="lp-section lp-deferred space-y-5 py-8">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Quem prepara este curso</h2>
                    <p class="text-sm text-slate-600 md:text-base">Uma pessoa real, com experiência prática, para ensinar de forma direta e aplicável.</p>
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
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Professor(a) responsável</p>
                                <h3 class="text-xl font-black leading-tight text-slate-900 md:text-2xl">
                                    {{ $course->owner->preferredName() }}
                                </h3>
                            </div>

                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <p class="text-sm leading-6 text-slate-600">
                                    {{ $course->owner->qualification ?: 'Professor com experiência prática na área e foco em ensino direto, simples e aplicável para quem está começando ou quer se atualizar.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="lp-section lp-deferred space-y-4 py-8">
            @php
                $faqItems = collect([
                    ['title' => 'Este curso tem vínculo com o governo?', 'body' => 'Não. Este curso faz parte de uma iniciativa social independente, sem vínculo com prefeitura, estado ou governo federal.'],
                    ['title' => 'Este curso garante emprego?', 'body' => 'Não. O curso ajuda na sua preparação profissional, fortalece seu currículo e sua prática, mas não garante contratação.'],
                    ['title' => 'Posso fazer o curso no meu celular?', 'body' => 'Claro! Você pode assistir as aulas no celular, tablet ou computador, quando e onde quiser.'],
                    ['title' => 'Como funciona a matrícula e o pagamento?', 'body' => 'Clique no botão de matrícula, escolha a opção e finalize o pagamento no ambiente seguro. Depois da confirmação, você recebe acesso para começar a estudar.'],
                    ['title' => 'O pagamento é mensalidade?', 'body' => 'Não. O valor informado nesta página é o pagamento único da matrícula (não é mensalidade), salvo se alguma opção diferente estiver claramente informada.'],
                    ['title' => 'Por quanto tempo posso acessar o curso?', 'body' => 'Você pode acessar o curso por 2 anos. No momento da matrícula, também existe a opção de adquirir acesso vitalício.'],
                    ['title' => 'Preciso fazer prova para ganhar o certificado?', 'body' => $course->finalTest ? 'Sim, tem um teste final bem prático para você provar que aprendeu e liberar o certificado.' : 'Não! Basta você concluir todas as aulas para ganhar seu certificado.'],
                    ['title' => 'Preciso de internet rápida?', 'body' => 'Não precisa de internet super rápida. A plataforma funciona bem com internet normal. As aulas carregam direitinho.'],
                    ['title' => 'Posso baixar as aulas para assistir depois?', 'body' => 'Você assiste online pela plataforma. Recomendamos uma conexão de internet estável para não perder o aprendizado.'],
                    ['title' => 'Como funciona o certificado?', 'body' => 'Depois de terminar o curso' . ($course->finalTest ? ' e passar no teste' : '') . ', você recebe um certificado oficial. Se quiser, pode pagar um valor bem acessível para receber uma cópia impressa.'],
                    ['title' => 'Vou precisar pagar algo além da matrícula?', 'body' => 'O valor principal é o da matrícula escolhida nesta página. Outros itens só serão cobrados se estiverem informados de forma clara (por exemplo, serviços opcionais).'],
                    ['title' => 'Como faço para me matricular?', 'body' => 'É simples: clique em "Quero me matricular", escolha a opção de pagamento e siga as etapas para finalizar sua matrícula.'],
                    ['title' => 'O que é a carta de estágio?', 'body' => 'É um documento complementar para apoiar sua apresentação profissional. Ela pode ajudar a mostrar sua dedicação aos estudos, mas não garante vaga de emprego.'],
                    ['title' => 'Posso cancelar minha matrícula depois?', 'body' => 'As condições de cancelamento e atendimento seguem as regras informadas no momento da compra. Se precisar, use os canais de suporte informados no pagamento e na plataforma.'],
                ]);
                $quickFaqs = $faqItems->take(5);
                $moreFaqs = $faqItems->slice(5);
            @endphp

            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Perguntas frequentes</h2>
                <p class="text-sm text-slate-600 md:text-base">Respostas rápidas para as dúvidas mais comuns antes da matrícula.</p>
            </div>

            <div class="grid gap-3">
                @foreach ($quickFaqs as $faq)
                    <details class="rounded-2xl border border-edux-line/70 bg-white p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-edux-primary">{{ $faq['title'] }}</summary>
                        <p class="mt-2 text-sm text-slate-600">{{ $faq['body'] }}</p>
                    </details>
                @endforeach
            </div>

            <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <summary class="cursor-pointer text-sm font-black text-slate-900">Ver mais perguntas</summary>
                <div class="mt-3 space-y-3">
                    @foreach ($moreFaqs as $faq)
                        <details class="rounded-xl border border-slate-200 bg-white p-4">
                            <summary class="cursor-pointer text-sm font-semibold text-edux-primary">{{ $faq['title'] }}</summary>
                            <p class="mt-2 text-sm text-slate-600">{{ $faq['body'] }}</p>
                        </details>
                    @endforeach
                </div>
            </details>
        </section>

        <section class="lp-section lp-deferred py-8">
            <div class="rounded-3xl border border-slate-300 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-5 text-white shadow-xl md:p-6">
                <div class="grid gap-5 md:grid-cols-[1.15fr_0.85fr] md:items-center">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/65">Fechamento de decisão</p>
                        <h2 class="text-2xl font-display text-white md:text-3xl">{{ $cityFinalCtaTitle }}</h2>
                        <p class="text-sm leading-6 text-white/80 md:text-base">
                            Iniciativa social independente, sem vínculo com governo. O curso ajuda na sua preparação e no fortalecimento do currículo, mas não garante contratação.
                        </p>
                        <div class="mt-3 inline-flex items-center rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-sm font-semibold text-white/90">
                            Valor social a partir de <span class="ml-2 text-base font-black text-white">{{ $stickyCheckoutPriceLabel }}</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <a
                            href="{{ $primaryCtaHref }}"
                            data-checkout-link
                            data-checkout-source="final_cta_v4"
                            data-checkout-hours="{{ $lpPrimaryCheckout?->hours ?? '' }}"
                            data-checkout-price="{{ $lpPrimaryCheckoutValue ?? '' }}"
                            data-checkout-name="{{ $primaryCheckoutName }}"
                            class="inline-flex min-h-[54px] w-full items-center justify-center rounded-2xl bg-white px-4 py-3 text-center text-sm font-black text-slate-900 transition hover:bg-slate-100"
                        >
                            Quero me matricular agora
                        </a>
                        <a
                            href="#matricula"
                            data-lp-cta-source="final_secondary_v4"
                            class="inline-flex min-h-[48px] w-full items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            Ver opções de matrícula
                        </a>
                        <p class="text-xs leading-5 text-white/60">
                            Pagamento seguro. Acesso liberado após confirmação do pagamento.
                        </p>
                    </div>
                </div>
            </div>
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

            @supports (content-visibility: auto) {
                .lp-deferred {
                    content-visibility: auto;
                    contain-intrinsic-size: 900px;
                }
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
                    'page_type' => 'catalogo_course_lp_v4',
                    'lp_variant' => 'v4',
                    'city_name' => $lpHasCityContext ? $lpCityDisplayName : null,
                    'has_city_context' => $lpHasCityContext,
                ]);
                const primaryCheckoutValue = @js($lpPrimaryCheckoutValue);
                const rawSearchParams = new URLSearchParams(window.location.search);
                const shouldSkipPrefiredViewContent = rawSearchParams.get('edux_vc_prefired') === '1'
                    && rawSearchParams.get('edux_source') === 'city_campaign';

                const queryParams = {};
                for (const [key, value] of rawSearchParams.entries()) {
                    if (!key || value === '') continue;
                    const safeKey = String(key).toLowerCase().replace(/[^a-z0-9_]/g, '_').slice(0, 40);
                    if (!safeKey) continue;
                    queryParams[`qp_${safeKey}`] = String(value).slice(0, 120);
                }

                if (typeof window.lpMetaTrack !== 'function') {
                    window.lpMetaTrack = function lpMetaTrack(eventName, extra = {}) {
                        window.eduxFirstPartyTrack?.(
                            eventName,
                            { ...courseMeta, ...queryParams, ...extra },
                            { source: 'meta_custom', pageType: courseMeta.page_type }
                        );

                        if (!window.fbq) return;
                        try {
                            window.fbq('trackCustom', eventName, { ...courseMeta, ...queryParams, ...extra });
                        } catch (_) {}
                    };
                }

                if (typeof window.lpMetaTrackStandard !== 'function') {
                    window.lpMetaTrackStandard = function lpMetaTrackStandard(eventName, params = {}) {
                        window.eduxFirstPartyTrack?.(
                            eventName,
                            { ...courseMeta, ...queryParams, ...params },
                            { source: 'meta_standard', pageType: courseMeta.page_type }
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

                    if (!url.searchParams.has('edux_lp_variant')) {
                        url.searchParams.set('edux_lp_variant', 'v4');
                    }

                    if (!url.searchParams.has('edux_course_slug')) {
                        url.searchParams.set('edux_course_slug', String(courseMeta.course_slug));
                    }

                    try {
                        const ids = typeof window.eduxFirstPartyIds === 'function' ? window.eduxFirstPartyIds() : null;
                        if (ids?.visitorId && !url.searchParams.has('edux_vid')) {
                            url.searchParams.set('edux_vid', ids.visitorId);
                        }
                        if (ids?.sessionId && !url.searchParams.has('edux_sid')) {
                            url.searchParams.set('edux_sid', ids.sessionId);
                        }
                    } catch (_) {}

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

                const prepareInlineCtas = () => {
                    document.querySelectorAll('a[data-lp-cta-source]').forEach((link) => {
                        if (link.dataset.lpCtaBound === '1') return;
                        link.dataset.lpCtaBound = '1';

                        link.addEventListener('click', () => {
                            window.lpMetaTrack('LPCtaClick', {
                                cta_source: link.dataset.lpCtaSource || 'inline_cta',
                            });
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
                    prepareInlineCtas();
                    initFaqTracking();

                    const saveData = navigator.connection?.saveData === true;
                    if (!saveData) {
                        if (typeof window.requestIdleCallback === 'function') {
                            window.requestIdleCallback(() => initSectionTracking(), { timeout: 1500 });
                        } else {
                            window.setTimeout(() => initSectionTracking(), 700);
                        }
                    }

                    window.lpMetaTrack('LPCourseView', {
                        page_path: window.location.pathname,
                        has_checkout: document.querySelectorAll('a[data-checkout-link]').length > 0,
                    });

                    if (!shouldSkipPrefiredViewContent && primaryCheckoutValue !== null && primaryCheckoutValue !== undefined) {
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

    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-700 bg-slate-950/95 p-3 text-white shadow-2xl backdrop-blur md:hidden">
        <div class="flex items-center gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/60">A partir de</p>
                <p class="text-base font-black leading-none text-white">{{ $stickyCheckoutPriceLabel }}</p>
            </div>
            <a
                href="{{ $stickyCtaHref }}"
                data-checkout-link
                data-checkout-source="mobile_sticky_cta_v4"
                data-checkout-hours="{{ $stickyCheckout?->hours ?? '' }}"
                data-checkout-price="{{ $stickyCheckoutValue ?? '' }}"
                data-checkout-name="{{ $stickyCheckoutName }}"
                class="inline-flex min-h-[50px] flex-1 items-center justify-center rounded-xl bg-amber-300 px-4 py-3 text-center text-sm font-black text-slate-950 shadow-md transition hover:bg-amber-200"
            >
                Matricular agora
            </a>
        </div>
        <p class="mt-2 text-xs leading-4 text-white/60">
            {{ $cityStickyFooterLine }}
        </p>
    </div>
@endsection
