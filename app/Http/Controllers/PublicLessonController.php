<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lead;
use App\Models\LeadCourse;
use App\Models\LeadLesson;
use App\Models\Lesson;
use App\Services\WhatsappOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicLessonController extends Controller
{
    public function show(Request $request, Course $course): View
    {
        $course->load(['modules.lessons' => fn ($query) => $query->orderBy('position')]);

        $lessons = $course->modules
            ->sortBy('position')
            ->flatMap(fn ($module) => $module->lessons->sortBy('position'))
            ->values();

        $lead = $this->leadFromRequest($request);
        $isAuthenticated = $lead !== null;

        if ($lessons->isEmpty()) {
            return view('public.lesson', [
                'course' => $course,
                'lesson' => null,
                'lessons' => $lessons,
                'previousLesson' => null,
                'nextLesson' => null,
                'youtubeId' => null,
                'isAuthenticated' => false,
                'firstLessonId' => null,
                'completedLessonIds' => [],
                'showContinueMessage' => false,
                'isCompleted' => false,
            ]);
        }

        $firstLessonId = $lessons->first()->id;
        $requestedLessonId = $request->integer('lesson');
        $lesson = null;

        if ($isAuthenticated && $requestedLessonId) {
            $lesson = $lessons->firstWhere('id', $requestedLessonId);
        }

        $leadCourse = null;
        if ($isAuthenticated) {
            $leadCourse = LeadCourse::firstOrCreate([
                'lead_id' => $lead->id,
                'course_id' => $course->id,
            ]);

            if (! $lesson && $leadCourse->last_lesson_id) {
                $lesson = $lessons->firstWhere('id', $leadCourse->last_lesson_id);
            }
        }

        if (! $lesson) {
            $lesson = $lessons->first();
        }

        if ($isAuthenticated) {
            $leadCourse->last_lesson_id = $lesson->id;
            $leadCourse->save();

            $leadLesson = LeadLesson::firstOrCreate(
                [
                    'lead_id' => $lead->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'status' => 'nao_concluida',
                    'started_at' => now(),
                ]
            );

            if (! $leadLesson->started_at) {
                $leadLesson->started_at = now();
                $leadLesson->save();
            }
        }

        $currentIndex = $lessons->search(fn ($item) => $item->id === $lesson->id);
        $previousLesson = $currentIndex > 0 ? $lessons[$currentIndex - 1] : null;
        $nextLesson = $currentIndex < $lessons->count() - 1 ? $lessons[$currentIndex + 1] : null;

        $completedLessonIds = [];
        $isCompleted = false;
        $showContinueMessage = false;

        if ($isAuthenticated) {
            $completedLessonIds = LeadLesson::where('lead_id', $lead->id)
                ->where('status', 'concluida')
                ->pluck('lesson_id')
                ->all();

            $isCompleted = in_array($lesson->id, $completedLessonIds, true);
            $showContinueMessage = $leadCourse?->last_lesson_id !== null;
        }

        return view('public.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'youtubeId' => $this->extractYoutubeId($lesson?->video_url),
            'isAuthenticated' => $isAuthenticated,
            'firstLessonId' => $firstLessonId,
            'completedLessonIds' => $completedLessonIds,
            'showContinueMessage' => $showContinueMessage,
            'isCompleted' => $isCompleted,
        ]);
    }

    public function sendOtp(Request $request, WhatsappOtpService $service): JsonResponse
    {
        $validated = $request->validate([
            'whatsapp' => ['required', 'digits_between:10,11'],
        ]);

        $result = $service->send($validated['whatsapp'], $request->ip());

        return response()->json($result);
    }

    public function verifyOtp(Request $request, WhatsappOtpService $service): JsonResponse
    {
        $validated = $request->validate([
            'whatsapp' => ['required', 'digits_between:10,11'],
            'code' => ['required', 'digits:4'],
        ]);

        $result = $service->verify($validated['whatsapp'], $request->ip(), $validated['code']);

        if ($result['status'] !== 'verified') {
            return response()->json($result, 422);
        }

        $lead = Lead::firstOrCreate([
            'whatsapp' => $validated['whatsapp'],
        ]);

        $lead->session_token = (string) Str::uuid();
        $lead->save();

        return response()
            ->json(['status' => 'verified'])
            ->cookie('lead_session', $lead->session_token, 60 * 24 * 30);
    }

    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $lead = $this->leadFromRequest($request);

        if (! $lead) {
            return redirect()
                ->route('public.lessons.show', ['course' => $course])
                ->with('error', 'Faça login para continuar.');
        }

        abort_if($lesson->module->course_id !== $course->id, 404);

        $leadLesson = LeadLesson::updateOrCreate(
            [
                'lead_id' => $lead->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'status' => 'concluida',
                'completed_at' => now(),
            ]
        );

        if (! $leadLesson->started_at) {
            $leadLesson->started_at = now();
            $leadLesson->save();
        }

        LeadCourse::updateOrCreate(
            [
                'lead_id' => $lead->id,
                'course_id' => $course->id,
            ],
            [
                'last_lesson_id' => $lesson->id,
            ]
        );

        return redirect()->route('public.lessons.show', [
            'course' => $course,
            'lesson' => $lesson->id,
        ]);
    }

    private function leadFromRequest(Request $request): ?Lead
    {
        $token = $request->cookie('lead_session');

        if (! $token) {
            return null;
        }

        return Lead::where('session_token', $token)->first();
    }

    private function extractYoutubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $patterns = [
            '/youtu\.be\/([\w-]+)/',
            '/youtube\.com\/watch\?v=([\w-]+)/',
            '/youtube\.com\/embed\/([\w-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return Arr::get($matches, 1);
            }
        }

        return null;
    }
}
