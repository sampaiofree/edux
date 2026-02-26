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
        $courseAtuacaoItems = collect(preg_split('/\s*;\s*/u', (string) ($course->atuacao ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();
        $courseOQueFazItems = collect(preg_split('/\s*;\s*/u', (string) ($course->oquefaz ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();
        $courseDescriptionTabText = trim((string) ($course->description ?? ''));
    @endphp

    <article class="pb-8">
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
                    <p class="text-xs uppercase tracking-[0.2em] text-edux-primary">Curso profissionalizante online</p>
                    <h1 class="mt-1 font-display text-2xl leading-tight text-edux-primary md:text-3xl">
                        {{ $course->title }}
                    </h1>
                </div>
            </div>

            <div class="space-y-3">
                <ul class="space-y-2">
                    <li class="flex items-start gap-2">
                        <span aria-hidden="true" class="font-black leading-6 text-emerald-600">✔</span>
                        <span class="text-sm font-semibold leading-6 text-slate-700 md:text-sm">Ideal para quem busca o primeiro emprego</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span aria-hidden="true" class="font-black leading-6 text-emerald-600">✔</span>
                        <span class="text-sm font-semibold leading-6 text-slate-700 md:text-sm">Formação rápida e 100% online</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span aria-hidden="true" class="font-black leading-6 text-emerald-600">✔</span>
                        <span class="text-sm font-semibold leading-6 text-slate-700 md:text-sm">Certificado para fortalecer seu currículo</span>
                    </li>
                </ul>

                @if ($course->summary)
                    <p class="text-sm leading-6 text-slate-600 md:text-base">{{ $course->summary }}</p>
                @endif
            </div>

            @if (! $course->summary && $course->description)
                <p class="text-sm leading-6 text-slate-600 md:text-base">{{ $course->summary ?: $course->description }}</p>
            @endif

            <div
                class="rounded-2xl border border-edux-primary/15 bg-gradient-to-br from-white via-white to-edux-primary/5 p-4 ring-1 ring-edux-primary/10"
                x-data="{ activeCourseInfoTab: 'atuacao' }"
            >
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="activeCourseInfoTab = 'atuacao'"
                        class="inline-flex items-center justify-start rounded-full px-3 py-1 text-left text-xs font-bold transition"
                        :class="activeCourseInfoTab === 'atuacao' ? 'bg-edux-primary text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                    >
                        Onde um {{ $course->title }} pode atuar?
                    </button>
                    <button
                        type="button"
                        @click="activeCourseInfoTab = 'oquefaz'"
                        class="inline-flex items-center justify-start rounded-full px-3 py-1 text-left text-xs font-bold transition"
                        :class="activeCourseInfoTab === 'oquefaz' ? 'bg-edux-primary text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                    >
                        O que faz um {{ $course->title }} na prática?
                    </button>
                    <button
                        type="button"
                        @click="activeCourseInfoTab = 'descricao'"
                        class="inline-flex items-center justify-start rounded-full px-3 py-1 text-left text-xs font-bold transition"
                        :class="activeCourseInfoTab === 'descricao' ? 'bg-edux-primary text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                    >
                        Como é o curso {{ $course->title }}
                    </button>
                </div>

                <div class="mt-3 rounded-2xl bg-white/80 p-4 ring-1 ring-white/70">
                    <div x-show="activeCourseInfoTab === 'atuacao'">
                        @if ($courseAtuacaoItems->isNotEmpty())
                            <ul class="space-y-2">
                                @foreach ($courseAtuacaoItems as $item)
                                    <li class="flex items-start gap-2 text-sm leading-6 text-slate-700 md:text-base">
                                        <span aria-hidden="true" class="mt-0.5 font-black text-edux-primary">•</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm leading-6 text-slate-700 md:text-base">
                                Este curso ajuda você a se preparar para atuar em funções relacionadas à área, com foco em prática e rotina de trabalho.
                            </p>
                        @endif
                    </div>

                    <div x-show="activeCourseInfoTab === 'oquefaz'" style="display: none;">
                        @if ($courseOQueFazItems->isNotEmpty())
                            <ul class="space-y-2">
                                @foreach ($courseOQueFazItems as $item)
                                    <li class="flex items-start gap-2 text-sm leading-6 text-slate-700 md:text-base">
                                        <span aria-hidden="true" class="mt-0.5 font-black text-edux-primary">•</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm leading-6 text-slate-700 md:text-base">
                                Você aprende tarefas práticas da área para fortalecer sua rotina, sua organização e sua apresentação profissional.
                            </p>
                        @endif
                    </div>

                    <div x-show="activeCourseInfoTab === 'descricao'" style="display: none;">
                        @if ($courseDescriptionTabText !== '')
                            <p class="whitespace-pre-line text-sm leading-6 text-slate-700 md:text-base">{{ $courseDescriptionTabText }}</p>
                        @else
                            <p class="text-sm leading-6 text-slate-700 md:text-base">
                                Curso profissionalizante online com foco em capacitação acessível, linguagem simples e aplicação prática.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-4 rounded-3xl border border-edux-line/70 bg-white p-4 shadow-sm md:grid-cols-[1.1fr_0.9fr] md:p-5">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <h2 class="text-xl font-black leading-tight text-slate-900 md:text-2xl">Capacitação profissional com valor social acessível</h2>
                        <p class="text-sm leading-6 text-slate-600 md:text-base">
                            Este curso faz parte de uma iniciativa de capacitação acessível, criada para ampliar oportunidades de formação profissional a quem deseja entrar na área educacional.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <div class="flex items-start gap-3">
                                <x-ui.color-icon name="clock" tone="blue" size="sm" />
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $courseHoursLabel }} horas</p>
                                    <p class="mt-1 text-sm leading-5 text-slate-600">Conteúdo prático para aprender passo a passo.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <div class="flex items-start gap-3">
                                <x-ui.color-icon name="play-circle" tone="indigo" size="sm" />
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $totalLessonsCount ?: 'x' }} aulas</p>
                                    <p class="mt-1 text-sm leading-5 text-slate-600">Acesso online para assistir no seu tempo.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <div class="flex items-start gap-3">
                                <x-ui.color-icon name="badge-check" tone="green" size="sm" />
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Certificado</p>
                                    <p class="mt-1 text-sm leading-5 text-slate-600">Ao concluir o curso{{ $course->finalTest ? ' e passar no teste final' : '' }}.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <div class="flex items-start gap-3">
                                <x-ui.color-icon name="file-text" tone="amber" size="sm" />
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Carta de estágio</p>
                                    <p class="mt-1 text-sm leading-5 text-slate-600">Material complementar para apoiar sua apresentação profissional.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-950 p-4 text-white ring-1 ring-white/10 md:p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-300">Iniciativa de formação com valor social</p>
                    <p class="mt-2 text-3xl font-black leading-none md:text-4xl">
                        {{ $primaryCheckoutPriceLabel ?: 'Consultar' }}
                    </p>
                    <p class="mt-2 text-sm text-white/80">Investimento único para acesso completo ao curso e certificado.</p>
                    <p class="mt-1 text-xs text-white/60">
                        @if ($lpPrimaryCheckout)
                            {{ $primaryCheckoutName }}{{ $lpPrimaryCheckout?->hours ? ' • ' . $lpPrimaryCheckout->hours . 'h' : '' }}
                        @else
                            Veja as opções disponíveis abaixo.
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-white/60">Pagamento único da matrícula (não é mensalidade).</p>

                    <div class="mt-4 space-y-2">
                        <a
                            href="{{ $primaryCtaHref }}"
                            data-checkout-link
                            data-checkout-source="hero_offer_cta"
                            data-checkout-hours="{{ $lpPrimaryCheckout?->hours ?? '' }}"
                            data-checkout-price="{{ $lpPrimaryCheckoutValue ?? '' }}"
                            data-checkout-name="{{ $primaryCheckoutName }}"
                            class="inline-flex min-h-[52px] w-full items-center justify-center rounded-2xl bg-emerald-500 px-4 py-3 text-center text-sm font-black text-white shadow-[0_14px_30px_-18px_rgba(16,185,129,0.9)] transition hover:bg-emerald-600"
                        >
                            Quero começar minha formação
                        </a>
                        @if ($hasMultipleCheckouts)
                            <a
                                href="#matricula"
                                class="inline-flex min-h-[48px] w-full items-center justify-center rounded-2xl border border-white/20 bg-white/5 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-white/10"
                            >
                                Ver opções de matrícula
                            </a>
                        @endif
                    </div>

                    <p class="mt-3 text-sm leading-5 text-white/70">
                        Pagamento seguro. Acesso liberado conforme confirmação do pagamento.
                    </p>
                </div>
            </div>
        </section>

        <section class="lp-section space-y-4 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Para quem é este curso</h2>
                <p class="text-sm text-slate-600 md:text-base">Feito para quem quer aprender uma profissão com linguagem simples e aplicação prática.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <x-ui.color-icon name="sparkles" tone="blue" size="sm" />
                        <div>
                            <p class="text-sm font-black text-slate-900">Quem está começando do zero</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Você aprende passo a passo, sem precisar ter experiência anterior.</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <x-ui.color-icon name="briefcase" tone="green" size="sm" />
                        <div>
                            <p class="text-sm font-black text-slate-900">Quem quer melhorar o currículo</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Curso, certificado e prática ajudam a apresentar melhor seu perfil.</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <x-ui.color-icon name="smartphone" tone="indigo" size="sm" />
                        <div>
                            <p class="text-sm font-black text-slate-900">Quem precisa estudar pelo celular</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Aulas online para assistir quando puder, no seu ritmo.</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <x-ui.color-icon name="wallet" tone="amber" size="sm" />
                        <div>
                            <p class="text-sm font-black text-slate-900">Quem busca capacitação acessível</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Valor social e proposta de apoio à formação profissional.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section space-y-4 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Como funciona sua matrícula</h2>
                <p class="text-sm text-slate-600 md:text-base">Processo simples para começar hoje mesmo.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <div class="relative shrink-0">
                            <x-ui.color-icon name="list-check" tone="blue" size="sm" />
                            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-edux-primary px-1 text-[10px] font-black text-white">1</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Escolha a opção de matrícula</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Veja o valor e clique no botão para ir ao pagamento.</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <div class="relative shrink-0">
                            <x-ui.color-icon name="shield-check" tone="green" size="sm" />
                            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-edux-primary px-1 text-[10px] font-black text-white">2</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Conclua o pagamento com segurança</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Você será direcionado para finalizar a matrícula com segurança.</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <div class="relative shrink-0">
                            <x-ui.color-icon name="book-open" tone="amber" size="sm" />
                            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-edux-primary px-1 text-[10px] font-black text-white">3</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Comece a estudar</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Acesse as aulas, avance no seu ritmo e conclua para receber seu certificado.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                <p class="text-sm font-semibold text-slate-800">Você precisa apenas de celular ou computador com internet para começar.</p>
            </div>
        </section>

        <section class="lp-section space-y-4 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">O que você vai aprender</h2>
                <p class="text-sm text-slate-600 md:text-base">Veja os módulos e as aulas organizadas para você estudar com clareza.</p>
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
        </section>

        <section class="lp-section space-y-4 py-8" x-data="{ scroll(dir) { this.$refs.track.scrollBy({ left: (this.$refs.track.clientWidth * 0.9) * dir, behavior: 'smooth' }) } }">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Veja como fica seu certificado</h2>
                    <p class="text-sm text-slate-600 md:text-base">Prévia do certificado com exemplo de nome para você saber exatamente como ele é.</p>
                </div>
                <div class="hidden items-center gap-2 sm:flex">
                    <button type="button" @click="scroll(-1)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-edux-primary hover:bg-edux-primary/5" aria-label="Voltar">&lsaquo;</button>
                    <button type="button" @click="scroll(1)" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-edux-line bg-white text-edux-primary hover:bg-edux-primary/5" aria-label="Avançar">&rsaquo;</button>
                </div>
            </div>

            <div class="rounded-2xl border border-edux-line/70 bg-white p-4 ring-1 ring-slate-100">
                <p class="text-sm leading-6 text-slate-700 md:text-base">
                    Ao concluir o curso, você receberá certificado de curso livre com carga horária de {{ $courseHoursLabel }} horas.
                </p>
                <p class="mt-3 text-sm font-semibold text-slate-900">O certificado pode ser utilizado para:</p>
                <ul class="mt-2 space-y-2">
                    <li class="flex items-start gap-2 text-sm leading-6 text-slate-700 md:text-base">
                        <span aria-hidden="true" class="mt-0.5 font-black text-edux-primary">•</span>
                        <span>Complementar currículo</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm leading-6 text-slate-700 md:text-base">
                        <span aria-hidden="true" class="mt-0.5 font-black text-edux-primary">•</span>
                        <span>Atividades complementares escolares</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm leading-6 text-slate-700 md:text-base">
                        <span aria-hidden="true" class="mt-0.5 font-black text-edux-primary">•</span>
                        <span>Enriquecimento profissional</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm leading-6 text-slate-700 md:text-base">
                        <span aria-hidden="true" class="mt-0.5 font-black text-edux-primary">•</span>
                        <span>Apresentação em processos seletivos (conforme critérios da instituição)</span>
                    </li>
                </ul>
                <p class="mt-3 text-sm font-medium leading-6 text-slate-600">
                    Trata-se de curso livre de qualificação profissional, conforme legislação vigente.
                </p>
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
                <h2 class="text-2xl font-display text-edux-primary">O que você recebe na matrícula</h2>
                <p class="text-sm text-slate-600 md:text-base">Tudo para estudar, concluir o curso e apresentar melhor sua formação.</p>
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
                        <p class="text-sm font-black leading-none text-slate-900">Certificado de Conclusão</p>
                        <p class="mt-1 text-sm text-slate-500">Comprove a conclusão do curso e fortaleça seu currículo.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <x-ui.color-icon name="file-text" tone="amber" />
                    <div>
                        <p class="text-sm font-black leading-none text-slate-900">Carta de Estágio</p>
                        <p class="mt-1 text-sm text-slate-500">Documento complementar para apoiar sua apresentação em processos seletivos.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="lp-section space-y-5 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Carta de estágio para fortalecer sua apresentação profissional</h2>
                <p class="text-sm text-slate-600 md:text-base">
                    Ao concluir sua matrícula, você também pode contar com a carta de estágio assinada para complementar seu currículo e sua apresentação em processos seletivos.
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
                        <div class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">
                            Incluso no suporte ao aluno
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-xl font-black leading-tight text-slate-900">
                                Um complemento para mostrar seu compromisso com os estudos
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
                    </div>
                </div>
            </div>
        </section>

        <section
            id="matricula"
            class="lp-section space-y-5 py-8"
            x-data="{ scroll(dir) { if (!this.$refs.track) return; this.$refs.track.scrollBy({ left: (this.$refs.track.clientWidth * 0.9) * dir, behavior: 'smooth' }) } }"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Escolha sua forma de matrícula</h2>
                    <p class="text-sm text-slate-600 md:text-base">Escolha a opção que cabe no seu momento e siga para o pagamento com segurança.</p>
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
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                            Carga horária {{ $checkout->hours }}h
                                        </span>

                                        <div class="space-y-1">
                                            <h3 class="text-lg font-black leading-tight text-slate-900 md:text-xl">
                                                {{ $checkout->nome ?: ('Opção ' . $checkout->hours . 'h') }}
                                            </h3>
                                            <p class="text-sm leading-6 text-slate-600">
                                                {{ $checkout->descricao ?: 'Escolha esta opção para seguir ao pagamento e concluir sua matrícula com segurança.' }}
                                            </p>
                                        </div>
                                    </div>

                                    @if ($loop->first)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">
                                            Mais procurada
                                        </span>
                                    @endif
                                </div>

                                <div class="rounded-2xl bg-slate-950 p-4 text-white ring-1 ring-white/10">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Valor do curso</p>
                                    <div class="mt-2 flex items-end justify-between gap-3">
                                        <p class="text-3xl font-black leading-none md:text-4xl">
                                            R$ {{ number_format((float) $checkout->price, 2, ',', '.') }}
                                        </p>
                                        <p class="text-xs text-right text-white/70">
                                            pagamento seguro<br>confirmação rápida
                                        </p>
                                    </div>
                                </div>

                                @if ($checkout->bonuses->isNotEmpty())
                                    <div class="space-y-2">
                                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Bônus inclusos sem custo</p>
                                        <div class="grid gap-2">
                                            @foreach ($checkout->bonuses as $bonus)
                                                <div class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3">
                                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-xs font-black text-white">✓</span>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-semibold text-slate-900">{{ $bonus->nome }}</p>
                                                        @if ($bonus->descricao)
                                                            <p class="mt-1 text-sm leading-5 text-slate-500">{{ $bonus->descricao }}</p>
                                                        @endif
                                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-emerald-700">
                                                                Grátis
                                                            </span>
                                                            <span class="text-xs font-semibold text-slate-400 line-through">
                                                                de R$ {{ number_format((float) $bonus->preco, 2, ',', '.') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
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
                                            Quero começar agora
                                            <span aria-hidden="true">→</span>
                                        </span>
                                    </a>
                                    <p class="text-sm leading-5 text-slate-500">
                                        Você será direcionado para um ambiente seguro para concluir sua matrícula.
                                    </p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($hasMultipleCheckouts)
                    <p class="text-sm text-slate-500">Deslize para ver e comparar as opções de matrícula.</p>
                @endif
            @else
                <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-100">
                    Nenhuma opção de matrícula disponível no momento.
                </div>
            @endif
        </section>

        <section class="lp-section space-y-5 py-8">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Garantia e segurança</h2>
                <p class="text-sm text-slate-600 md:text-base">
                    Informações importantes para você iniciar sua formação com mais tranquilidade.
                </p>
            </div>

            <div class="grid gap-3">
                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <x-ui.color-icon name="smartphone" tone="indigo" size="sm" />
                        <p class="text-sm leading-6 text-slate-700 md:text-base">
                            Você pode acessar o curso pelo celular ou computador, de forma simples e organizada.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <x-ui.color-icon name="users" tone="blue" size="sm" />
                        <p class="text-sm leading-6 text-slate-700 md:text-base">
                            Caso tenha qualquer dificuldade de acesso, nossa equipe oferece suporte para ajudar.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-edux-line/70 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <x-ui.color-icon name="shield-check" tone="green" size="sm" />
                        <p class="text-sm leading-6 text-slate-700 md:text-base">
                            Pagamento seguro e acesso liberado conforme confirmação.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        @php
            $studentTestimonialsVideos = [
                ['id' => 'rejxwJ2lX-Q', 'label' => 'Depoimento de aluno 1'],
                ['id' => '1hekoAyPVRs', 'label' => 'Depoimento de aluno 2'],
                ['id' => 'Mnn2yIAlhZk', 'label' => 'Depoimento de aluno 3'],
                ['id' => '1qWXa9F0qBw', 'label' => 'Depoimento de aluno 4'],
            ];
        @endphp

        <section class="lp-section space-y-5 py-8" style="content-visibility:auto; contain-intrinsic-size: 900px;">
            <div class="space-y-2">
                <h2 class="text-2xl font-display text-edux-primary">Alunos que já participaram</h2>
                <p class="text-sm text-slate-600 md:text-base">
                    Veja relatos em vídeo de alunos. Os vídeos só são carregados quando você clicar em reproduzir, para não atrapalhar o carregamento da página.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($studentTestimonialsVideos as $video)
                    <article
                        class="overflow-hidden rounded-2xl border border-edux-line/70 bg-white shadow-sm"
                        x-data="{ loaded: false }"
                    >
                        <div class="aspect-video bg-slate-100">
                            <template x-if="!loaded">
                                <button
                                    type="button"
                                    @click="loaded = true"
                                    class="group relative flex h-full w-full items-center justify-center overflow-hidden bg-slate-950 text-white"
                                    aria-label="Reproduzir {{ $video['label'] }}"
                                >
                                    <img
                                        src="https://i.ytimg.com/vi/{{ $video['id'] }}/hqdefault.jpg"
                                        alt="{{ $video['label'] }}"
                                        class="h-full w-full object-cover opacity-90 transition duration-300 group-hover:scale-[1.02] group-hover:opacity-100"
                                        loading="lazy"
                                        decoding="async"
                                        fetchpriority="low"
                                    >
                                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-black/30"></div>
                                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-3 px-4 text-center">
                                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-white text-lg font-black text-edux-primary shadow-lg">
                                            ▶
                                        </span>
                                        <span class="text-sm font-semibold leading-5 text-white/95">Clique para reproduzir</span>
                                    </div>
                                </button>
                            </template>

                            <template x-if="loaded">
                                <iframe
                                    class="h-full w-full"
                                    src="https://www.youtube-nocookie.com/embed/{{ $video['id'] }}?autoplay=1&rel=0&modestbranding=1"
                                    title="{{ $video['label'] }}"
                                    loading="lazy"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                    referrerpolicy="strict-origin-when-cross-origin"
                                ></iframe>
                            </template>
                        </div>

                    </article>
                @endforeach
            </div>
        </section>

        @if ($course->owner)
            <section class="lp-section space-y-5 py-8">
                <div class="space-y-2">
                    <h2 class="text-2xl font-display text-edux-primary">Quem prepara este curso</h2>
                    <p class="text-sm text-slate-600 md:text-base">Conheça quem organizou as aulas e vai orientar sua jornada de aprendizado.</p>
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

        <section class="lp-section space-y-4 py-8">
            <h2 class="text-2xl font-display text-edux-primary">Perguntas frequentes</h2>
            @foreach ([
                ['title' => 'Por quanto tempo posso acessar o curso?', 'body' => 'Você pode acessar o curso por 2 anos. No momento da matrícula, também existe a opção de adquirir acesso vitalício.'],
                ['title' => 'Preciso fazer prova para ganhar o certificado?', 'body' => $course->finalTest ? 'Sim, tem um teste final bem prático para você provar que aprendeu e liberar o certificado.' : 'Não! Basta você concluir todas as aulas para ganhar seu certificado.'],
                ['title' => 'Posso fazer o curso no meu celular?', 'body' => 'Claro! Você pode assistir as aulas no celular, tablet ou computador, quando e onde quiser.'],
                ['title' => 'Este curso tem vínculo com o governo?', 'body' => 'Não. Este curso faz parte de uma iniciativa social independente, sem vínculo com prefeitura, estado ou governo federal.'],
                ['title' => 'Este curso garante emprego?', 'body' => 'Não. O curso ajuda na sua preparação profissional, fortalece seu currículo e sua prática, mas não garante contratação.'],
                ['title' => 'Preciso de internet rápida?', 'body' => 'Não precisa de internet super rápida. A plataforma funciona bem com internet normal. As aulas carregam direitinho.'],
                ['title' => 'Posso baixar as aulas para assistir depois?', 'body' => 'Você assiste online pela plataforma. Recomendamos uma conexão de internet estável para não perder o aprendizado.'],
                ['title' => 'Como funciona o certificado?', 'body' => 'Depois de terminar o curso' . ($course->finalTest ? ' e passar no teste' : '') . ', você recebe um certificado oficial. Se quiser, pode pagar um valor bem acessível para receber uma cópia impressa.'],
                ['title' => 'Como funciona a matrícula e o pagamento?', 'body' => 'Clique no botão de matrícula, escolha a opção e finalize o pagamento no ambiente seguro. Depois da confirmação, você recebe acesso para começar a estudar.'],
                ['title' => 'Vou precisar pagar algo além da matrícula?', 'body' => 'O valor principal é o da matrícula escolhida nesta página. Outros itens só serão cobrados se estiverem informados de forma clara (por exemplo, serviços opcionais).'],
                ['title' => 'Como faço para me matricular?', 'body' => 'É simples: clique em "Quero começar agora", escolha a opção de pagamento e siga as etapas para finalizar sua matrícula.'],
                ['title' => 'O que é a carta de estágio?', 'body' => 'É um documento complementar para apoiar sua apresentação profissional. Ela pode ajudar a mostrar sua dedicação aos estudos, mas não garante vaga de emprego.'],
                ['title' => 'Posso cancelar minha matrícula depois?', 'body' => 'As condições de cancelamento e atendimento seguem as regras informadas no momento da compra. Se precisar, use os canais de suporte informados no pagamento e na plataforma.'],
            ] as $faq)
                <details class="rounded-2xl border border-edux-line/70 p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-edux-primary">{{ $faq['title'] }}</summary>
                    <p class="mt-2 text-sm text-slate-600">{{ $faq['body'] }}</p>
                </details>
            @endforeach
        </section>

        <section class="lp-section py-8">
            <div class="rounded-3xl border border-edux-primary/15 bg-gradient-to-br from-white via-edux-primary/5 to-emerald-50 p-5 shadow-sm md:p-6">
                <div class="grid gap-4 md:grid-cols-[1.2fr_0.8fr] md:items-center">
                    <div class="space-y-2">
                        <h2 class="text-2xl font-display text-edux-primary">Comece sua capacitação profissional hoje</h2>
                        <p class="text-sm leading-6 text-slate-700 md:text-base">
                            Iniciativa social independente, sem vínculo com governo, com foco em formação acessível para ajudar você a se preparar melhor para oportunidades de trabalho.
                        </p>
                        <p class="text-sm font-medium leading-6 text-slate-600">Este curso não garante contratação.</p>
                    </div>

                    <div class="space-y-2">
                        <a
                            href="{{ $primaryCtaHref }}"
                            data-checkout-link
                            data-checkout-source="final_cta"
                            data-checkout-hours="{{ $lpPrimaryCheckout?->hours ?? '' }}"
                            data-checkout-price="{{ $lpPrimaryCheckoutValue ?? '' }}"
                            data-checkout-name="{{ $primaryCheckoutName }}"
                            class="inline-flex min-h-[54px] w-full items-center justify-center rounded-2xl bg-emerald-500 px-4 py-3 text-center text-sm font-black text-white shadow-[0_16px_30px_-18px_rgba(16,185,129,0.9)] transition hover:bg-emerald-600"
                        >
                            Quero começar minha formação
                        </a>
                        @if ($hasMultipleCheckouts)
                            <a
                                href="#matricula"
                                class="inline-flex min-h-[48px] w-full items-center justify-center rounded-2xl border border-edux-line bg-white px-4 py-3 text-center text-sm font-semibold text-edux-primary transition hover:bg-edux-background"
                            >
                                Ver opções de matrícula
                            </a>
                        @endif
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

    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-edux-line bg-white/95 p-3 shadow-2xl backdrop-blur md:hidden">
        <div class="flex items-center gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Apenas</p>
                <p class="text-base font-black leading-none text-slate-900">{{ $stickyCheckoutPriceLabel }}</p>
            </div>
            <a
                href="{{ $stickyCtaHref }}"
                data-checkout-link
                data-checkout-source="mobile_sticky_cta"
                data-checkout-hours="{{ $stickyCheckout?->hours ?? '' }}"
                data-checkout-price="{{ $stickyCheckoutValue ?? '' }}"
                data-checkout-name="{{ $stickyCheckoutName }}"
                class="inline-flex min-h-[50px] flex-1 items-center justify-center rounded-xl bg-edux-primary px-4 py-3 text-center text-sm font-black text-white shadow-md transition hover:opacity-95"
            >
                Quero começar agora
            </a>
        </div>
        <p class="mt-2 text-xs leading-4 text-slate-500">
            Iniciativa social independente • sem vínculo com governo
        </p>
    </div>
@endsection
