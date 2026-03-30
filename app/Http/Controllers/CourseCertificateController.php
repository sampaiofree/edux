<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateBranding;
use App\Models\Course;
use App\Services\CertificateImageService;
use App\Support\EnsuresStudentEnrollment;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class CourseCertificateController extends Controller
{
    use EnsuresStudentEnrollment;

    public function show(Request $request, Course $course, Certificate $certificate): View
    {
        $user = $this->resolveAuthorizedStudent($request, $course, $certificate);

        $branding = $this->resolveBranding($course);
        $displayName = $user->preferredName();
        $issuedAt = $certificate->issued_at ?? now();
        $publicUrl = route('certificates.verify', $certificate->public_token);

        $frontContent = view('learning.certificates.templates.front', [
            'course' => $course,
            'branding' => $branding,
            'displayName' => $displayName,
            'issuedAt' => $issuedAt,
            'publicUrl' => $publicUrl,
        ])->render();

        $backContent = view('learning.certificates.templates.back', [
            'course' => $course,
            'branding' => $branding,
        ])->render();

        return view('learning.certificates.show', [
            'course' => $course,
            'certificate' => $certificate,
            'publicUrl' => $publicUrl,
            'imageUrl' => route('learning.courses.certificate.image', [$course, $certificate]),
            'frontContent' => $frontContent,
            'backContent' => $backContent,
        ]);
    }

    public function image(
        Request $request,
        Course $course,
        Certificate $certificate,
        CertificateImageService $certificateImageService
    ) {
        $this->resolveAuthorizedStudent($request, $course, $certificate);

        $image = $certificateImageService->fromPdf($this->makePdf($certificate, $course));

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="certificado-'.$course->slug.'.png"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    // Mantido para uma futura retomada do fluxo em PDF no app.
    public function download(Request $request, Course $course, Certificate $certificate)
    {
        $this->resolveAuthorizedStudent($request, $course, $certificate);

        $pdf = $this->makePdf($certificate, $course);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="certificado-'.$course->slug.'.pdf"',
        ]);
    }

    private function resolveBranding(Course $course): CertificateBranding
    {
        return CertificateBranding::resolveForCourse($course);
    }

    private function resolveAuthorizedStudent(Request $request, Course $course, Certificate $certificate)
    {
        $user = $request->user();
        $this->ensureEnrollment($user, $course);

        abort_if($certificate->course_id !== $course->id || $certificate->user_id !== $user->id, 403);

        return $user;
    }

    private function makePdf(Certificate $certificate, Course $course): Dompdf
    {
        $certificate->loadMissing('user');
        $options = new Options();
        $options->set('defaultFont', 'Inter');
        $options->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($options);

        $branding = $this->resolveBranding($course);
        $publicUrl = route('certificates.verify', $certificate->public_token);
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
