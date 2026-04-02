<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificadoController extends Controller
{
    public function index(): View
    {
        return view('certificado.index');
    }

    public function create(Request $request): View
    {
        $courseId = $request->integer('course_id');
        $completionDate = $request->query('completion_date');
        $completionConfirmed = $request->query('completion_confirmed');

        return view('certificado.create', [
            'prefilledCourseId' => $courseId > 0 ? $courseId : null,
            'prefilledCompletionDate' => is_string($completionDate) && $completionDate !== '' ? $completionDate : null,
            'prefilledCompletionConfirmed' => in_array($completionConfirmed, ['yes', 'no'], true)
                ? $completionConfirmed
                : null,
        ]);
    }
}
