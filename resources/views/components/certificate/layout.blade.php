@props([
    'variant' => 'front',
    'mode' => 'preview',
    'background' => null,
    'studentName' => null,
    'courseName' => null,
    'completedAtLabel' => null,
    'workloadLabel' => null,
    'completedAtStartLabel' => null,
    'completedAtEndLabel' => null,
    'issuerPortal' => null,
    'issuerInstitution' => null,
    'cpf' => null,
    'paragraphs' => [],
    'showWatermark' => false,
    'qrUrl' => null,
])

@php
    $mode = $mode ?: 'preview';
    $studentNameLabel = $studentName ?: 'SEU NOME AQUI';
    $courseNameLabel = $courseName ?: 'NOME DO CURSO';
    $completedAtLabel = $completedAtLabel ?: 'DATA DE CONCLUSÃO';
    $workloadLabel = $workloadLabel ?: '—';
    $startLabel = $completedAtStartLabel ?: 'DATA INICIAL';
    $endLabel = $completedAtEndLabel ?: 'DATA FINAL';
    $portalLabel = $issuerPortal ?: 'PORTAL JOVEM EMPREENDEDOR';
    $institutionLabel = $issuerInstitution ?: 'PROGRAMA JE CURSOS E TREINAMENTO LTDA'; 
    $cpfLabel = $cpf ? sprintf('CPF %s', $cpf) : null;
    $paragraphs = is_array($paragraphs) ? $paragraphs : [];
    $backgroundStyle = $background && $mode !== 'pdf'
        ? "background-image:url('{$background}');"
        : '';
    $useImageBackground = $background && $mode === 'pdf';
@endphp

@once
    <style>
        .certificate-layout {
            width: 100%;
            height: 100%;
        }

        .certificate-preview-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            overflow-x: auto;
            padding-bottom: 1rem;
        }

        .certificate-preview-scale {
            --scale: 0.6;
            transform: scale(var(--scale));
            transform-origin: top center;
        }

        @media (max-width: 1024px) {
            .certificate-preview-scale {
                --scale: 0.5;
            }
        }

        @media (max-width: 768px) {
            .certificate-preview-scale {
                --scale: 0.45;
            }
        }

        @media (max-width: 640px) {
            .certificate-preview-scale {
                --scale: 0.4;
            }
        }

        .certificate-canvas {
            width: 1100px;
            height: 780px;
            max-width: 100%;
            position: relative;
            border-radius: 0;
            overflow: hidden;
            border: none;
            box-shadow: none;
            background-color: #ffffff;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .certificate-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }

        

        .certificate-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.1) 0%, rgba(15, 23, 42, 0.65) 100%);
            mix-blend-mode: multiply;
            z-index: 1;
        }

        .certificate-content {
            position: absolute;
            inset: 0;
            padding: 0 120px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #0f172a;            
            z-index: 2;
        }

        .certificate-content-inner {
            width: 100%;
        }

        .certificate-qr {
            position: absolute;
            left: 40px;
            bottom: 40px;
            width: 120px;
            height: 120px;
            object-fit: contain;
            background: #ffffff;
            border-radius: 12px;
            padding: 8px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            z-index: 3;
        }

        
        .certificate-title {
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            margin: 0 0 8px;
            color: #0f172a;
        }

        .certificate-subtitle {
            font-size: 16px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            margin: 0 0 18px;
        }

        .certificate-student {
            font-size: clamp(56px, 30vw, 80px);
            font-weight: 700;
            letter-spacing: 0.06em;
            margin: 0 0 12px;
        }

        

        .certificate-line {
            font-size: 18px;
            margin: 0 0 12px;
            text-transform: capitalize;
        }

        .certificate-course {
            font-size: clamp(22px, 3.5vw, 48px);
            font-weight: 600;
            margin-top: 0 0 18px;
            line-height: 1.2;
        }

        .certificate-layout.certificate-mode-pdf .certificate-preview-wrapper {
            width: 100%;
            height: 100%;
            overflow: hidden;
            padding-bottom: 0;
        }

        .certificate-layout.certificate-mode-pdf .certificate-preview-scale {
            width: 100%;
            height: 100%;
            transform: none;
        }

        .certificate-layout.certificate-mode-pdf .certificate-canvas {
            width: 100%;
            height: 100%;
            max-width: none;
        }

        .certificate-layout.certificate-mode-pdf .certificate-qr {
            width: 140px;
            height: 140px;
            padding: 6px;
        }

        .certificate-layout.certificate-mode-pdf .certificate-student {
            font-size: 42px; /* ou 32pt */
        }

         .certificate-layout.certificate-mode-pdf .certificate-course {
            font-size: 32px; /* ou 32pt */
        }

        .certificate-layout.certificate-mode-pdf .certificate-content {
            position: absolute;
            top: 140px;   /* ajuste vertical */
            left: 120px;  /* margem esquerda */
            right: 120px; /* margem direita */
            bottom: auto;
            display: block;
            padding: 0;
        }

        .certificate-layout.certificate-mode-pdf .certificate-content-inner {
            margin-top: 40px; /* ajuste fino */
        }



        .certificate-description {
            font-size: 16px;
            line-height: 1.4;
            /*max-width: 720px;*/
            margin-top: 12px;
            font-family: 'Inter', sans-serif;
        }

        .certificate-cpf,
        .certificate-date {
            font-size: 14px;
            margin: 0;
            letter-spacing: 0.05em;
        }

        .certificate-date {
            margin-top: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .certificate-back-content {
            position: absolute;
            inset: 0;
            padding: 90px 120px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 18px;
            text-align: left;
            color: #111827;
            font-size: clamp(12px, 1.2vw, 16px);
            line-height: 1.45;
            z-index: 2;
            overflow: hidden;
        }

        .certificate-back-content p {
            margin: 0;
        }

        .certificate-back-watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 72px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.35);
            font-weight: 700;
            transform: rotate(-20deg);
            pointer-events: none;
            z-index: 3;
        }
    </style>
