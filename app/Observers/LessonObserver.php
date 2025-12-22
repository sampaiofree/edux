<?php

namespace App\Observers;

use App\Models\Lesson;

class LessonObserver
{
    public function saved(Lesson $lesson): void
    {
        $lesson->module?->course?->touch();
    }

    public function deleted(Lesson $lesson): void
    {
        $lesson->module?->course?->touch();
    }

    public function restored(Lesson $lesson): void
    {
        $lesson->module?->course?->touch();
    }
}
