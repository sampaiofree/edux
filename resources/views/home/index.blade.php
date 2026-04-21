@extends('layouts.public-marketing')

@section('title', $pageTitle)

@section('content')
    <article>
        <section class="w3-top-banner">
            <div class="header-top">
                <div class="w3-top-banner__logo-card">
                    @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" class="w3-top-banner__logo">
                    @else
                            <span class="w3-top-banner__logo-fallback">{{ $schoolName }}</span>
                    @endif
                </div>        
            </div>
            
            <div class="w3-shell">
                <div class="w3-top-banner__inner">
                    @if ($cityDisplayName)
                        <p class="w3-top-banner__city">📍 {{ $cityDisplayName }} e Região</p>
                    @endif

                    <h2>Inscrições abertas para o Programa Nacional de Capacitação Profissional.</h2>

                    <ul class="w3-top-banner__list">
                        @foreach ($topBannerItems as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>

                    <a href="#lista-cursos" class="w3-btn w3-btn--primary" style="margin-bottom: 30px">QUERO ME INSCREVER AGORA</a>
                </div>
            </div>
        </section>

        <!--<section class="w3-hero">
            <div class="w3-shell">
                <h1>{{ $heroTitle }}</h1>
                <p class="w3-hero__subtitle">{{ $heroSubtitle }}</p>
                <a href="#lista-cursos" class="w3-btn w3-btn--primary">QUERO ME INSCREVER AGORA</a>
            </div>
        </section>-->

        <section class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>4 motivos para você mudar de vida hoje</h2>
                    <p>Uma iniciativa de impacto social para quem busca o primeiro emprego ou uma promoção rápida.</p>
                </div>

                <div class="w3-reasons-grid">
                    @foreach ($reasonCards as $card)
                        <article class="w3-reason-card">
                            <div class="w3-reason-card__media">
                                <img
                                    src="{{ $card['image_url'] }}"
                                    alt="Ilustração: {{ $card['title'] }}"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                            <h3>{{ $card['title'] }}</h3>
                            <p>{{ $card['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="lista-cursos" class="w3-section w3-section--courses">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>Escolha sua profissão abaixo</h2>
                    <p>Selecione o curso desejado para ver os detalhes e garantir sua vaga com taxa social.</p>
                </div>

                <livewire:public-catalog context="home" />
            </div>
        </section>

        <section class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>Por que o {{ $schoolName }} é para você?</h2>
                </div>

                <div class="w3-why-grid">
                    <div>
                        @if ($featureImageUrl)
                            <img src="{{ $featureImageUrl }}" alt="Portal Jovem Empreendedor - Qualificação">
                        @else
                            <div class="w3-why-placeholder">
                                <div>
                                    <strong>{{ $schoolName }}</strong>
                                    <span>Sua ponte para o mercado de trabalho.</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        @foreach ($whyStudyParagraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="beneficios" class="w3-section w3-section--dark">
            <div class="w3-shell">
                <div class="w3-benefits-grid">
                    @foreach ($benefitColumns as $column)
                        <article>
                            <h2>{{ $column['title'] }}</h2>
                            <ul>
                                @foreach ($column['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="certificado" class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>Certificado Válido e Reconhecido em todo o Brasil</h2>
                    <p>Ao concluir, você baixa seu certificado com QR Code para comprovar sua qualificação para as empresas.</p>
                </div>

                <ul class="w3-check-list">
                    @foreach ($certificateChecklist as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>

                <div class="w3-certificate-image-wrap">
                    <img
                        src="{{ $certificatePreviewImageUrl }}"
                        alt="Exemplo de certificado com QR Code"
                        loading="lazy"
                        decoding="async"
                        fetchpriority="low"
                        width="1200"
                        height="900"
                        class="w3-certificate-image"
                    >
                </div>
            </div>
        </section>

        <section id="alunos" class="w3-section w3-section--deep-dark">
            <div class="w3-shell">
                <div class="w3-section__head w3-section__head--light">
                    <h2>O que dizem nossos alunos</h2>
                    <p>Milhares de vidas já foram transformadas através dos nossos cursos.</p>
                </div>

                <div class="w3-testimonials">
                    @foreach ($testimonials as $video)
                        <article class="w3-testimonial" x-data="{ loaded: false }">
                            <template x-if="!loaded">
                                <button type="button" class="w3-testimonial__trigger" @click="loaded = true" aria-label="Ver depoimento de {{ $video['label'] }}">
                                    <div class="w3-testimonial__placeholder">
                                        <div class="w3-testimonial__placeholder-copy">
                                            <strong>{{ $video['title'] }}</strong>
                                            <small>{{ $video['caption'] }}</small>
                                        </div>
                                    </div>
                                    <span class="w3-testimonial__play" aria-hidden="true">
                                        <span class="w3-testimonial__play-icon">▶</span>
                                    </span>
                                </button>
                            </template>

                            <template x-if="loaded">
                                <div class="w3-testimonial__player">
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

        <!--<section id="bonus_padrao" class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>Bônus e Materiais Inclusos</h2>
                    <p>Além das aulas, você recebe ferramentas para acelerar sua entrada no mercado.</p>
                </div>

                @foreach ($bonusItems as $item)
                    <article class="w3-bonus-item">
                        @if ($item['image_url'])
                            <img src="{{ $item['image_url'] }}" alt="{{ $item['title'] }}">
                        @else
                            <div class="w3-bonus-item__placeholder">{{ $item['placeholder'] }}</div>
                        @endif

                        <div>
                            <span>{{ $item['eyebrow'] }}</span>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>-->

        <section id="garantia" class="w3-section w3-section--dark-accent">
            <div class="w3-shell">
                <div class="w3-guarantee">
                    <div class="w3-guarantee__seal">
                        <img
                            src="{{ asset('images/home/garantia-7-dias.svg') }}"
                            alt="Selo 100% dinheiro de volta com garantia de 7 dias"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                    <p>Fique tranquilo! Você tem 7 dias para testar a plataforma. Se não gostar do conteúdo, devolvemos 100% do seu investimento na taxa social. Sem perguntas, sem burocracia.</p>
                </div>
            </div>
        </section>

        <section id="perguntas_e_respotas" class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>Tira-dúvidas rápido</h2>
                    <p>Respostas para as perguntas mais comuns dos nossos alunos.</p>
                </div>

                <div class="w3-faq">
                    @foreach ($faqItems as $item)
                        <details>
                            <summary>{{ $item['question'] }}</summary>
                            <p>{{ $item['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    </article>
@endsection

@php
    $metaAdsPixelId = trim((string) (\App\Models\SystemSetting::current()->meta_ads_pixel ?? ''));
@endphp

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
            const homeMeta = @js([
                'page_type' => 'public_home',
                'city_name' => $cityDisplayName ?: null,
            ]);
            const rawSearchParams = new URLSearchParams(window.location.search);
            const queryParams = {};

            for (const [key, value] of rawSearchParams.entries()) {
                if (!key || value === '') continue;
                const safeKey = String(key).toLowerCase().replace(/[^a-z0-9_]/g, '_').slice(0, 40);
                if (!safeKey) continue;
                queryParams[`qp_${safeKey}`] = String(value).slice(0, 120);
            }

            if (typeof window.homeMetaTrackStandard !== 'function') {
                window.homeMetaTrackStandard = function homeMetaTrackStandard(eventName, params = {}) {
                    window.eduxFirstPartyTrack?.(
                        eventName,
                        { ...homeMeta, ...queryParams, ...params },
                        { source: 'meta_standard', pageType: homeMeta.page_type }
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

            const shouldKeepDefaultNavigation = (event, link) => (
                event.defaultPrevented ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey ||
                (typeof event.button === 'number' && event.button !== 0) ||
                ((link.getAttribute('target') || '').toLowerCase() === '_blank')
            );

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

            const normalizeUrl = (href) => {
                if (shouldSkipHref(href)) return '';

                try {
                    return new URL(href, window.location.href).toString();
                } catch (_) {
                    return String(href || '').trim();
                }
            };

            const isWhatsappHref = (href) => {
                const normalized = normalizeUrl(href);
                if (!normalized) return false;

                try {
                    const url = new URL(normalized);
                    const hostname = String(url.hostname || '').toLowerCase();

                    return hostname === 'wa.me'
                        || hostname.endsWith('.wa.me')
                        || hostname === 'api.whatsapp.com'
                        || hostname.endsWith('.whatsapp.com');
                } catch (_) {
                    return normalized.includes('wa.me/') || normalized.includes('whatsapp.com/');
                }
            };

            const buildCourseViewContentPayload = (card) => {
                const courseId = Number(card.dataset.courseId || 0) || null;
                const courseSlug = String(card.dataset.courseSlug || '').trim();
                const courseTitle = String(card.dataset.courseTitle || '').trim();
                const coursePosition = Number(card.dataset.coursePosition || 0) || undefined;
                const coursePrice = Number(card.dataset.coursePrice || 0);
                const hasPrice = Number.isFinite(coursePrice) && coursePrice > 0;
                const cityScope = String(card.dataset.cityScope || '').trim();
                const contentId = courseId || courseSlug || courseTitle || 'course';

                const payload = {
                    content_name: courseTitle || 'Curso',
                    content_type: 'product',
                    content_category: 'course',
                    content_ids: [contentId],
                    source_page: homeMeta.page_type,
                    course_id: courseId || undefined,
                    course_slug: courseSlug || undefined,
                    course_position: coursePosition,
                    city_slug: cityScope || undefined,
                    city_name: homeMeta.city_name || undefined,
                    cta_source: 'home_course_card',
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

            const bindHomeCourseTracking = () => {
                if (document.documentElement.dataset.homeCourseTrackingBound === '1') {
                    return;
                }

                document.documentElement.dataset.homeCourseTrackingBound = '1';

                document.addEventListener('click', (event) => {
                    const clickTarget = event.target instanceof Element ? event.target : null;
                    if (!clickTarget) return;

                    const link = clickTarget.closest('a[data-course-link]');
                    if (!link) return;

                    const card = link.closest('[data-home-course-card="1"]');
                    if (!card) return;

                    const href = (link.getAttribute('href') || '').trim();
                    if (shouldSkipHref(href) || link.getAttribute('aria-disabled') === 'true') {
                        return;
                    }

                    const courseUrl = normalizeUrl(card.dataset.courseUrl || '');
                    const waitlistUrl = normalizeUrl(card.dataset.waitlistUrl || '');
                    const currentUrl = normalizeUrl(href);
                    const isWaitlist = waitlistUrl !== '' && currentUrl === waitlistUrl;
                    const isCoursePage = courseUrl !== '' && currentUrl === courseUrl;
                    const isWhatsappDestination = isWaitlist || isWhatsappHref(currentUrl);
                    const viewContentPayload = buildCourseViewContentPayload(card);

                    if (isWhatsappDestination) {
                        window.homeMetaTrackStandard('Lead', {
                            ...viewContentPayload,
                            lead_channel: 'whatsapp',
                            destination_type: 'whatsapp',
                        });
                    } else if (isCoursePage) {
                        window.homeMetaTrackStandard('ViewContent', viewContentPayload);
                    }

                    window.eduxFirstPartyFlush?.();

                    if ((!isCoursePage && !isWhatsappDestination) || shouldKeepDefaultNavigation(event, link)) {
                        return;
                    }

                    event.preventDefault();
                    const nextHref = isCoursePage
                        ? withViewContentPrefiredFlag(href)
                        : href;

                    window.setTimeout(() => {
                        window.location.assign(nextHref);
                    }, 120);
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindHomeCourseTracking, { once: true });
            } else {
                bindHomeCourseTracking();
            }
        })();
    </script>
@endpush
