@extends('layouts.public-course-lp')

@section('title', $course->title.' | '.$schoolName)

@section('content')
    @php
        $metaAdsPixelId = trim((string) (\App\Models\SystemSetting::current()->meta_ads_pixel ?? ''));
        $planSection = $planSection ?? [];
        $primaryPlanCards = collect($planSection['primary_cards'] ?? [])->values();
        $additionalPlanCards = collect($planSection['additional_cards'] ?? [])->values();
        $stickyAction = $stickyAction ?? null;
        $panelAction = $panelAction ?? ['type' => 'none'];
    @endphp

    <svg xmlns="http://www.w3.org/2000/svg" class="lp-icon-sprite" aria-hidden="true">
        <symbol id="lp-icon-check-circle" viewBox="0 0 24 24">
            <path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2Zm-1 14-4-4 1.4-1.4 2.6 2.6 5.6-5.6L18 9Z"/>
        </symbol>
        <symbol id="lp-icon-users" viewBox="0 0 24 24">
            <path fill="currentColor" d="M8 11a4 4 0 1 1 4-4 4 4 0 0 1-4 4Zm8 1a3 3 0 1 1 3-3 3 3 0 0 1-3 3ZM2 20a6 6 0 0 1 12 0v1H2Zm13 1v-1a6 6 0 0 0-2.25-4.68A5.5 5.5 0 0 1 22 20v1Z"/>
        </symbol>
        <symbol id="lp-icon-clock" viewBox="0 0 24 24">
            <path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2Zm1 10.41 3.29 3.3-1.41 1.41L11 13.24V6h2Z"/>
        </symbol>
        <symbol id="lp-icon-certificate" viewBox="0 0 24 24">
            <path fill="currentColor" d="M12 3a7 7 0 0 0-4.66 12.22L6 21l6-2 6 2-1.34-5.78A7 7 0 0 0 12 3Zm0 10a3 3 0 1 1 3-3 3 3 0 0 1-3 3Z"/>
        </symbol>
        <symbol id="lp-icon-book" viewBox="0 0 24 24">
            <path fill="currentColor" d="M4 4a2 2 0 0 1 2-2h14v18H6a2 2 0 0 0-2 2Zm2 0v14h12V4Zm2 3h8v2H8Zm0 4h6v2H8Z"/>
        </symbol>
        <symbol id="lp-icon-chevron-down" viewBox="0 0 24 24">
            <path fill="currentColor" d="m12 16-6-6 1.4-1.4L12 13.2l4.6-4.6L18 10Z"/>
        </symbol>
        <symbol id="lp-icon-gift" viewBox="0 0 24 24">
            <path fill="currentColor" d="M20 7h-2.18A3 3 0 1 0 12 4.65 3 3 0 0 0 6.18 7H4a1 1 0 0 0-1 1v3h2v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9h2V8a1 1 0 0 0-1-1ZM14 4a1 1 0 1 1 0 2h-1V5a1 1 0 0 1 1-1ZM10 4a1 1 0 0 1 1 1v1h-1a1 1 0 1 1 0-2ZM7 11h4v8H7Zm6 8v-8h4v8Z"/>
        </symbol>
        <symbol id="lp-icon-briefcase" viewBox="0 0 24 24">
            <path fill="currentColor" d="M9 4h6a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v4h-9v-1h-2v1H2V9a2 2 0 0 1 2-2h3V6a2 2 0 0 1 2-2Zm0 3h6V6H9Zm13 8v5a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-5h9v1h2v-1Z"/>
        </symbol>
        <symbol id="lp-icon-rocket" viewBox="0 0 24 24">
            <path fill="currentColor" d="M14.5 2a8.3 8.3 0 0 0-6 2.49L5 8l3 3 3.51-3.5A6.2 6.2 0 0 1 16 6a6.2 6.2 0 0 1-1.5 4.49L11 14l3 3 3.51-3.5A8.3 8.3 0 0 0 20 7.5V2ZM4 13l-2 2 3 1 1 3 2-2Zm7 2-4 4h4v3l4-4Z"/>
        </symbol>
        <symbol id="lp-icon-tag" viewBox="0 0 24 24">
            <path fill="currentColor" d="M21 10 11 20 2 11V3h8Zm-13-2a2 2 0 1 0-2-2 2 2 0 0 0 2 2Z"/>
        </symbol>
        <symbol id="lp-icon-shield" viewBox="0 0 24 24">
            <path fill="currentColor" d="m12 2 8 3v6c0 5.55-3.84 10.74-8 12-4.16-1.26-8-6.45-8-12V5Zm-1 12 6-6-1.4-1.4-4.6 4.6-2.6-2.6L7 9.99Z"/>
        </symbol>
    </svg>

    <article data-lp-variant="base">
        <header class="lp-header">
            <div class="lp-shell lp-header__inner">
                <a class="lp-brand" href="#hero" aria-label="Topo da página">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $schoolName }}">
                    @else
                        <span class="lp-brand__fallback">{{ $schoolName }}</span>
                    @endif
                </a>

                <a href="#planos" class="lp-btn lp-btn--small" data-lp-primary-scroll-cta>
                    Garantir vaga
                </a>
            </div>
        </header>

        <main>
            <section id="hero" class="lp-hero lp-section">
                <div class="lp-shell lp-hero__grid">
                    <div class="lp-hero__content">
                        <p class="lp-badge">{{ $heroBadge }}</p>
                        <h1>{{ $course->title }}</h1>
                        <p class="lp-subtitle">{{ $heroSubtitle }}</p>

                        <ul class="lp-keypoints">
                            @foreach ($heroKeypoints as $item)
                                <li>
                                    <span class="lp-keypoint__icon" aria-hidden="true">
                                        <svg class="lp-icon lp-icon--sm"><use href="#lp-icon-{{ $item['icon'] }}"></use></svg>
                                    </span>
                                    <span>{{ $item['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if ($heroPrice)
                            <div class="lp-price-highlight" data-lp-hero-price>
                                <span class="lp-price-highlight__label">{{ $heroPrice['label'] }}</span>
                                <strong>{{ $heroPrice['value'] }}</strong>
                                @if (! empty($heroPrice['cash_line']))
                                    <small>{{ $heroPrice['cash_line'] }}</small>
                                @endif
                            </div>
                        @endif

                        <div class="lp-hero__actions">
                            <a href="#planos" class="lp-btn" data-lp-primary-scroll-cta>
                                Quero me inscrever agora
                            </a>
                        </div>

                        @if (count($heroProofItems) > 0)
                            <div class="lp-proof-inline">
                                @foreach ($heroProofItems as $item)
                                    <span class="lp-proof-inline__item">
                                        <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-{{ $item['icon'] }}"></use></svg>
                                        <span>{{ $item['text'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="lp-hero__media">
                        @if ($heroImageUrl)
                            <img
                                src="{{ $heroImageUrl }}"
                                alt="Capa do curso {{ $course->title }}"
                                fetchpriority="high"
                            >
                        @else
                            <div class="lp-hero__media-placeholder">
                                <div>
                                    <strong>{{ $course->title }}</strong>
                                    <span>Landing pública com informações de matrícula e conteúdo.</span>
                                </div>
                            </div>
                        @endif

                        <div class="lp-media-note lp-media-note--top">{{ $heroMediaNotes['top'] }}</div>
                        <div class="lp-media-note lp-media-note--bottom">{{ $heroMediaNotes['bottom'] }}</div>
                    </div>
                </div>
            </section>

            <section class="lp-strip">
                <div class="lp-shell lp-strip__grid">
                    @foreach ($proofStripItems as $item)
                        <article>
                            <span class="lp-strip__icon" aria-hidden="true">
                                <svg class="lp-icon"><use href="#lp-icon-{{ $item['icon'] }}"></use></svg>
                            </span>
                            <strong>{{ $item['value'] }}</strong>
                            <span>{{ $item['label'] }}</span>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="conteudo" class="lp-section">
                <div class="lp-shell">
                    <div class="lp-heading">
                        <h2 class="lp-heading__title">
                            <span>O que você vai aprender</span>
                        </h2>
                        <p>Conteúdo organizado para acelerar sua evolução, com foco em prática e aplicação na rotina profissional.</p>
                    </div>

                    <div class="lp-content-grid">
                        <div class="lp-modules-accordion">
                            @foreach ($modulesAccordion as $module)
                                <details class="lp-module-item">
                                    <summary class="lp-module-item__summary">
                                        <span class="lp-module-item__index">{{ $module['index_label'] }}</span>
                                        <span class="lp-module-item__title-row">
                                            <span class="lp-module-item__title-wrap">
                                                <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-book"></use></svg>
                                                <span>{{ $module['title'] }}</span>
                                            </span>
                                            <svg class="lp-icon lp-icon--sm lp-module-item__chevron" aria-hidden="true"><use href="#lp-icon-chevron-down"></use></svg>
                                        </span>
                                    </summary>
                                    <div class="lp-module-item__body">
                                        <ul>
                                            @foreach ($module['lessons'] as $lesson)
                                                <li>{{ $lesson }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </details>
                            @endforeach
                        </div>

                        <div class="lp-content-grid__aside">
                            <div class="lp-areas-block">
                                <h3 class="lp-areas__title">
                                    <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-briefcase"></use></svg>
                                    <span>Áreas de atuação</span>
                                </h3>
                                <ul class="lp-areas__list">
                                    @foreach ($areasOfPractice as $item)
                                        <li class="lp-areas__item">
                                            <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-check-circle"></use></svg>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="lp-practice-block">
                                <h3 class="lp-practice__title">
                                    <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-rocket"></use></svg>
                                    <span>Na prática, você vai aprender</span>
                                </h3>
                                <ul class="lp-practice__list">
                                    @foreach ($practiceHighlights as $item)
                                        <li class="lp-practice__item">
                                            <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-check-circle"></use></svg>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="bonus" class="lp-section lp-section--muted">
                <div class="lp-shell">
                    <div class="lp-heading">
                        <h2 class="lp-heading__title">
                            <span>Bônus e diferenciais</span>
                        </h2>
                        <p>Além do conteúdo principal, esta página destaca recursos extras e diferenciais factuais da sua experiência de estudo.</p>
                    </div>

                    <div class="lp-bonus-grid">
                        @foreach ($bonusCards as $card)
                            <article class="lp-bonus-card">
                                <h3>
                                    <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-{{ $card['icon'] }}"></use></svg>
                                    <span>{{ $card['title'] }}</span>
                                </h3>
                                <p>{{ $card['description'] }}</p>
                            </article>
                        @endforeach
                    </div>

                    @if ($extraBonusItems->isNotEmpty())
                        <div class="lp-bonus-extra">
                            <h3>
                                <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-gift"></use></svg>
                                <span>Bônus adicionais incluídos neste curso</span>
                            </h3>
                            <ul>
                                @foreach ($extraBonusItems as $item)
                                    <li>
                                        {{ $item['name'] }}
                                        @if ($item['description'] !== '')
                                            - {{ $item['description'] }}
                                        @endif
                                        @if ($item['price_label'])
                                            (valor de referência {{ $item['price_label'] }})
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>

            <section id="planos" class="lp-section lp-section--dark">
                <div class="lp-shell">
                    <div class="lp-heading lp-heading--light">
                        <h2 class="lp-heading__title">
                            <span>Escolha seu plano de acesso</span>
                        </h2>
                        <p>Veja as opções reais disponíveis para esta formação e siga para o checkout ou atendimento direto.</p>
                    </div>

                    <div class="lp-plans-layout">
                        <div>
                            @if (($planSection['mode'] ?? null) === 'plans')
                                <div class="lp-pricing lp-pricing--two">
                                    @foreach ($primaryPlanCards as $card)
                                        <article
                                            class="lp-price-card {{ $card['is_recommended'] ? 'lp-price-card--primary' : 'lp-price-card--secondary' }}"
                                            data-lp-plan-card-role="primary"
                                        >
                                            <p class="lp-price-card__tag">
                                                <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-tag"></use></svg>
                                                <span>{{ $card['tag'] }}</span>
                                            </p>
                                            <h3>{{ $card['heading'] }}</h3>
                                            <p class="lp-price-card__description">{{ $card['description'] }}</p>
                                            <p class="lp-price-card__value">{{ $card['price_label'] }}</p>
                                            <p class="lp-price-card__cash">{{ $card['cash_label'] }}</p>

                                            <ul>
                                                @foreach ($card['features'] as $feature)
                                                    <li>{{ $feature }}</li>
                                                @endforeach
                                            </ul>

                                            <div class="lp-price-card__cta-wrap">
                                                <a
                                                    href="{{ $card['action_url'] }}"
                                                    class="lp-btn {{ $card['is_recommended'] ? '' : 'lp-btn--outline' }}"
                                                    data-checkout-link
                                                    data-cta-type="checkout"
                                                    data-checkout-source="plan_card_cta"
                                                    data-checkout-id="{{ $card['checkout_id'] }}"
                                                    data-checkout-hours="{{ $card['checkout_hours'] }}"
                                                    data-checkout-price="{{ $card['checkout_price'] }}"
                                                    data-checkout-name="{{ $card['checkout_name'] }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                >
                                                    {{ $card['action_label'] }}
                                                </a>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>

                                @if ($additionalPlanCards->isNotEmpty())
                                    <div class="lp-pricing lp-pricing--extra">
                                        @foreach ($additionalPlanCards as $card)
                                            <article class="lp-price-card lp-price-card--extra" data-lp-plan-card-role="extra">
                                                <p class="lp-price-card__tag">
                                                    <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-tag"></use></svg>
                                                    <span>{{ $card['tag'] }}</span>
                                                </p>
                                                <h3>{{ $card['heading'] }}</h3>
                                                <p class="lp-price-card__description">{{ $card['description'] }}</p>
                                                <p class="lp-price-card__value">{{ $card['price_label'] }}</p>
                                                <p class="lp-price-card__cash">{{ $card['cash_label'] }}</p>

                                                <ul>
                                                    @foreach ($card['features'] as $feature)
                                                        <li>{{ $feature }}</li>
                                                    @endforeach
                                                </ul>

                                                <div class="lp-price-card__cta-wrap">
                                                    <a
                                                        href="{{ $card['action_url'] }}"
                                                        class="lp-btn lp-btn--outline"
                                                        data-checkout-link
                                                        data-cta-type="checkout"
                                                        data-checkout-source="plan_card_extra_cta"
                                                        data-checkout-id="{{ $card['checkout_id'] }}"
                                                        data-checkout-hours="{{ $card['checkout_hours'] }}"
                                                        data-checkout-price="{{ $card['checkout_price'] }}"
                                                        data-checkout-name="{{ $card['checkout_name'] }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                    >
                                                        {{ $card['action_label'] }}
                                                    </a>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @elseif (! empty($planSection['info_card']))
                                    <article class="lp-price-card lp-price-card--info" data-lp-plan-card-role="info">
                                        <p class="lp-price-card__tag">
                                            <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-shield"></use></svg>
                                            <span>Informações adicionais</span>
                                        </p>
                                        <h3>{{ $planSection['info_card']['heading'] }}</h3>
                                        <p class="lp-price-card__description">{{ $planSection['info_card']['description'] }}</p>
                                        <ul>
                                            @foreach ($planSection['info_card']['items'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </article>
                                @endif
                            @elseif (($planSection['mode'] ?? null) === 'whatsapp')
                                <div class="lp-pricing">
                                    <article class="lp-price-card lp-price-card--primary" data-lp-plan-card-role="whatsapp">
                                        <p class="lp-price-card__tag">
                                            <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-users"></use></svg>
                                            <span>Atendimento direto</span>
                                        </p>
                                        <h3>{{ $planSection['whatsapp_card']['title'] }}</h3>
                                        <p class="lp-price-card__description">{{ $planSection['whatsapp_card']['description'] }}</p>
                                        <div class="lp-price-card__cta-wrap">
                                            <a
                                                href="{{ $planSection['whatsapp_card']['action_url'] }}"
                                                class="lp-btn"
                                                data-checkout-link
                                                data-cta-type="whatsapp"
                                                data-checkout-source="plan_whatsapp_cta"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                {{ $planSection['whatsapp_card']['action_label'] }}
                                            </a>
                                        </div>
                                    </article>
                                </div>
                            @else
                                <div class="lp-empty-state" data-lp-plan-card-role="neutral">
                                    {{ $planSection['unavailable_message'] }}
                                </div>
                            @endif
                        </div>

                        <aside class="lp-plan-sidebar">
                            <p class="lp-plan-sidebar__eyebrow">{{ $panelAction['title'] }}</p>
                            <h3 class="lp-plan-sidebar__price">{{ $panelAction['price_label'] }}</h3>
                            <p class="lp-plan-sidebar__meta">{{ $panelAction['description'] }}</p>

                            <ul class="lp-plan-sidebar__list">
                                @foreach ($panelAction['meta_items'] as $item)
                                    <li>
                                        <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-check-circle"></use></svg>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            @if (! empty($panelAction['action_url']) && ! empty($panelAction['action_label']))
                                <div class="lp-plan-sidebar__actions">
                                    <a
                                        href="{{ $panelAction['action_url'] }}"
                                        class="lp-btn"
                                        data-checkout-link
                                        data-cta-type="{{ $panelAction['type'] }}"
                                        data-checkout-source="plan_sidebar_cta"
                                        data-checkout-id="{{ $panelAction['checkout_id'] }}"
                                        data-checkout-hours="{{ $panelAction['checkout_hours'] }}"
                                        data-checkout-price="{{ $panelAction['checkout_price'] }}"
                                        data-checkout-name="{{ $panelAction['checkout_name'] }}"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        {{ $panelAction['action_label'] }}
                                    </a>
                                    <a href="#planos" class="lp-btn lp-btn--outline" data-lp-primary-scroll-cta>
                                        Ver opções
                                    </a>
                                </div>
                            @endif

                            <p class="lp-plan-sidebar__foot">Acesso liberado conforme confirmação do pagamento ou retorno do atendimento.</p>
                        </aside>
                    </div>
                </div>
            </section>

            <section class="lp-section">
                <div class="lp-shell lp-certificate">
                    <div>
                        <div class="lp-heading">
                            <h2 class="lp-heading__title">
                                <span>Certificação para fortalecer seu currículo</span>
                            </h2>
                        </div>
                        <p>Seu certificado de conclusão é emitido digitalmente conforme a conclusão do curso, com informações da formação e possibilidade de validação na plataforma.</p>
                        <ul>
                            @foreach ($certificateHighlights as $item)
                                <li>
                                    <svg class="lp-icon lp-icon--sm" aria-hidden="true"><use href="#lp-icon-check-circle"></use></svg>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="lp-certificate__preview-stack">
                        <div class="lp-certificate__preview">
                            {!! $certificateFrontPreview !!}
                        </div>
                        <div class="lp-certificate__preview">
                            {!! $certificateBackPreview !!}
                        </div>
                    </div>
                </div>
            </section>

            <section id="depoimentos" class="lp-section lp-section--muted">
                <div class="lp-shell">
                    <div class="lp-heading">
                        <h2 class="lp-heading__title">
                            <span>Depoimentos de alunos</span>
                        </h2>
                        <p>Vídeos carregados apenas no clique para preservar o desempenho da página.</p>
                    </div>

                    <div class="lp-testimonials" aria-label="Depoimentos em vídeo">
                        @foreach ($testimonialCards as $video)
                            <article class="lp-testimonial" x-data="{ loaded: false }" data-video-id="{{ $video['id'] }}">
                                <template x-if="!loaded">
                                    <button
                                        type="button"
                                        class="lp-testimonial__trigger"
                                        @click="loaded = true"
                                        data-lp-testimonial-trigger
                                        data-video-id="{{ $video['id'] }}"
                                        aria-label="Assistir {{ $video['label'] }}"
                                    >
                                        <div class="lp-testimonial__placeholder">
                                            <div>
                                                <strong>{{ $video['title'] }}</strong>
                                                <small>{{ $video['caption'] }}</small>
                                            </div>
                                        </div>
                                        <span class="lp-testimonial__play" aria-hidden="true">▶</span>
                                    </button>
                                </template>

                                <template x-if="loaded">
                                    <div class="lp-testimonial__player">
                                        <iframe
                                            src="https://www.youtube-nocookie.com/embed/{{ $video['id'] }}?autoplay=1&rel=0&modestbranding=1"
                                            title="{{ $video['label'] }}"
                                            loading="lazy"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen
                                            referrerpolicy="strict-origin-when-cross-origin"
                                        ></iframe>
                                    </div>
                                </template>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="faq" class="lp-section lp-section--muted">
                <div class="lp-shell">
                    <div class="lp-heading">
                        <h2 class="lp-heading__title">
                            <span>Perguntas frequentes</span>
                        </h2>
                    </div>
                    <div class="lp-faq">
                        @foreach ($faqItems as $faq)
                            <details>
                                <summary>{{ $faq['question'] }}</summary>
                                <p>{{ $faq['answer'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="lp-final-cta">
                <div class="lp-shell lp-final-cta__inner">
                    <div>
                        <h2>Pronto para acelerar sua carreira?</h2>
                        <p>Veja as opções disponíveis, escolha seu plano e siga para a próxima etapa da matrícula com mais clareza.</p>
                    </div>
                    <a href="#planos" class="lp-btn" data-lp-primary-scroll-cta>
                        Quero garantir minha vaga agora
                    </a>
                </div>
            </section>
        </main>

        <footer class="lp-footer">
            <div class="lp-shell lp-footer__inner">
                <div>
                    <strong>{{ $schoolName }}</strong>
                    <p>© {{ now()->year }} {{ $schoolName }}. Todos os direitos reservados.</p>
                </div>
                <div class="lp-footer__links">
                    <a href="{{ route('legal.terms') }}">Termos</a>
                    <a href="{{ route('legal.privacy') }}">Privacidade</a>
                </div>
            </div>
        </footer>

        @if ($stickyAction)
            <div class="lp-sticky-cta">
                <div class="lp-sticky-cta__inner">
                    <div class="lp-sticky-cta__meta">
                        <p class="lp-sticky-cta__meta-label">{{ $stickyAction['type'] === 'whatsapp' ? 'Atendimento' : 'Valor' }}</p>
                        <p class="lp-sticky-cta__meta-value">{{ $stickyAction['price_label'] }}</p>
                    </div>
                    <a
                        href="{{ $stickyAction['url'] }}"
                        class="lp-btn"
                        data-checkout-link
                        data-cta-type="{{ $stickyAction['type'] }}"
                        data-checkout-source="mobile_sticky_cta"
                        data-checkout-id="{{ $stickyAction['checkout_id'] }}"
                        data-checkout-hours="{{ $stickyAction['checkout_hours'] }}"
                        data-checkout-price="{{ $stickyAction['checkout_price'] }}"
                        data-checkout-name="{{ $stickyAction['checkout_name'] }}"
                        target="_blank"
                        rel="noopener"
                    >
                        {{ $stickyAction['label'] }}
                    </a>
                </div>
            </div>
        @endif
    </article>
@endsection

@push('scripts')
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
            const primaryCheckoutValue = @js($panelAction['checkout_price'] ?? null);
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

            const isWhatsAppHref = (href) => {
                try {
                    const url = new URL(href, window.location.href);
                    return ['wa.me', 'api.whatsapp.com', 'www.whatsapp.com'].includes(url.hostname);
                } catch (_) {
                    return false;
                }
            };

            const withTrackingParams = (href) => {
                if (shouldSkipHref(href) || isWhatsAppHref(href)) return href;

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

                    const trackedHref = withTrackingParams(originalHref);
                    if (trackedHref !== originalHref) {
                        link.setAttribute('href', trackedHref);
                    }

                    if (link.dataset.lpCheckoutBound === '1') return;
                    link.dataset.lpCheckoutBound = '1';

                    link.addEventListener('click', () => {
                        const checkoutName = link.dataset.checkoutName || '';
                        const checkoutSource = link.dataset.checkoutSource || 'checkout_cta';
                        const checkoutHours = Number(link.dataset.checkoutHours || 0) || null;
                        const checkoutPrice = Number(link.dataset.checkoutPrice || 0);
                        const ctaType = link.dataset.ctaType || 'checkout';

                        window.lpMetaTrack('LPCheckoutClick', {
                            checkout_source: checkoutSource,
                            checkout_name: checkoutName || undefined,
                            checkout_hours: checkoutHours ?? undefined,
                            checkout_price: Number.isFinite(checkoutPrice) && checkoutPrice > 0 ? checkoutPrice : undefined,
                            cta_type: ctaType,
                        });

                        if (ctaType === 'whatsapp') {
                            window.lpMetaTrackStandard('Lead', {
                                content_name: checkoutName || courseMeta.course_title,
                                content_category: 'course',
                            });
                            return;
                        }

                        if (Number.isFinite(checkoutPrice) && checkoutPrice > 0) {
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

            const initScrollCtaTracking = () => {
                document.querySelectorAll('[data-lp-primary-scroll-cta]').forEach((button, index) => {
                    if (button.dataset.lpScrollBound === '1') return;
                    button.dataset.lpScrollBound = '1';

                    button.addEventListener('click', () => {
                        window.lpMetaTrack('LPScrollCtaClick', {
                            cta_index: index + 1,
                            cta_label: button.textContent.trim().slice(0, 80),
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
                document.querySelectorAll('#faq details').forEach((details, index) => {
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

            const initTestimonialTracking = () => {
                document.querySelectorAll('[data-lp-testimonial-trigger]').forEach((button, index) => {
                    if (button.dataset.lpTestimonialBound === '1') return;
                    button.dataset.lpTestimonialBound = '1';

                    button.addEventListener('click', () => {
                        window.lpMetaTrack('LPTestimonialPlay', {
                            testimonial_index: index + 1,
                            video_id: button.dataset.videoId || undefined,
                        });
                    });
                });
            };

            const boot = () => {
                prepareCheckoutLinks();
                initScrollCtaTracking();
                initSectionTracking();
                initFaqTracking();
                initTestimonialTracking();

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
