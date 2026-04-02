<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FinalTestAttempt;
use App\Models\FinalTestQuestion;
use App\Support\EnsuresStudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentFinalTestController extends Controller
{
    use EnsuresStudentEnrollment;

    public function intro(Request $request, Course $course): View
    {
        $user = $request->user();
        $enrollment = $this->ensureEnrollment($user, $course);

        $finalTest = $course->finalTest()->with('questions.options')->firstOrFail();

        if ($finalTest->questions->isEmpty()) {
            return back()->withErrors(['test' => 'Teste sem questões disponíveis no momento.']);
        }

        $attemptsCount = $finalTest->attempts()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->count();

        $openAttempt = $finalTest->attempts()
            ->where('user_id', $user->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        $latestSubmittedAttempt = $finalTest->attempts()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->first();

        $isEligibleForCertificate = $enrollment->progress_percent === 100;
        $canGenerateCertificate = $isEligibleForCertificate && (bool) ($latestSubmittedAttempt?->passed);
        $attemptsRemaining = $finalTest->max_attempts
            ? max($finalTest->max_attempts - $attemptsCount, 0)
            : null;

        $certificateCreateUrl = null;

        if ($canGenerateCertificate) {
            $certificateParams = [
                'course_id' => $course->id,
                'completion_confirmed' => 'yes',
            ];

            if ($enrollment->completed_at) {
                $certificateParams['completion_date'] = $enrollment->completed_at->format('Y-m-d');
            }

            $certificateCreateUrl = route('certificado.create', $certificateParams);
        }

        return view('learning.final-test.intro', compact(
            'course',
            'finalTest',
            'enrollment',
            'attemptsCount',
            'attemptsRemaining',
            'openAttempt',
            'latestSubmittedAttempt',
            'isEligibleForCertificate',
            'canGenerateCertificate',
            'certificateCreateUrl',
        ));
    }

    public function start(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        $this->ensureEnrollment($user, $course);

        $finalTest = $course->finalTest()->with('questions.options')->firstOrFail();

        $attemptsCount = $finalTest->attempts()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->count();

        $attempt = $finalTest->attempts()
            ->where('user_id', $user->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        if ($attempt && $finalTest->duration_minutes && $attempt->remainingSeconds() !== null && $attempt->remainingSeconds() <= 0) {
            $this->expireAttempt($attempt);
            $attemptsCount++;
            $attempt = null;
        }

        if ($finalTest->max_attempts && $attemptsCount >= $finalTest->max_attempts && ! $attempt) {
            return back()->withErrors(['test' => 'Limite de tentativas atingido.']);
        }

        if (! $attempt) {
            $attempt = FinalTestAttempt::create([
                'final_test_id' => $finalTest->id,
                'user_id' => $user->id,
                'score' => 0,
                'passed' => false,
                'started_at' => now(),
                'attempted_at' => now(),
            ]);
        }

        return redirect()->route('learning.courses.final-test.attempt', [$course, $attempt]);
    }

    public function attempt(Request $request, Course $course, FinalTestAttempt $attempt): View|RedirectResponse
    {
        $user = $request->user();
        $this->ensureEnrollment($user, $course);

        abort_if($attempt->user_id !== $user->id, 403);
        abort_if($attempt->finalTest->course_id !== $course->id, 404);

        if ($attempt->submitted_at) {
            return redirect()->route('learning.courses.final-test.intro', $course)
                ->with('status', 'Esta tentativa já foi concluída.');
        }

        $finalTest = $attempt->finalTest()->with('questions.options')->first();
        $attempt->setRelation('finalTest', $finalTest);

        $remainingSeconds = $attempt->remainingSeconds();

        if ($finalTest->duration_minutes && $remainingSeconds !== null && $remainingSeconds <= 0) {
            return $this->autoSubmit($attempt, $course);
        }

        return view('learning.final-test.attempt', [
            'course' => $course,
            'finalTest' => $finalTest,
            'attempt' => $attempt,
            'remainingSeconds' => $remainingSeconds,
        ]);
    }

    public function submit(Request $request, Course $course, FinalTestAttempt $attempt): RedirectResponse
    {
        $user = $request->user();
        $this->ensureEnrollment($user, $course);

        abort_if($attempt->user_id !== $user->id, 403);
        abort_if($attempt->finalTest->course_id !== $course->id, 404);

        if ($attempt->submitted_at) {
            return redirect()->route('learning.courses.final-test.intro', $course)
                ->with('status', 'Tentativa já finalizada.');
        }

        $finalTest = $attempt->finalTest()->with('questions.options')->first();
        $attempt->setRelation('finalTest', $finalTest);
        $remainingSeconds = $attempt->remainingSeconds();

        if ($finalTest->duration_minutes && $remainingSeconds !== null && $remainingSeconds <= 0) {
            return $this->autoSubmit($attempt, $course);
        }

        $answers = $this->collectAnswers($request, $finalTest->questions);

        $this->persistAnswers($attempt, $answers);

        $score = $this->calculateScore($finalTest->questions, $answers);
        $passed = $score >= $finalTest->passing_score;

        $attempt->update([
            'score' => $score,
            'passed' => $passed,
            'submitted_at' => now(),
            'attempted_at' => now(),
        ]);

        return redirect()
            ->route('learning.courses.final-test.intro', $course)
            ->with('status', "Prova enviada! Nota final: {$score}%.");
    }

    private function autoSubmit(FinalTestAttempt $attempt, Course $course): RedirectResponse
    {
        $this->expireAttempt($attempt);

        return redirect()
            ->route('learning.courses.final-test.intro', $course)
            ->withErrors(['test' => 'Tempo esgotado. A tentativa foi encerrada.']);
    }

    private function expireAttempt(FinalTestAttempt $attempt): void
    {
        $attempt->update([
            'score' => 0,
            'passed' => false,
            'submitted_at' => now(),
            'attempted_at' => now(),
        ]);
    }

    private function collectAnswers(Request $request, $questions): array
    {
        $answers = [];

        foreach ($questions as $question) {
            $field = 'question_'.$question->id;
            $optionId = $request->input($field);

            if (! $optionId) {
                throw ValidationException::withMessages([
                    $field => 'Selecione uma alternativa.',
                ]);
            }

            $answers[$question->id] = (int) $optionId;
        }

        return $answers;
    }

    private function persistAnswers(FinalTestAttempt $attempt, array $answers): void
    {
        foreach ($answers as $questionId => $optionId) {
            $option = $attempt->finalTest->questions
                ->firstWhere('id', $questionId)
                ?->options
                ->firstWhere('id', $optionId);

            if (! $option) {
                throw ValidationException::withMessages([
                    'question_'.$questionId => 'Alternativa inválida.',
                ]);
            }

            $attempt->answers()->updateOrCreate(
                [
                    'final_test_question_id' => $questionId,
                ],
                [
                    'final_test_question_option_id' => $option?->id,
                    'is_correct' => (bool) ($option?->is_correct),
                ]
            );
        }
    }

    private function calculateScore($questions, array $answers): int
    {
        $totalWeight = $questions->sum('weight') ?: 1;
        $earnedWeight = 0;

        foreach ($questions as $question) {
            $selectedOptionId = $answers[$question->id] ?? null;
            $option = $question->options->firstWhere('id', $selectedOptionId);

            if ($option && $option->is_correct) {
                $earnedWeight += $question->weight;
            }
        }

        return (int) round(($earnedWeight / $totalWeight) * 100);
    }
}
