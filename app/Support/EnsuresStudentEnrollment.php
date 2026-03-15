<?php

namespace App\Support;

use App\Enums\EnrollmentAccessStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

trait EnsuresStudentEnrollment
{
    protected function ensureEnrollment(User $user, Course $course): Enrollment
    {
        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        abort_if(! $enrollment, 403, 'Você não está matriculado neste curso.');
        abort_if(
            ! $enrollment->manual_override
                && $enrollment->access_status !== EnrollmentAccessStatus::ACTIVE,
            403,
            'Seu acesso a este curso está bloqueado.'
        );

        return $enrollment;
    }
}
