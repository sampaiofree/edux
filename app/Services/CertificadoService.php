<?php

namespace App\Services;

use App\Models\CertificateBranding;
use App\Models\Course;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Spatie\PdfToImage\Pdf as PdfToImage;
use Throwable;

class CertificadoService
{
    private const INITIAL_FONT_SIZE = 12;
    private const MIN_FONT_SIZE = 7;
    public function pdfVerso(Course $course): string
    {
        $course->loadMissing(['certificateBranding', 'modules.lessons']);
        $payload = $this->prepareVersoPayload($course);

        $this->ensurePdfExists($payload['pdfRelative'], 'certificates.back', [
            'paragraphs' => $payload['paragraphs'],
            'backgroundImagePath' => $payload['backgroundImagePath'],
        ]);

        return Storage::disk('public')->url($payload['pdfRelative']);
    }

    private function decoratePreviewImage(string $path): void
    {
        if (! extension_loaded('gd')) {
            Log::warning('GD extension missing, skipping preview decoration', ['path' => $path]);
            return;
        }

        $image = @imagecreatefrompng($path);

        if (! $image) {
            Log::warning('Failed to open preview image for decoration', ['path' => $path]);
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $targetWidth = min($width, 800);

        if ($width > $targetWidth) {
            $scale = $targetWidth / $width;
            $resized = imagecreatetruecolor($targetWidth, (int) floor($height * $scale));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, (int) floor($height * $scale), $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $targetWidth;
            $height = imagesy($image);
        }

        $this->applyWatermark($image, $width, $height);

        imagepng($image, $path);
        imagedestroy($image);
    }

    private function applyWatermark($image, int $width, int $height): void
    {
        $overlay = imagecreatetruecolor($width, $height);
        imagealphablending($overlay, false);
        imagesavealpha($overlay, true);
        $transparent = imagecolorallocatealpha($overlay, 0, 0, 0, 127);
        imagefill($overlay, 0, 0, $transparent);

        $fontSize = max(32, (int) floor($width / 8));
        $angle = -30;
        $fontPath = $this->watermarkFontPath();

        if ($fontPath) {
            $textColor = imagecolorallocatealpha($overlay, 230, 230, 230, 90);
            $text = 'PREVIEW';
            $bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);

            if ($bbox) {
                $textWidth = abs($bbox[4] - $bbox[0]);
                $textHeight = abs($bbox[5] - $bbox[1]);
                $x = (int) (($width - $textWidth) / 2);
                $y = (int) (($height + $textHeight) / 2);
                imagettftext($overlay, $fontSize, $angle, $x, $y, $textColor, $fontPath, $text);
            }
        } else {
            $textColor = imagecolorallocatealpha($overlay, 230, 230, 230, 90);
            $font = 5;
            $text = 'PREVIEW';
            $x = (int) (($width - imagefontwidth($font) * strlen($text)) / 2);
            $y = (int) (($height - imagefontheight($font)) / 2);
            imagestring($overlay, $font, $x, $y, $text, $textColor);
        }

        imagecopy($image, $overlay, 0, 0, 0, 0, $width, $height);
        imagedestroy($overlay);
    }