@endonce

<div class="certificate-layout certificate-mode-{{ $mode }}">
    <div class="certificate-preview-wrapper">
        <div class="certificate-preview-scale">
            <div class="certificate-canvas" style="{{ $backgroundStyle }}">
                @if ($useImageBackground)
                    <img src="{{ $background }}" alt="" class="certificate-bg" aria-hidden="true">
                @endif
                <div class="certificate-overlay"></div>

                @if ($variant === 'front')
                    <div class="certificate-content">
                        <div class="certificate-content-inner">
                        <p class="certificate-title">Certificado de Conclusão</p>
                        <p class="certificate-subtitle">CERTIFICAMOS QUE</p>
                        <p class="certificate-student">{{ $studentNameLabel }}</p>
                        <p class="certificate-line">Concluiu com 100% de aproveitamento o curso</p>
                        <p class="certificate-course">{{ $courseNameLabel }}</p>
                        <p class="certificate-description">
                            Com carga horária de {{ $workloadLabel }} horas, no período de
                            {{ $startLabel }} a {{ $endLabel }}, promovido pelo portal de cursos
                            {{ $portalLabel }} e certificado pela instituição {{ $institutionLabel }}.
                        </p>
                        @if ($cpfLabel)
                            <p class="certificate-cpf">{{ $cpfLabel }}</p>
                        @endif
                        <p class="certificate-date">Concluido em {{ $completedAtLabel }}</p>
                        </div>
                    </div>
                    @if ($qrUrl)
                        <img src="{{ $qrUrl }}" alt="QR Code para validacao" class="certificate-qr">
                    @endif
                @else
                    <div class="certificate-back-content">
                        <p style="font-weight: bolder">Conteúdo do curso:</p>
                        @forelse ($paragraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @empty
                            <p>Conteúdo do curso em atualização.</p>
                        @endforelse
                    </div>
                    @if ($showWatermark)
                        <div class="certificate-back-watermark">PREVIEW</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

