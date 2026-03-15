<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Support\EnsuresStudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentCourseController extends Controller
{
    use EnsuresStudentEnrollment;

    public function redirectToNextLesson(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        $this->ensureEnrollment($user, $course);

        $nextLesson = $course->nextLessonFor($user) ?? $course->lessons()
            ->with('module')
            ->orderBy('modules.position')
            ->orderBy('lessons.position')
            ->first();

        if (! $nextLesson) {
            return redirect()->route('dashboard')->with('status', 'Curso ainda não possui aulas.');
        }

        return redirect()->route('learning.courses.lessons.show', [$course, $nextLesson]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson): View
    {
        $user = $request->user();
        $this->ensureEnrollment($user, $course);

        abort_if($lesson->module->course_id !== $course->id, 404);

        return view('learning.lesson', [
            'course' => $course,
            'lesson' => $lesson,
        ]);
    }

    public function enroll(Request $request, Course $course): RedirectResponse
    {
        abort(404);
    }
}