    private function watermarkFontPath(): ?string
    {
        $paths = [
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public function previewVerso(Course $course, bool $lowResolution = true): ?string
    {
        $course->loadMissing(['certificateBranding', 'modules.lessons']);
        $payload = $this->prepareVersoPayload($course);

        $this->ensurePdfExists($payload['pdfRelative'], 'certificates.back', [
            'paragraphs' => $payload['paragraphs'],
            'backgroundImagePath' => $payload['backgroundImagePath'],
        ]);

        return $this->ensurePreview($payload['pdfRelative'], $payload['pngRelative'], $lowResolution, [
            'type' => 'verso',
            'course_id' => $course->id,
            'version' => $payload['version'],
        ]);
    }

    public function pdfFrente(array $data): string
    {
        $payload = $this->prepareFrontPayload($data);

        $this->ensurePdfExists($payload['pdfRelative'], 'certificates.front', [
            'studentName' => $payload['studentName'],
            'courseName' => $payload['courseName'],
            'completedAtLabel' => $payload['completedAtLabel'],
            'cpf' => $payload['cpf'],
            'backgroundImagePath' => $payload['backgroundImagePath'],
        ]);

        return Storage::disk('public')->url($payload['pdfRelative']);
    }

    public function previewFrente(array $data, bool $lowResolution = true): ?string
    {
        $payload = $this->prepareFrontPayload($data);

        $this->ensurePdfExists($payload['pdfRelative'], 'certificates.front', [
            'studentName' => $payload['studentName'],
            'courseName' => $payload['courseName'],
            'completedAtLabel' => $payload['completedAtLabel'],
            'cpf' => $payload['cpf'],
            'backgroundImagePath' => $payload['backgroundImagePath'],
        ]);

        return $this->ensurePreview($payload['pdfRelative'], $payload['pngRelative'], $lowResolution, [
            'type' => 'frente',
            'hash' => $payload['hash'],
        ]);
    }

    private function ensurePdfExists(string $relativePath, string $view, array $viewData): void
    {
        $disk = Storage::disk('public');
        $directory = dirname($relativePath);
        $disk->makeDirectory($directory);

        if ($disk->exists($relativePath)) {
            return;
        }

        $dompdf = $this->renderPdfWithAdaptiveFont($view, $viewData);
        $disk->put($relativePath, $dompdf->output());
    }

    private function ensurePreview(string $relativePdf, string $relativePng, bool $lowResolution, array $context = []): ?string
    {
        $disk = Storage::disk('public');
        $directory = dirname($relativePng);
        $disk->makeDirectory($directory);

        if (! $disk->exists($relativePdf)) {
            Log::warning('Certificate preview requested without PDF', [
                'pdf' => $relativePdf,
                'png' => $relativePng,
                'context' => $context,
            ]);

            return null;
        }

        $this->configureGhostscriptPath();

        if ($disk->exists($relativePng)) {
            $pdfMtime = @filemtime($disk->path($relativePdf));
            $pngMtime = @filemtime($disk->path($relativePng));

            if ($pdfMtime !== false && $pngMtime !== false && $pngMtime >= $pdfMtime) {
                return $disk->url($relativePng);
            }
        }

        try {
            $pdfToImage = new PdfToImage($disk->path($relativePdf));
            $pdfToImage->setPage(1);
            $pdfToImage->setOutputFormat('png');
            $pdfToImage->setResolution($lowResolution ? 144 : 300);
            $pdfToImage->saveImage($disk->path($relativePng));

            if ($lowResolution) {
                $this->decoratePreviewImage($disk->path($relativePng));
            }

            return $disk->url($relativePng);
        } catch (Throwable $exception) {
            Log::error('Failed to generate certificate preview', [
                'pdf' => $relativePdf,
                'png' => $relativePng,
                'context' => $context,
                'ghostscript' => getenv('MAGICK_GS') ?: null,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function configureGhostscriptPath(): void
    {
        $candidates = array_filter([
            config('services.ghostscript_path'),
            getenv('MAGICK_GS') ?: null,
            '/opt/homebrew/bin/gs',
            '/usr/local/bin/gs',
            '/opt/local/bin/gs',
            '/usr/bin/gs',
        ]);

        foreach ($candidates as $path) {
            if (! $path || ! is_string($path)) {
                continue;
            }

            if (! file_exists($path)) {
                continue;
            }

            putenv('MAGICK_GS='.$path);
            putenv('MAGICK_GHOSTSCRIPT_PATH='.$path);

            $pathDir = dirname($path);
            $currentPath = getenv('PATH') ?: '';

            if (! str_contains($currentPath, $pathDir)) {
                putenv('PATH='.$currentPath.PATH_SEPARATOR.$pathDir);
            }

            return;
        }
    }

    private function renderPdfWithAdaptiveFont(string $view, array $viewData): Dompdf
    {
        $fontSize = self::INITIAL_FONT_SIZE;
        $dompdf = null;
        $warningLogged = false;

        do {
            $viewData['fontSize'] = $fontSize;
            $html = view($view, $viewData)->render();

            $dompdf = $this->createDompdf($html);
            $dompdf->render();

            $pageCount = $dompdf->getCanvas()->get_page_count();

            if ($pageCount === 1 || $fontSize <= self::MIN_FONT_SIZE) {
                if ($pageCount > 1 && ! $warningLogged) {
                    Log::warning('Certificate PDF still exceeds one page', [
                        'view' => $view,
                        'font_size' => $fontSize,
                        'page_count' => $pageCount,
                    ]);
                    $warningLogged = true;
                }

                break;
            }

            $fontSize = max(self::MIN_FONT_SIZE, $fontSize - 1);
        } while (true);

        return $dompdf;
    }

    private function createDompdf(string $html): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'landscape');

        return $dompdf;
    }

    private function prepareVersoPayload(Course $course): array
    {
        $version = $this->versoVersion($course);
        $directory = "certificates/back/course-{$course->id}";
        $paragraphs = $this->buildVersoParagraphs($course);
        $branding = $this->resolveBranding($course);
        $backgroundImagePath = $this->resolveBackgroundPath(
            $branding->back_background_path
        );

        return [
            'version' => $version,
            'pdfRelative' => "{$directory}/verso-v{$version}.pdf",
            'pngRelative' => "{$directory}/verso.png",
            'paragraphs' => $paragraphs,
            'backgroundImagePath' => $backgroundImagePath,
        ];
    }

    private function prepareFrontPayload(array $data): array
    {
        $studentName = $this->requiredField($data, 'student_name');
        $courseName = $this->requiredField($data, 'course_name');
        $completedAtRaw = $this->requiredField($data, 'completed_at');
        $cpf = isset($data['cpf']) && trim((string) $data['cpf']) !== '' ? trim((string) $data['cpf']) : null;

        $completedAtLabel = $this->formatCompletedAt($completedAtRaw);
        $hash = md5(sprintf('%s|%s|%s|%s', $studentName, $courseName, $completedAtRaw, $cpf ?? ''));

        $branding = $data['certificate_branding'] ?? $data['branding'] ?? null;
        if (! $branding instanceof CertificateBranding) {
            $branding = CertificateBranding::firstOrCreate(['course_id' => null]);
        }
        $brandingPath = $branding->front_background_path;

        $directory = "certificates/front/{$hash}";

        return [
            'studentName' => $studentName,
            'courseName' => $courseName,
            'completedAtLabel' => $completedAtLabel,
            'cpf' => $cpf,
            'hash' => $hash,
            'pdfRelative' => "{$directory}/front.pdf",
            'pngRelative' => "{$directory}/front.png",
            'backgroundImagePath' => $this->resolveBackgroundPath(
                $brandingPath
            ),
        ];
    }

    private function resolveBranding(Course $course): CertificateBranding
    {
        return CertificateBranding::resolveForCourse($course);
    }

    private function requiredField(array $data, string $key): string
    {
        if (! isset($data[$key]) || trim((string) $data[$key]) === '') {
            throw new InvalidArgumentException("{$key} is required");
        }

        return trim((string) $data[$key]);
    }

    private function formatCompletedAt(string $value): string
    {
        try {
            return Carbon::parse($value)
                ->locale('pt_BR')
                ->isoFormat('D [de] MMMM [de] YYYY');
        } catch (Throwable $exception) {
            Log::warning('Invalid completed_at for certificate front', [
                'value' => $value,
                'exception' => $exception->getMessage(),
            ]);

            return $value;
        }
    }

    private function resolveBackgroundPath(?string ...$paths): ?string
    {
        $disk = Storage::disk('public');

        foreach ($paths as $path) {
            $normalized = $this->normalizePublicPath($path);

            if (! $normalized) {
                continue;
            }

            if ($disk->exists($normalized)) {
                return $disk->path($normalized);
            }
        }

        return null;
    }

    private function normalizePublicPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = ltrim((string) $path, '/');

        return $normalized !== '' ? $normalized : null;
    }

    private function buildVersoParagraphs(Course $course): array
    {
        $paragraphs = [];

        foreach ($course->modules as $moduleIndex => $module) {
            $moduleNumber = $moduleIndex + 1;
            $line = "Módulo {$moduleNumber}: {$module->title}";

            if ($module->lessons->isNotEmpty()) {
                $lessonFragments = $module->lessons->values()->map(function ($lesson, $lessonIndex) {
                    $lessonNumber = $lessonIndex + 1;
                    return "Aula {$lessonNumber}: {$lesson->title}";
                })->implode('. ');

                $line = "{$line}. {$lessonFragments}";
            }

            $line = rtrim($line, '. ');
            $paragraphs[] = "{$line}.";
        }

        return $paragraphs;
    }

    private function versoVersion(Course $course): int
    {
        return $course->updated_at?->timestamp ?: now()->timestamp;
    }
}
