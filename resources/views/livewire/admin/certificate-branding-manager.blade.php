@php
    $frontPreview = $front_background?->temporaryUrl() ?? $branding->front_background_url ?: $settings->assetUrl('default_certificate_front_path');
    $backPreview = $back_background?->temporaryUrl() ?? $branding->back_background_url ?: $settings->assetUrl('default_certificate_back_path');
@endphp

<section class="space-y-6 rounded-card bg-white p-6 shadow-card" x-data="{ frontPreviewOpen: false, backPreviewOpen: false }">
    <header class="space-y-2">
        <p class="text-sm uppercase tracking-wide text-edux-primary">Certificados</p>
        <h1 class="font-display text-3xl text-edux-primary">Modelos padrão</h1>
        <p class="text-slate-600">Estas imagens serão usadas em todos os cursos que não definirem um fundo próprio.</p>
    </header>

    <form wire:submit.prevent="save" class="grid gap-6 md:grid-cols-2">
        <div class="space-y-3">
            <label class="text-sm font-semibold text-slate-600">Imagem da frente</label>
            <input type="file" wire:model="front_background" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3 text-sm">
            @error('front_background') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

            <div class="rounded-2xl border border-edux-line bg-edux-background/40 p-3 space-y-2">
                @if ($front_background)
                    <img src="{{ $front_background->temporaryUrl() }}" alt="Prévia da frente" class="w-full rounded-xl border border-edux-line object-cover">
                    <p class="text-xs text-slate-500">Pré-visualização temporária</p>
                @elseif ($branding->front_background_url)
                    <div class="flex items-center justify-between text-xs font-semibold text-edux-primary">
                        <span>Pré-visualização atual</span>
                        <div class="flex gap-2">
                            <button type="button" class="underline-offset-2 hover:underline" @click="frontPreviewOpen = true">Ver</button>
                            <button type="button" class="text-red-500 underline-offset-2 hover:underline" wire:click="deleteFront">Remover</button>
                        </div>
                    </div>
                    <img src="{{ $branding->front_background_url }}" alt="Frente atual" class="w-full rounded-xl border border-edux-line object-cover">
                @else
                    <p class="text-xs text-slate-500">Nenhuma imagem cadastrada.</p>
                @endif
            </div>
        </div>

        <div class="space-y-3">
            <label class="text-sm font-semibold text-slate-600">Imagem do verso</label>
            <input type="file" wire:model="back_background" accept="image/*" class="w-full rounded-xl border border-edux-line px-4 py-3 text-sm">
            @error('back_background') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

            <div class="rounded-2xl border border-edux-line bg-edux-background/40 p-3 space-y-2">
                @if ($back_background)
                    <img src="{{ $back_background->temporaryUrl() }}" alt="Prévia do verso" class="w-full rounded-xl border border-edux-line object-cover">
                    <p class="text-xs text-slate-500">Pré-visualização temporária</p>
                @elseif ($branding->back_background_url)
                    <div class="flex items-center justify-between text-xs font-semibold text-edux-primary">
                        <span>Pré-visualização atual</span>
                        <div class="flex gap-2">
                            <button type="button" class="underline-offset-2 hover:underline" @click="backPreviewOpen = true">Ver</button>
                            <button type="button" class="text-red-500 underline-offset-2 hover:underline" wire:click="deleteBack">Remover</button>
                        </div>
                    </div>
                    <img src="{{ $branding->back_background_url }}" alt="Verso atual" class="w-full rounded-xl border border-edux-line object-cover">
                @else
                    <p class="text-xs text-slate-500">Nenhuma imagem cadastrada.</p>
                @endif
            </div>
        </div>

        <div class="md:col-span-2 grid gap-4 md:grid-cols-3">
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Tamanho do nome do aluno</span>
                <input type="number" min="12" max="120" step="1" wire:model.live="titleSize" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                <small class="text-xs text-slate-500">Aplica na linha do aluno (L2).</small>
                @error('titleSize') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Tamanho do nome do curso</span>
                <input type="number" min="12" max="120" step="1" wire:model.live="subtitleSize" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                <small class="text-xs text-slate-500">Aplica na linha do curso (L4).</small>
                @error('subtitleSize') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Tamanho das demais linhas</span>
                <input type="number" min="10" max="120" step="1" wire:model.live="bodySize" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                <small class="text-xs text-slate-500">Aplica nas demais linhas (L1, L3, L6 e verso).</small>
                @error('bodySize') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="md:col-span-2 grid gap-4 md:grid-cols-3">
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Texto L1</span>
                <input type="text" wire:model.live="line1" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                <small class="text-xs text-slate-500">Use {student}, {course}, {duration}, {start}, {end} se precisar.</small>
                @error('line1') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Texto L3</span>
                <input type="text" wire:model.live="line3" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                <small class="text-xs text-slate-500">Linha antes do nome do curso.</small>
                @error('line3') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Texto L6</span>
                <input type="text" wire:model.live="line6" class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30">
                <small class="text-xs text-slate-500">Pode usar {duration}, {start}, {end}.</small>
                @error('line6') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="md:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-display text-edux-primary">Pré-visualização ao vivo</h3>
                <p class="text-xs text-slate-500">Clique nas imagens para ampliar.</p>
            </div>
            <div class="mt-3 grid gap-4 md:grid-cols-2">
                <figure class="overflow-hidden rounded-2xl border border-edux-line/70 bg-edux-background">
                    <img
                        data-cert-front
                        data-base="{{ $frontPreview }}"
                        data-course="Curso Exemplo"
                        data-student="Nome do Aluno"
                        data-title-size="{{ $titleSize }}"
                        data-subtitle-size="{{ $subtitleSize }}"
                        data-body-size="{{ $bodySize }}"
                        data-line1="{{ $line1 }}"
                        data-line3="{{ $line3 }}"
                        data-line6="{{ $line6 }}"
                        data-duration="40 horas"
                        data-start="01/01/2024"
                        data-end="31/12/2024"
                        alt="Prévia frente do certificado"
                        class="w-full cursor-zoom-in object-cover transition hover:scale-[1.01]">
                    <figcaption class="px-3 py-2 text-center text-xs text-slate-500">Frente</figcaption>
                </figure>
                <figure class="overflow-hidden rounded-2xl border border-edux-line/70 bg-edux-background">
                    <img
                        data-cert-back
                        data-base="{{ $backPreview }}"
                        data-course="Conteúdo"
                        data-text="1. Aula exemplo\n2. Outra aula\n3. Conteúdo extra"
                        data-body-size="{{ $bodySize }}"
                        alt="Prévia verso do certificado"
                        class="w-full cursor-zoom-in object-cover transition hover:scale-[1.01]">
                    <figcaption class="px-3 py-2 text-center text-xs text-slate-500">Verso</figcaption>
                </figure>
            </div>
            <p class="mt-2 text-xs text-slate-500">Se nenhuma imagem aparecer, envie um fundo ou use os modelos padrão.</p>
        </div>

        <div class="md:col-span-2 flex flex-wrap gap-3">
            <button type="submit" class="edux-btn">Salvar configurações</button>
            <p class="text-xs text-slate-500">Formatos aceitos: JPG, PNG ou WEBP — até 4MB.</p>
        </div>
    </form>

    @if ($branding->front_background_url)
    <template x-if="frontPreviewOpen">
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/70 p-4" @click.self="frontPreviewOpen = false">
            <div class="max-w-3xl rounded-3xl bg-white p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-display text-edux-primary">Frente do certificado</h2>
                    <button type="button" @click="frontPreviewOpen = false" class="text-sm font-semibold text-edux-primary">Fechar</button>
                </div>
                <img src="{{ $branding->front_background_url }}" alt="Frente do certificado" class="mt-4 w-full rounded-2xl border border-edux-line">
            </div>
        </div>
    </template>
    @endif

    @if ($branding->back_background_url)
    <template x-if="backPreviewOpen">
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/70 p-4" @click.self="backPreviewOpen = false">
            <div class="max-w-3xl rounded-3xl bg-white p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-display text-edux-primary">Verso do certificado</h2>
                    <button type="button" @click="backPreviewOpen = false" class="text-sm font-semibold text-edux-primary">Fechar</button>
                </div>
                <img src="{{ $branding->back_background_url }}" alt="Verso do certificado" class="mt-4 w-full rounded-2xl border border-edux-line">
            </div>
        </div>
    </template>
    @endif
