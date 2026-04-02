<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController extends Controller
{
    public function index(): View
    {
        return view('sa.logs.index', [
            'files' => $this->availableLogFiles(),
        ]);
    }

    public function download(string $filename): BinaryFileResponse
    {
        abort_unless($this->isValidLogFilename($filename), Response::HTTP_NOT_FOUND);

        $path = storage_path('logs/'.$filename);

        abort_unless(File::exists($path) && File::isFile($path), Response::HTTP_NOT_FOUND);

        return response()->download($path, $filename);
    }

    /**
     * @return list<array{name:string,size_bytes:int,size_human:string,modified_at:CarbonImmutable}>
     */
    private function availableLogFiles(): array
    {
        $directory = storage_path('logs');

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file): bool => $file->getExtension() === 'log')
            ->sortByDesc(fn (\SplFileInfo $file): int => $file->getMTime())
            ->map(function (\SplFileInfo $file): array {
                $sizeBytes = $file->getSize();

                return [
                    'name' => $file->getFilename(),
                    'size_bytes' => $sizeBytes,
                    'size_human' => $this->formatBytes($sizeBytes),
                    'modified_at' => CarbonImmutable::createFromTimestamp($file->getMTime()),
                ];
            })
            ->values()
            ->all();
    }

    private function isValidLogFilename(string $filename): bool
    {
        return preg_match('/\A[a-zA-Z0-9._-]+\.log\z/', $filename) === 1;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        return number_format($bytes / (1024 * 1024), 2, ',', '.').' MB';
    }
}
