@php
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

    $mode = $mode ?? 'preview';
    $studentName = $displayName ?? 'SEU NOME AQUI';
    $issuedAtInstance = $issuedAt ? Carbon::parse($issuedAt) : Carbon::now();
    $completedAtLabel = $issuedAtInstance
        ->locale('pt_BR')
        ->isoFormat('D [de] MMMM [de] YYYY');
    $courseStartLabel = optional($course?->created_at)->format('d/m/Y') ?? '01/01/2024';
    $courseEndLabel = $issuedAtInstance->format('d/m/Y');
    $workloadLabel = $course && $course->duration_minutes
        ? round($course->duration_minutes / 60, 1) . ' horas'
        : 'x horas';
    $backgroundUrl = $branding?->front_background_url;
    $qrUrl = null;

    if ($mode === 'pdf' && $branding?->front_background_path) {
        $path = ltrim($branding->front_background_path, '/');
        if (Storage::disk('public')->exists($path)) {
            $backgroundUrl = 'file://' . str_replace('\\', '/', Storage::disk('public')->path($path));
        }
    }

    if (! empty($qrDataUri)) {
        $qrUrl = $qrDataUri;
    } elseif ($mode !== 'pdf' && isset($publicUrl)) {
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($publicUrl);
    }
@endphp

<x-certificate.layout
    variant="front"
    :mode="$mode"
    :qr-url="$qrUrl"
    :student-name="$studentName"
    :course-name="$course?->title ?? 'CURSO'"
    :completed-at-label="$completedAtLabel"
    :workload-label="$workloadLabel"
    :completed-at-start-label="$courseStartLabel"
    :completed-at-end-label="$courseEndLabel"
    :background="$backgroundUrl"
    :show-watermark="false"
/>
