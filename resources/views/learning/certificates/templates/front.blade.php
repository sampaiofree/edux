@php
    $settings = $settings ?? \App\Models\SystemSetting::current();
    $frontStyle = "font-family:'Inter',Arial,sans-serif; padding:2rem; border:4px solid #0f172a; border-radius:20px; position:relative; overflow:hidden;";
    if (!empty($branding?->front_background_url)) {
        $frontStyle .= "background-image:url('{$branding->front_background_url}'); background-size:cover; background-position:center;";
    }
    $titlePx = (($settings->certificate_title_size ?? 68)) . 'px'; // nome do aluno
    $subtitlePx = (($settings->certificate_subtitle_size ?? 52)) . 'px'; // nome do curso
    $bodyPx = (($settings->certificate_body_size ?? 40)) . 'px'; // demais linhas
    $courseStart = optional($course->created_at)->format('d/m/Y') ?? '01/01/2024';
    $courseEnd = $issuedAt->format('d/m/Y');
    $durationHours = $course->duration_minutes
        ? round($course->duration_minutes / 60, 1) . ' horas'
        : 'x horas';
@endphp

<div style="{{ $frontStyle }}">
    <div style="position:relative; z-index:2;">
        <p style="text-transform:uppercase; letter-spacing:0.4em; font-size:{{ $bodyPx }}; color:#94a3b8; margin:0;">Certificamos que</p>
        <h1 style="font-size:{{ $titlePx }}; margin:0.5rem 0;">{{ $displayName }}</h1>
        <p style="font-size:{{ $bodyPx }}; margin:1rem 0 0.25rem 0; line-height:1.3;">concluiu com 100% de aproveitamento o curso</p>
        <h2 style="font-size:{{ $subtitlePx }}; margin:0.25rem 0 1rem 0;">{{ $course->title }}</h2>
        <p style="font-size:{{ $bodyPx }}; color:#475569; line-height:1.4; margin:1rem 0 0 0;">
            Com carga horária de {{ $durationHours }}, no período de {{ $courseStart }} a {{ $courseEnd }}, promovido pelo portal de cursos EDUX.
        </p>
    </div>
    <div style="position:absolute; bottom:1.5rem; right:1.5rem; z-index:2; background:#ffffffcc; padding:0.5rem; border-radius:8px; text-align:center;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($publicUrl) }}" alt="QR Code" style="width:120px; height:120px;">
        <p style="margin:0.25rem 0 0 0; font-size:0.65rem;">Verifique: {{ $publicUrl }}</p>
    </div>
</div>