</section>

@push('scripts')
    <script>
        const renderCertificatePreview = () => {
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

            const applyPlaceholders = (text, data) => {
                return (text || '')
                    .replaceAll('{student}', data.student || 'Aluno')
                    .replaceAll('{course}', data.course || 'Curso')
                    .replaceAll('{duration}', data.duration || 'X horas')
                    .replaceAll('{start}', data.start || '01/01/2024')
                    .replaceAll('{end}', data.end || '01/06/2024');
            };

            const drawFront = (ctx, canvas, opts) => {
                const centerX = canvas.width / 2;
                const { titlePx, subtitlePx, bodyPx } = opts;
                let y = canvas.height * 0.3;

                ctx.fillStyle = 'rgba(17, 24, 39, 0.8)';
                ctx.textAlign = 'center';
                ctx.font = `${bodyPx}px "Inter", Arial, sans-serif`;
                ctx.fillText(opts.line1 || 'Certificamos que', centerX, y);

                y += titlePx * 1.1;
                ctx.font = `${titlePx}px "Inter", Arial, sans-serif`;
                ctx.fillText(opts.student || 'Aluno', centerX, y);

                y += bodyPx * 1.3;
                ctx.font = `${bodyPx}px "Inter", Arial, sans-serif`;
                ctx.fillText(opts.line3 || 'concluiu com 100% de aproveitamento o curso', centerX, y);

                y += subtitlePx * 1.2;
                ctx.font = `${subtitlePx}px "Inter", Arial, sans-serif`;
                ctx.fillText(opts.course || 'Curso', centerX, y);

                y += bodyPx * 1.4;
                ctx.font = `${bodyPx}px "Inter", Arial, sans-serif`;
                const maxWidth = canvas.width * 0.86;
                const lines = splitLines(ctx, opts.line6 || '', maxWidth);
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
                        const line1 = applyPlaceholders(options.line1, options);
                        const line3 = applyPlaceholders(options.line3, options);
                        const line6 = applyPlaceholders(options.line6, options);
                        drawFront(ctx, canvas, {
                            course: title,
                            student: subtitle,
                            titlePx,
                            subtitlePx,
                            bodyPx,
                            line1,
                            line3,
                            line6,
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
                        line1: frontTarget.dataset.line1,
                        line3: frontTarget.dataset.line3,
                        line6: frontTarget.dataset.line6,
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
        };

        document.addEventListener('DOMContentLoaded', renderCertificatePreview);
        if (window.Livewire) {
            Livewire.hook('message.processed', renderCertificatePreview);
        }
    </script>
@endpush
