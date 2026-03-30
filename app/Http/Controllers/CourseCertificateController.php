<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateBranding;
use App\Models\Course;
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
        $user = $request->user();
        $this->ensureEnrollment($user, $course);

        abort_if($certificate->course_id !== $course->id || $certificate->user_id !== $user->id, 403);

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
            'frontContent' => $frontContent,
            'backContent' => $backContent,
        ]);
    }

    public function download(Request $request, Course $course, Certificate $certificate)
    {
        $user = $request->user();
        $this->ensureEnrollment($user, $course);

        abort_if($certificate->course_id !== $course->id || $certificate->user_id !== $user->id, 403);

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
