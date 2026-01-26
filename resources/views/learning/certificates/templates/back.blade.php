@php
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

    $mode = $mode ?? 'preview';
    $backgroundUrl = $branding?->back_background_url;
    $paragraphs = [];

    $backgroundResolvedPath = null;
    $backgroundExists = null;
    $backgroundMime = null;
    $backgroundBytes = null;
    $backgroundSource = null;

    if ($mode === 'pdf' && $branding?->back_background_path) {
        $path = ltrim($branding->back_background_path, '/');
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
        Log::info('Certificate PDF background (back)', [
            'course_id' => $course?->id,
            'branding_id' => $branding?->id,
            'branding_path' => $branding?->back_background_path,
            'disk_exists' => $backgroundExists,
            'disk_path' => $backgroundResolvedPath,
            'background_source' => $backgroundSource,
            'background_mime' => $backgroundMime,
            'background_size' => $backgroundBytes !== null ? strlen($backgroundBytes) : null,
        ]);
    }

    if ($course?->modules) {
        foreach ($course->modules as $moduleIndex => $module) {
            $moduleNumber = $module->position ?: ($moduleIndex + 1);
            $line = "Módulo {$moduleNumber}: {$module->title}";

            if ($module->lessons->isNotEmpty()) {
                $lessonFragments = $module->lessons->values()->map(function ($lesson, $lessonIndex) {
                    $lessonNumber = $lesson->position ?: ($lessonIndex + 1);
                    return "Aula {$lessonNumber}: {$lesson->title}";
                });

                $line .= '. ' . $lessonFragments->implode('. ') . '.';
            } else {
                $line .= '.';
            }

            $paragraphs[] = $line;
        }
    }
@endphp

<x-certificate.layout
    variant="back"
    :mode="$mode"
    :background="$backgroundUrl"
    :paragraphs="$paragraphs"
    :show-watermark="false"
/>
