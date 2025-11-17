@extends('layouts.student')

@section('title', $course->title)

@section('content')
    @php
        $settings = \App\Models\SystemSetting::current();
        $frontImage = $settings->assetUrl('default_certificate_front_path');
        $backImage = $settings->assetUrl('default_certificate_back_path');
        $titleSize = $settings->certificate_title_size ?? 68;
        $subtitleSize = $settings->certificate_subtitle_size ?? 52;
        $bodySize = $settings->certificate_body_size ?? 40;
        $courseStart = optional($course->created_at)->format('d/m/Y') ?? '---';
        $courseEnd = now()->format('d/m/Y');
        $durationHours = $course->duration_minutes
            ? round($course->duration_minutes / 60, 1) . ' horas'
            : '---';
        $studentName = auth()->check() ? auth()->user()->preferredName() : 'Seu nome aqui';
        $programText = $course->modules->flatMap(fn ($module) => [
            "Modulo {$module->position} - {$module->title}",
            ...$module->lessons->map(fn ($lesson) => "{$lesson->position}. {$lesson->title}")->all(),
            '',
        ])->implode("\n");
    @endphp

    <article class="space-y-8 x-1">
        <section class="rounded-card bg-white shadow-card overflow-hidden">
            @if ($course->promo_video_url)
                <div class="aspect-video w-full">
                    <iframe src="{{ $course->promo_video_url }}" class="h-full w-full" allowfullscreen loading="lazy"></iframe>
                </div>
            @elseif ($course->coverImageUrl())
                <img src="{{ $course->coverImageUrl() }}" alt="{{ $course->title }}" class="h-64 w-full object-cover md:h-96">
            @endif
            <div class="space-y-3 p-6">
                <p class="text-xs uppercase tracking-wide text-edux-primary">Curso online</p>
                <h1 class="font-display text-3xl text-edux-primary">{{ $course->title }}</h1>
                <p class="text-slate-600">{{ $course->description }}</p>
                <div class="flex flex-wrap gap-4 text-sm text-slate-500">
                    <span>Alunos: <strong class="text-edux-primary">{{ $studentCount }}</strong></span>
                    <span>Carga horaria: <strong class="text-edux-primary">{{ $course->duration_minutes ?? '---' }} min</strong></span>
                </div>
                @auth
                    <form method="POST" action="{{ route('learning.courses.enroll', $course) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="edux-btn w-full inline-flex justify-center"> inscreva-se grátis</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="edux-btn mt-4 inline-flex">Crie sua conta para se inscrever</a>
                @endauth
            </div>
        </section>

        <section class="rounded-card bg-white p-6 shadow-card space-y-4">
            <h2 class="text-2xl font-display text-edux-primary">Conteudo programatico</h2>
            <div class="space-y-3">
                @foreach ($course->modules as $module)
                    <article class="rounded-2xl border border-edux-line/70 p-4">
                        <p class="text-sm font-semibold text-edux-primary">Modulo {{ $module->position }} - {{ $module->title }}</p>
                        <ul class="mt-2 space-y-1 text-sm text-slate-600">
                            @foreach ($module->lessons as $lesson)
                                <li>- {{ $lesson->title }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rounded-card bg-white p-6 shadow-card space-y-4" x-data="{ modal: null }" @keydown.escape.window="modal = null">
            <h2 class="text-2xl font-display text-edux-primary">Modelo do certificado</h2>
            <p class="text-sm text-slate-600">A imagem abaixo ja combina o fundo com os textos do curso e do aluno, apenas para visualizacao.</p>
            <div class="grid gap-4 md:grid-cols-2">
                <figure class="overflow-hidden rounded-2xl border border-edux-line/70 bg-edux-background" @click="modal = $refs.certFront?.src">
                    <img data-cert-front data-base="{{ $frontImage }}" data-course="{{ $course->title }}" data-student="{{ $studentName }}" data-title-size="{{ $titleSize }}" data-subtitle-size="{{ $subtitleSize }}" alt="Modelo frente do certificado" class="w-full cursor-zoom-in object-cover transition hover:scale-[1.01]" x-ref="certFront">
                    <figcaption class="px-3 py-2 text-center text-xs text-slate-500">Clique para ampliar</figcaption>
                </figure>
                <figure class="overflow-hidden rounded-2xl border border-edux-line/70 bg-edux-background" @click="modal = $refs.certBack?.src">
                    <img data-cert-back data-base="{{ $backImage }}" data-course="{{ $course->title }}" data-text="{{ $programText }}" data-body-size="{{ $bodySize }}" alt="Modelo verso do certificado" class="w-full cursor-zoom-in object-cover transition hover:scale-[1.01]" x-ref="certBack">
                    <figcaption class="px-3 py-2 text-center text-xs text-slate-500">Clique para ampliar</figcaption>
                </figure>
            </div>

            <div x-show="modal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click="modal = null">
                <img :src="modal" alt="Certificado ampliado" class="max-h-[90vh] max-w-full rounded-2xl shadow-2xl">
            </div>

            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const frontTarget = document.querySelector('[data-cert-front]');
                        const backTarget = document.querySelector('[data-cert-back]');

                        const splitLines = (ctx, text, maxWidth) => {
                            const words = text.split(' ');
                            const lines = [];
                            let line = '';

                            words.forEach((word, idx) => {
                                const testLine = `${line}${word} `;
                                if (ctx.measureText(testLine).width > maxWidth && idx > 0) {
                                    lines.push(line.trim());
                                    line = `${word} `;
                                } else {
                                    line = testLine;
                                }
                            });
                            lines.push(line.trim());
                            return lines;
                        };

                        const fontSize = (value, fallback, scale) => Math.max(10, Math.round(((parseInt(value, 10) || fallback)) * scale));

                        const scaleBodyText = (ctx, lines, maxHeight, baseSize) => {
                            let lineHeight = Math.round(baseSize * 1.1);
                            let totalHeight = lines.length * lineHeight;
                            if (totalHeight > maxHeight) {
                                const factor = Math.max(0.6, maxHeight / totalHeight);
                                const newSize = Math.round(baseSize * factor);
                                lineHeight = Math.round(newSize * 1.1);
                                totalHeight = lines.length * lineHeight;
                                ctx.font = `${newSize}px "Inter", Arial, sans-serif`;
                            }
                            return { lineHeight, totalHeight };
                        };

                        const drawFront = (ctx, canvas, opts) => {
                            const centerX = canvas.width / 2;
                            const { titlePx, subtitlePx, bodyPx } = opts;
                            let y = canvas.height * 0.3;

                            ctx.fillStyle = 'rgba(17, 24, 39, 0.8)';
                            ctx.textAlign = 'center';
                            ctx.font = `${bodyPx}px "Inter", Arial, sans-serif`;
                            ctx.fillText('Certificamos que', centerX, y);

                            y += titlePx * 1.1;
                            ctx.font = `${titlePx}px "Inter", Arial, sans-serif`;
                            ctx.fillText(opts.student || 'Aluno', centerX, y);

                            y += bodyPx * 1.3;
                            ctx.font = `${bodyPx}px "Inter", Arial, sans-serif`;
                            ctx.fillText('concluiu com 100% de aproveitamento o curso', centerX, y);

                            y += subtitlePx * 1.2;
                            ctx.font = `${subtitlePx}px "Inter", Arial, sans-serif`;
                            ctx.fillText(opts.course || 'Curso', centerX, y);

                            y += bodyPx * 1.4;
                            ctx.font = `${bodyPx}px "Inter", Arial, sans-serif`;
                            const line6 = `Com carga horária de ${opts.duration || '---'}, no período de ${opts.start || '---'} a ${opts.end || '---'}, promovido pelo portal de cursos EDUX.`;
                            const maxWidth = canvas.width * 0.86;
                            const lines = splitLines(ctx, line6, maxWidth);
                            const lineHeight = Math.round(bodyPx * 1.2);
                            lines.forEach((line) => {
                                ctx.fillText(line, centerX, y);
                                y += lineHeight;
                            });
                        };

                        const compose = (baseUrl, title, subtitle, target, options = {}) => {
                            if (!baseUrl || !target) return;
                            if (baseUrl === 'null' || baseUrl === 'undefined') return;
                            const img = new Image();
                            img.crossOrigin = 'anonymous';
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                canvas.width = img.width;
                                canvas.height = img.height;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0);

                                const paddingX = canvas.width * 0.05;
                                const centerX = canvas.width / 2;
                                ctx.textAlign = 'center';
                                const scale = canvas.width / 1920;

                                const titlePx = fontSize(options.titleSize, 68, scale);
                                const subtitlePx = fontSize(options.subtitleSize, 52, scale);
                                const bodyPx = fontSize(options.bodySize, 40, scale);
                                if (options.layout === 'front') {
                                    drawFront(ctx, canvas, {
                                        course: title,
                                        student: subtitle,
                                        titlePx,
                                        subtitlePx,
                                        bodyPx,
                                        duration: options.duration,
                                        start: options.start,
                                        end: options.end,
                                    });
                                }

                                if (options.layout !== 'front' && options.watermark) {
                                    ctx.save();
                                    ctx.translate(canvas.width / 2, canvas.height / 2);
                                    ctx.rotate(-Math.PI / 6);
                                    const gradient = ctx.createLinearGradient(-canvas.width / 2, 0, canvas.width / 2, canvas.height / 2);
                                    gradient.addColorStop(0, 'rgba(255,255,255,0)');
                                    gradient.addColorStop(0.5, 'rgba(59,130,246,0.14)');
                                    gradient.addColorStop(1, 'rgba(0,0,0,0)');
                                    ctx.fillStyle = gradient;
                                    ctx.font = `bold ${Math.round(canvas.width * 0.16)}px "Inter", Arial, sans-serif`;
                                    ctx.textAlign = 'center';
                                    ctx.fillText('MODELO', 0, 0);
                                    ctx.restore();
                                }

                                if (options.layout !== 'front' && options.bodyText) {
                                    ctx.fillStyle = 'rgba(55, 65, 81, 0.95)';
                                    ctx.font = `${bodyPx}px "Inter", Arial, sans-serif`;
                                    ctx.textAlign = 'center';
                                    const maxWidth = canvas.width - paddingX * 2;
                                    const lines = splitLines(ctx, options.bodyText, maxWidth);
                                    const { lineHeight, totalHeight } = scaleBodyText(ctx, lines, canvas.height * 0.5, bodyPx);
                                    let y = (canvas.height - totalHeight) / 2;
                                    lines.forEach((line) => {
                                        ctx.fillText(line, centerX, y);
                                        y += lineHeight;
                                    });
                                }

                                target.src = canvas.toDataURL('image/png');
                            };
                            img.src = baseUrl;
                        };

                        if (frontTarget) {
                            compose(
                                frontTarget.dataset.base,
                                frontTarget.dataset.course || 'Curso',
                                frontTarget.dataset.student || 'Aluno',
                                frontTarget,
                                {
                                    layout: 'front',
                                    watermark: false,
                                    titleSize: frontTarget.dataset.titleSize,
                                    subtitleSize: frontTarget.dataset.subtitleSize,
                                    bodySize: frontTarget.dataset.bodySize,
                                    duration: frontTarget.dataset.duration,
                                    start: frontTarget.dataset.start,
                                    end: frontTarget.dataset.end,
                                }
                            );
                        }

                        if (backTarget) {
                            compose(
                                backTarget.dataset.base,
                                backTarget.dataset.course || 'Conteudo',
                                'Programa do curso',
                                backTarget,
                                {
                                    bodyText: backTarget.dataset.text || '',
                                    bodySize: backTarget.dataset.bodySize,
                                    watermark: false,
                                }
                            );
                        }
                    });
                </script>
            @endpush
        </section>

        <section class="rounded-card bg-white p-6 shadow-card space-y-4">
            <h2 class="text-2xl font-display text-edux-primary">Perguntas frequentes</h2>
            @foreach ([
                ['title' => 'O curso é totalmente gratuito?', 'body' => 'Sim! Você pode assistir todas as aulas sem pagar nada. O certificado é opcional e só custa algo se você quiser.'],
                ['title' => 'Por quanto tempo posso acessar o curso?', 'body' => 'Sem limite! Enquanto o curso estiver disponível, você pode assistir e reassistir as aulas quantas vezes quiser, no seu próprio ritmo.'],
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
