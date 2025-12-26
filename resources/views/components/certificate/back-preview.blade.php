@props([
    'paragraphs' => [],
    'background' => null,
])

@php
    $paragraphs = is_array($paragraphs) ? $paragraphs : [];
    $backgroundStyle = $background ? "background-image:url('{$background}');" : '';
@endphp

<div class="certificate-preview-container">
    <div class="certificate-preview-scale">
        <div class="certificate-canvas" style="{{ $backgroundStyle }}">
            <div class="certificate-back-overlay"></div>
            <div class="certificate-back-content">
                @forelse ($paragraphs as $paragraph)
                    <p>{{ $paragraph }}</p>
                @empty
                    <p>Conteúdo do curso em atualização.</p>
                @endforelse
            </div>
            <div class="certificate-back-watermark">PREVIEW</div>
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

    @media (max-width: 768px) {
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

    .certificate-back-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.05);
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
        z-index: 1;
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
        z-index: 2;
    }
</style>
