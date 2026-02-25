@php
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

    $mode = $mode ?? 'preview';
    $presentation = $presentation ?? 'default';
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

    $backgroundResolvedPath = null;
    $backgroundExists = null;
    $backgroundMime = null;
    $backgroundBytes = null;
    $backgroundSource = null;

    if ($mode === 'pdf' && $branding?->front_background_path) {
        $path = ltrim($branding->front_background_path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        $disk = Storage::disk('public');
        $backgroundExists = $disk->exists($path);
        $backgroundResolvedPath = $disk->path($path);
        if ($backgroundExists) {
            $backgroundBytes = @file_get_contents($backgroundResolvedPath);
            if ($backgroundBytes !== false) {
                $backgroundMime = @mime_content_type($backgroundResolvedPath) ?: 'image/jpeg';
                $backgroundUrl = 'data:' . $backgroundMime . ';base64,' . base64_encode($backgroundBytes);
                $backgroundSource = 'base64';
            } else {
                $absolutePath = str_replace('\\', '/', $backgroundResolvedPath);
                if (preg_match('/^[A-Za-z]:\\//', $absolutePath)) {
                    $absolutePath = '/' . $absolutePath;
                }
                $backgroundUrl = 'file://' . $absolutePath;
                $backgroundSource = 'file';
            }
        }
    }

    if ($mode === 'pdf') {
        Log::info('Certificate PDF background (front)', [
            'course_id' => $course?->id,
            'branding_id' => $branding?->id,
            'branding_path' => $branding?->front_background_path,
            'disk_exists' => $backgroundExists,
            'disk_path' => $backgroundResolvedPath,
            'background_source' => $backgroundSource,
            'background_mime' => $backgroundMime,
            'background_size' => $backgroundBytes !== null ? strlen($backgroundBytes) : null,
        ]);
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
    :presentation="$presentation"
/>
