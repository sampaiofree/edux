@extends('layouts.public-marketing')

@section('title', $pageTitle)

@section('content')
    <article>
        <section class="w3-top-banner">
            <div class="w3-shell">
                <div class="w3-top-banner__inner">
                    <div class="w3-top-banner__logo-card">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" class="w3-top-banner__logo">
                        @else
                            <span class="w3-top-banner__logo-fallback">{{ $schoolName }}</span>
                        @endif
                    </div>

                    <h2>Cursos online organizados para quem quer estudar com mais clareza.</h2>

                    <ul class="w3-top-banner__list">
                        @foreach ($topBannerItems as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <section class="w3-hero">
            <div class="w3-shell">
                <h1>{{ $heroTitle }}</h1>
                <p class="w3-hero__subtitle">{{ $heroSubtitle }}</p>
                <a href="#lista-cursos" class="w3-btn w3-btn--primary">Escolher meu curso agora!</a>
            </div>
        </section>

        <section class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>4 motivos para começar uma nova etapa com mais preparo</h2>
                    <p>Uma estrutura pública simples para explorar cursos, comparar opções e seguir direto para a página da formação.</p>
                </div>

                <div class="w3-reasons-grid">
                    @foreach ($reasonCards as $card)
                        <article class="w3-reason-card">
                            <div class="w3-reason-card__icon">{{ $card['badge'] }}</div>
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
                    <h2>Escolha abaixo o curso ideal para o seu momento</h2>
                    <p>Os cards desta home levam para as páginas públicas locais de cada curso publicado.</p>
                </div>

                <livewire:public-catalog context="home" />
            </div>
        </section>

        <section class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>Por que estudar no {{ $schoolName }}?</h2>
                </div>

                <div class="w3-why-grid">
                    <div>
                        @if ($featureImageUrl)
                            <img src="{{ $featureImageUrl }}" alt="Imagem de destaque do {{ $schoolName }}">
                        @else
                            <div class="w3-why-placeholder">
                                <div>
                                    <strong>{{ $schoolName }}</strong>
                                    <span>Experiência pública simples, clara e objetiva.</span>
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
                    <h2>Conclua seu curso e receba seu <span>certificado digital</span></h2>
                    <p>O certificado segue as regras da plataforma e fica disponível conforme a conclusão do curso e dos requisitos aplicáveis.</p>
                </div>

                <ul class="w3-check-list">
                    @foreach ($certificateChecklist as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>

                <div class="w3-certificate-image-wrap">
                    <div class="w3-certificate-mock">
                        <div class="w3-certificate-mock__eyebrow">Certificado digital</div>
                        <h3>{{ $schoolName }}</h3>
                        <p>Documento emitido conforme as regras da plataforma e a conclusão do curso.</p>
                        <div class="w3-certificate-mock__name">Aluno(a) concluinte</div>
                        <div class="w3-certificate-mock__footer">
                            <span class="w3-certificate-mock__seal">OK</span>
                            <p>Disponibilizado no ambiente do aluno quando os requisitos forem atendidos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="alunos" class="w3-section w3-section--deep-dark">
            <div class="w3-shell">
                <div class="w3-section__head w3-section__head--light">
                    <h2>Relatos em vídeo de quem já passou pela plataforma</h2>
                    <p>Os vídeos só são carregados quando você decide assistir.</p>
                </div>

                <div class="w3-testimonials">
                    @foreach ($testimonials as $video)
                        <article class="w3-testimonial" x-data="{ loaded: false }">
                            <template x-if="!loaded">
                                <button type="button" class="w3-testimonial__trigger" @click="loaded = true" aria-label="Reproduzir {{ $video['label'] }}">
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

        <section id="bonus_padrao" class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>Materiais e apoios que podem complementar sua jornada</h2>
                    <p>A estrutura visual da home usa assets locais do projeto ou placeholders próprios.</p>
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
        </section>

        <section id="garantia" class="w3-section w3-section--dark-accent">
            <div class="w3-shell">
                <div class="w3-guarantee">
                    <div class="w3-guarantee__seal">100%<br>online</div>
                    <p>Esta é uma página pública de apresentação de cursos. O objetivo é facilitar sua leitura, sua comparação entre formações e o acesso às páginas locais de matrícula com informações mais completas.</p>
                </div>
            </div>
        </section>

        <section id="perguntas_e_respotas" class="w3-section">
            <div class="w3-shell">
                <div class="w3-section__head">
                    <h2>Perguntas frequentes</h2>
                    <p>Informações rápidas para decidir com mais segurança antes de abrir a página do curso.</p>
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
