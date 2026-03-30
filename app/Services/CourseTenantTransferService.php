<?php

namespace App\Services;

use App\Enums\EnrollmentAccessStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\CertificateBranding;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FinalTestAttempt;
use App\Models\LessonCompletion;
use App\Models\SupportWhatsappNumber;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseTenantTransferService
{
    public function transfer(Course $course, array $attributes): Course
    {
        return DB::transaction(function () use ($course, $attributes): Course {
            $course = Course::withoutGlobalScopes()->findOrFail($course->id);

            $targetSystemSettingId = (int) $attributes['system_setting_id'];
            $ownerId = (int) $attributes['owner_id'];

            $this->ensureOwnerMatchesTenant($ownerId, $targetSystemSettingId);

            $lessonIds = DB::table('lessons')
                ->join('modules', 'modules.id', '=', 'lessons.module_id')
                ->where('modules.course_id', $course->id)
                ->pluck('lessons.id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $finalTestId = DB::table('final_tests')
                ->where('course_id', $course->id)
                ->value('id');
            $finalTestId = $finalTestId !== null ? (int) $finalTestId : null;

            $enrollments = Enrollment::withoutGlobalScopes()
                ->where('course_id', $course->id)
                ->orderBy('id')
                ->get();

            foreach ($enrollments as $enrollment) {
                $this->transferEnrollment(
                    $course,
                    $enrollment,
                    $targetSystemSettingId,
                    $lessonIds,
                    $finalTestId,
                );
            }

            CertificateBranding::withoutGlobalScopes()
                ->where('course_id', $course->id)
                ->update([
                    'system_setting_id' => $targetSystemSettingId,
                    'updated_at' => now(),
                ]);

            if ($course->support_whatsapp_number_id !== null) {
                $supportWhatsappTenantId = SupportWhatsappNumber::withoutGlobalScopes()
                    ->whereKey($course->support_whatsapp_number_id)
                    ->value('system_setting_id');

                if ($supportWhatsappTenantId !== null && (int) $supportWhatsappTenantId !== $targetSystemSettingId) {
                    $attributes['support_whatsapp_number_id'] = null;
                }
            }

            $course->fill([
                'system_setting_id' => $targetSystemSettingId,
                'owner_id' => $ownerId,
                'title' => $attributes['title'],
                'summary' => $attributes['summary'] ?? null,
                'description' => $attributes['description'] ?? null,
                'status' => $attributes['status'],
                'duration_minutes' => $attributes['duration_minutes'] ?? null,
                'published_at' => $attributes['published_at'] ?? null,
                'promo_video_url' => $attributes['promo_video_url'] ?? null,
                'support_whatsapp_number_id' => array_key_exists('support_whatsapp_number_id', $attributes)
                    ? $attributes['support_whatsapp_number_id']
                    : $course->support_whatsapp_number_id,
            ]);

            if (array_key_exists('slug', $attributes)) {
                $course->slug = (string) $attributes['slug'];
            }

            $course->save();

            return $course->fresh([
                'systemSetting',
                'owner' => fn ($query) => $query->withoutGlobalScopes(),
            ]);
        });
    }

    private function transferEnrollment(
        Course $course,
        Enrollment $sourceEnrollment,
        int $targetSystemSettingId,
        array $lessonIds,
        ?int $finalTestId,
    ): void {
        $sourceUser = User::withoutGlobalScopes()->findOrFail($sourceEnrollment->user_id);
        $destinationUser = $this->resolveDestinationUser($sourceUser, $targetSystemSettingId);

        $this->transferEducationalHistory(
            $course->id,
            $lessonIds,
            $finalTestId,
            $sourceUser,
            $destinationUser,
        );

        $destinationEnrollment = Enrollment::withoutGlobalScopes()
            ->where('course_id', $course->id)
            ->where('user_id', $destinationUser->id)
            ->where('id', '!=', $sourceEnrollment->id)
            ->first();

        if (! $destinationEnrollment) {
            $sourceEnrollment->forceFill([
                'user_id' => $destinationUser->id,
                'system_setting_id' => $targetSystemSettingId,
            ])->save();

            return;
        }

        $this->consolidateEnrollment(
            $sourceEnrollment,
            $destinationEnrollment,
            $targetSystemSettingId,
        );

        $sourceEnrollment->delete();
    }

    private function transferEducationalHistory(
        int $courseId,
        array $lessonIds,
        ?int $finalTestId,
        User $sourceUser,
        User $destinationUser,
    ): void {
        if ($sourceUser->id === $destinationUser->id) {
            return;
        }

        $this->transferLessonCompletions($lessonIds, $sourceUser, $destinationUser);
        $this->transferFinalTestAttempts($finalTestId, $sourceUser, $destinationUser);
        $this->transferCertificates($courseId, $sourceUser, $destinationUser);
    }

    private function transferLessonCompletions(array $lessonIds, User $sourceUser, User $destinationUser): void
    {
        if ($lessonIds === []) {
            return;
        }

        LessonCompletion::query()
            ->where('user_id', $sourceUser->id)
            ->whereIn('lesson_id', $lessonIds)
            ->orderBy('id')
            ->get()
            ->each(function (LessonCompletion $completion) use ($destinationUser): void {
                $existingCompletion = LessonCompletion::query()
                    ->where('lesson_id', $completion->lesson_id)
                    ->where('user_id', $destinationUser->id)
                    ->first();

                if ($existingCompletion) {
                    $completion->delete();

                    return;
                }

                $completion->forceFill([
                    'user_id' => $destinationUser->id,
                ])->save();
            });
    }

    private function transferFinalTestAttempts(?int $finalTestId, User $sourceUser, User $destinationUser): void
    {
        if ($finalTestId === null) {
            return;
        }

        $targetAttemptsBySignature = FinalTestAttempt::query()
            ->where('final_test_id', $finalTestId)
            ->where('user_id', $destinationUser->id)
            ->get()
            ->keyBy(fn (FinalTestAttempt $attempt): string => $this->attemptSignature($attempt));

        FinalTestAttempt::query()
            ->where('final_test_id', $finalTestId)
            ->where('user_id', $sourceUser->id)
            ->orderBy('id')
            ->get()
            ->each(function (FinalTestAttempt $attempt) use ($destinationUser, $targetAttemptsBySignature): void {
                $signature = $this->attemptSignature($attempt);

                if ($targetAttemptsBySignature->has($signature)) {
                    $attempt->delete();

                    return;
                }

                $attempt->forceFill([
                    'user_id' => $destinationUser->id,
                ])->save();

                $targetAttemptsBySignature->put($signature, $attempt);
            });
    }

    private function transferCertificates(int $courseId, User $sourceUser, User $destinationUser): void
    {
        $destinationCertificate = Certificate::query()
            ->where('course_id', $courseId)
            ->where('user_id', $destinationUser->id)
            ->first();

        Certificate::query()
            ->where('course_id', $courseId)
            ->where('user_id', $sourceUser->id)
            ->orderBy('id')
            ->get()
            ->each(function (Certificate $certificate) use ($destinationUser, &$destinationCertificate): void {
                if ($destinationCertificate) {
                    $certificate->delete();

                    return;
                }

                $certificate->forceFill([
                    'user_id' => $destinationUser->id,
                ])->save();

                $destinationCertificate = $certificate;
            });
    }

    private function consolidateEnrollment(
        Enrollment $sourceEnrollment,
        Enrollment $destinationEnrollment,
        int $targetSystemSettingId,
    ): void {
        $finalStatus = $this->mergeAccessStatus($sourceEnrollment, $destinationEnrollment);
        $manualOverride = (bool) $sourceEnrollment->manual_override || (bool) $destinationEnrollment->manual_override;

        $manualOverrideSource = $this->pickManualOverrideSource($sourceEnrollment, $destinationEnrollment);
        $blockedSource = $this->pickBlockedSource($sourceEnrollment, $destinationEnrollment);

        $destinationEnrollment->forceFill([
            'system_setting_id' => $targetSystemSettingId,
            'progress_percent' => max((int) $sourceEnrollment->progress_percent, (int) $destinationEnrollment->progress_percent),
            'completed_at' => $this->earliestNonNullDate(
                $sourceEnrollment->completed_at,
                $destinationEnrollment->completed_at,
            ),
            'access_status' => $finalStatus,
            'access_block_reason' => $finalStatus === EnrollmentAccessStatus::BLOCKED->value
                ? $blockedSource?->access_block_reason
                : null,
            'access_blocked_at' => $finalStatus === EnrollmentAccessStatus::BLOCKED->value
                ? $blockedSource?->access_blocked_at
                : null,
            'manual_override' => $manualOverride,
            'manual_override_by' => $manualOverride ? $manualOverrideSource?->manual_override_by : null,
            'manual_override_at' => $manualOverride ? $manualOverrideSource?->manual_override_at : null,
        ])->save();
    }

    private function resolveDestinationUser(User $sourceUser, int $targetSystemSettingId): User
    {
        $normalizedEmail = mb_strtolower((string) $sourceUser->email);

        $destinationUser = User::withoutGlobalScopes()
            ->where('system_setting_id', $targetSystemSettingId)
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if ($destinationUser) {
            return $destinationUser;
        }

        $destinationUser = new User();
        $destinationUser->forceFill([
            'system_setting_id' => $targetSystemSettingId,
            'name' => $sourceUser->name,
            'display_name' => $sourceUser->display_name,
            'email' => $sourceUser->email,
            'whatsapp' => $sourceUser->whatsapp,
            'qualification' => $sourceUser->qualification,
            'profile_photo_path' => $sourceUser->profile_photo_path,
            'email_verified_at' => $sourceUser->email_verified_at,
            'password' => $sourceUser->getAuthPassword(),
            'remember_token' => $sourceUser->remember_token,
            'role' => UserRole::STUDENT->value,
        ]);
        $destinationUser->save();

        return $destinationUser->fresh();
    }

    private function ensureOwnerMatchesTenant(int $ownerId, int $targetSystemSettingId): void
    {
        $owner = User::withoutGlobalScopes()->find($ownerId);

        if (! $owner || ($owner->role->value ?? $owner->role) !== UserRole::ADMIN->value || (int) $owner->system_setting_id !== $targetSystemSettingId) {
            throw ValidationException::withMessages([
                'owner_id' => 'Selecione um responsável administrador que pertença à escola escolhida.',
            ]);
        }
    }

    private function mergeAccessStatus(Enrollment $sourceEnrollment, Enrollment $destinationEnrollment): string
    {
        $statuses = [
            $sourceEnrollment->access_status?->value ?? $sourceEnrollment->access_status,
            $destinationEnrollment->access_status?->value ?? $destinationEnrollment->access_status,
        ];

        return in_array(EnrollmentAccessStatus::ACTIVE->value, $statuses, true)
            ? EnrollmentAccessStatus::ACTIVE->value
            : EnrollmentAccessStatus::BLOCKED->value;
    }

    private function pickManualOverrideSource(Enrollment $sourceEnrollment, Enrollment $destinationEnrollment): ?Enrollment
    {
        $candidates = collect([$sourceEnrollment, $destinationEnrollment])
            ->filter(fn (Enrollment $enrollment): bool => (bool) $enrollment->manual_override);

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortByDesc(fn (Enrollment $enrollment): int => (($enrollment->manual_override_at?->getTimestamp() ?? 0) * 10)
                + ($enrollment->manual_override_by ? 1 : 0))
            ->first();
    }

    private function pickBlockedSource(Enrollment $sourceEnrollment, Enrollment $destinationEnrollment): ?Enrollment
    {
        $candidates = collect([$sourceEnrollment, $destinationEnrollment])
            ->filter(function (Enrollment $enrollment): bool {
                $status = $enrollment->access_status?->value ?? $enrollment->access_status;

                return $status === EnrollmentAccessStatus::BLOCKED->value;
            });

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortByDesc(fn (Enrollment $enrollment): int => (($enrollment->access_blocked_at?->getTimestamp() ?? 0) * 10)
                + (filled($enrollment->access_block_reason) ? 1 : 0))
            ->first();
    }

    private function earliestNonNullDate(?CarbonInterface $first, ?CarbonInterface $second): ?CarbonInterface
    {
        if (! $first) {
            return $second;
        }

        if (! $second) {
            return $first;
        }

        return $first->lessThanOrEqualTo($second) ? $first : $second;
    }

    private function attemptSignature(FinalTestAttempt $attempt): string
    {
        return implode('|', [
            $attempt->final_test_id,
            $attempt->score,
            $attempt->passed ? '1' : '0',
            $attempt->attempted_at?->format('Y-m-d H:i:s.u') ?? '',
            $attempt->started_at?->format('Y-m-d H:i:s.u') ?? '',
            $attempt->submitted_at?->format('Y-m-d H:i:s.u') ?? '',
        ]);
    }
}
