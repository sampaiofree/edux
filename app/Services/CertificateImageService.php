<?php

namespace App\Services;

use Dompdf\Dompdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\PdfToImage\Enums\OutputFormat;
use Spatie\PdfToImage\Pdf as PdfToImage;

class CertificateImageService
{
    public function fromPdf(Dompdf $pdf): string
    {
        $temporaryDirectory = storage_path('app/tmp/certificate-images/'.Str::uuid());
        File::ensureDirectoryExists($temporaryDirectory);

        try {
            $pdfPath = $temporaryDirectory.'/certificate.pdf';
            File::put($pdfPath, $pdf->output());

            $this->configureGhostscriptPath();

            $converter = new PdfToImage($pdfPath);
            $converter
                ->resolution(144)
                ->format(OutputFormat::Png)
                ->backgroundColor('white');

            $pagePaths = [];

            foreach (range(1, max(1, $converter->pageCount())) as $pageNumber) {
                $pagePath = $temporaryDirectory."/page-{$pageNumber}.png";
                $converter->selectPage($pageNumber)->save($pagePath);
                $pagePaths[] = $pagePath;
            }

            return $this->mergePages($pagePaths);
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    /**
     * @param  list<string>  $pagePaths
     */
    private function mergePages(array $pagePaths): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('A extensão GD é necessária para gerar a imagem do certificado.');
        }

        $pages = [];
        $maxWidth = 0;
        $totalHeight = 0;
        $spacing = 32;

        foreach ($pagePaths as $pagePath) {
            $image = @imagecreatefrompng($pagePath);

            if (! $image) {
                throw new RuntimeException("Nao foi possivel abrir a imagem temporaria do certificado: {$pagePath}");
            }

            $width = imagesx($image);
            $height = imagesy($image);

            $pages[] = [
                'image' => $image,
                'width' => $width,
                'height' => $height,
            ];

            $maxWidth = max($maxWidth, $width);
            $totalHeight += $height;
        }

        $totalHeight += max(0, count($pages) - 1) * $spacing;

        $canvas = imagecreatetruecolor($maxWidth, $totalHeight);

        if (! $canvas) {
            foreach ($pages as $page) {
                imagedestroy($page['image']);
            }

            throw new RuntimeException('Nao foi possivel montar a imagem final do certificado.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $offsetY = 0;

        foreach ($pages as $index => $page) {
            $offsetX = (int) floor(($maxWidth - $page['width']) / 2);

            imagecopy(
                $canvas,
                $page['image'],
                $offsetX,
                $offsetY,
                0,
                0,
                $page['width'],
                $page['height']
            );

            $offsetY += $page['height'];

            if ($index < count($pages) - 1) {
                $offsetY += $spacing;
            }

            imagedestroy($page['image']);
        }

        ob_start();
        imagepng($canvas);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        if (! is_string($binary)) {
            throw new RuntimeException('Nao foi possivel exportar a imagem final do certificado.');
        }

        return $binary;
    }

    private function configureGhostscriptPath(): void
    {
        $candidates = array_filter([
            config('services.ghostscript_path'),
            env('GHOSTSCRIPT_PATH'),
            getenv('MAGICK_GS') ?: null,
            '/opt/homebrew/bin/gs',
            '/usr/local/bin/gs',
            '/opt/local/bin/gs',
            '/usr/bin/gs',
        ]);

        foreach ($candidates as $path) {
            if (! is_string($path) || ! file_exists($path)) {
                continue;
            }

            putenv('MAGICK_GS='.$path);
            putenv('MAGICK_GHOSTSCRIPT_PATH='.$path);

            $pathDirectory = dirname($path);
            $currentPath = getenv('PATH') ?: '';

            if (! str_contains($currentPath, $pathDirectory)) {
                putenv('PATH='.$currentPath.PATH_SEPARATOR.$pathDirectory);
            }

            return;
        }
    }
}
