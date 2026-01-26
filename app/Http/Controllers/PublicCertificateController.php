<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateBranding;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PublicCertificateController extends Controller
{
    public function __invoke(string $token): View
    {
        $certificate = Certificate::with(['course.owner', 'user'])
            ->where('public_token', $token)
            ->firstOrFail();

        $branding = $this->resolveBranding($certificate->course);
        $frontContent = view('learning.certificates.templates.front', [
            'course' => $certificate->course,
            'branding' => $branding,
            'displayName' => $certificate->user->preferredName(),
            'issuedAt' => $certificate->issued_at ?? now(),
            'publicUrl' => route('certificates.verify', $certificate->public_token),
        ])->render();

        $backContent = view('learning.certificates.templates.back', [
            'course' => $certificate->course,
            'branding' => $branding,
        ])->render();

        return view('learning.certificates.public', [
            'certificate' => $certificate,
            'course' => $certificate->course,
            'user' => $certificate->user,
            'frontContent' => $frontContent,
            'backContent' => $backContent,
            'downloadUrl' => route('certificates.verify.download', $certificate->public_token),
        ]);
    }

    public function download(string $token)
    {
        $certificate = Certificate::with(['course', 'user'])
            ->where('public_token', $token)
            ->firstOrFail();

        $course = $certificate->course;
        if (! $course) {
            abort(404);
        }

        $pdf = $this->makePdf($certificate, $course);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="certificado-'.$course->slug.'.pdf"',
        ]);
    }

    private function resolveBranding($course): CertificateBranding
    {
        return CertificateBranding::resolveForCourse($course);
    }

    private function makePdf(Certificate $certificate, $course): Dompdf
    {
        $certificate->loadMissing('user');
        $options = new Options();
        $options->set('defaultFont', 'Inter');
        $options->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($options);

        $branding = $this->resolveBranding($course);
        $publicUrl = $certificate->public_token
            ? route('certificates.verify', $certificate->public_token)
            : null;
        $qrDataUri = $this->qrDataUri($publicUrl);
        $frontContent = view('learning.certificates.templates.front', [
            'course' => $course,
            'branding' => $branding,
            'displayName' => $certificate->user->preferredName(),
            'issuedAt' => $certificate->issued_at ?? now(),
            'publicUrl' => $publicUrl,
            'qrDataUri' => $qrDataUri,
            'mode' => 'pdf',
        ])->render();

        $backContent = view('learning.certificates.templates.back', [
            'course' => $course,
            'branding' => $branding,
            'mode' => 'pdf',
        ])->render();

        $html = view('learning.certificates.pdf', compact('frontContent', 'backContent'))->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return $dompdf;
    }

    private function qrDataUri(?string $publicUrl): ?string
    {
        if (! $publicUrl) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()->timeout(5)->get('https://api.qrserver.com/v1/create-qr-code/', [
                'size' => '240x240',
                'data' => $publicUrl,
            ]);

            if (! $response->successful()) {
                return null;
            }

            return 'data:image/png;base64,' . base64_encode($response->body());
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
