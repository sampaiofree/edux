<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;

trait HandlesCourseAuthorization
{
    protected function ensureCanManageCourse(User $user, Course $course): void
    {
        if ($user->isAdmin() && (int) $user->system_setting_id === (int) $course->system_setting_id) {
            return;
        }

        abort(403);
    }
}
