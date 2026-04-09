<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;

trait HandlesCourseAuthorization
{
    protected function ensureCanManageCourse(User $user, Course $course): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->canManageCourseContent() && $user->canAccessSystemSetting($course->system_setting_id)) {
            return;
        }

        abort(403);
    }
}
