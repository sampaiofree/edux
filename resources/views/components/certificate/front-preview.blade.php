@props([
    'studentName' => null,
    'courseName' => null,
    'completedAt' => null,
    'workload' => null,
    'completedAtStart' => null,
    'completedAtEnd' => null,
    'issuerPortal' => null,
    'issuerInstitution' => null,
    'cpf' => null,
    'background' => null,
])

@php
    $studentNameLabel = $studentName ?: 'SEU NOME AQUI';
    $courseNameLabel = $courseName ?: 'NOME DO CURSO';
    $completedAtLabel = $completedAt ?: 'DATA DE CONCLUSÃO';
    $workloadLabel = $workload ? (int) $workload : '—';
    $startLabel = $completedAtStart ?: 'DATA INICIAL';
    $endLabel = $completedAtEnd ?: 'DATA FINAL';
    $portalLabel = $issuerPortal ?: 'PORTAL DE CURSOS';
    $institutionLabel = $issuerInstitution ?: 'INSTITUIÇÃO CERTIFICADORA';
    $cpfLabel = $cpf ? sprintf('CPF: %s', $cpf) : null;
    $backgroundStyle = $background ? "background-image:url('{$background}');" : '';
@endphp

<div class="certificate-preview-container">
    <div class="certificate-preview-scale">
        <div class="certificate-canvas" style="{{ $backgroundStyle }}">
            <div class="certificate-overlay"></div>
            <div class="certificate-content">
                <p class="certificate-title">Certificado de Conclusão</p>
                <p class="certificate-subtitle">CERTIFICAMOS QUE</p>
                <p class="certificate-student">{{ $studentNameLabel }}</p>
                <p class="certificate-line">Concluiu com 100% de aproveitamento o curso</p>
                <p class="certificate-course">{{ $courseNameLabel }}</p>
                <p class="certificate-description">
            Com carga horária de {{ $workloadLabel }} horas, no período de
            {{ $startLabel }} a {{ $endLabel }}, promovido(a) pelo portal
            de cursos {{ $portalLabel }} e certificado pela instituição
            {{ $institutionLabel }}.
        </p>
        @if ($cpfLabel)
            <p class="certificate-cpf">{{ $cpfLabel }}</p>
        @endif
        <p class="certificate-date">Concluído em {{ $completedAtLabel }}</p>
            </div>
        </div>
    </div>
</div>

<style>
    .certificate-preview-container {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: flex-start;
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

    @media (max-width: 100%) {
        .certificate-preview-scale {
            --scale: 0.4;
        }
    }

    .certificate-canvas {
        width: 1100px;
        height: 780px;
        position: relative;
        border-radius: 28px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.25);
        background-color: #ffffff;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .certificate-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.2) 0%, rgba(15, 23, 42, 0.65) 100%);
        mix-blend-mode: multiply;
    }

    .certificate-content {
        position: absolute;
        inset: 80px 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        text-align: center;
        color: #0f172a;
        z-index: 1;
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
        font-size: clamp(28px, 4vw, 56px);
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
        margin: 0 0 18px;
        line-height: 1.2;
    }

    .certificate-description {
        font-size: 16px;
        line-height: 1.4;
        max-width: 720px;
        margin: 0 0 12px;
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
</style>
