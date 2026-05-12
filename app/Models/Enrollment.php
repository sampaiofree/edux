<?php

namespace App\Models;

use App\Enums\EnrollmentAccessStatus;
use App\Models\Concerns\BelongsToSystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use BelongsToSystemSetting;
    use HasFactory;

    public const CERTIFICATE_ISSUANCE_BLOCKED_MESSAGE = 'A emissão do certificado deste curso está temporariamente bloqueada. Fale com o suporte da escola para liberar seu certificado.';

    protected $fillable = [
        'system_setting_id',
        'course_id',
        'user_id',
        'progress_percent',
        'completed_at',
        'access_status',
        'access_block_reason',
        'access_blocked_at',
        'manual_override',
        'manual_override_by',
        'manual_override_at',
        'certificate_issuance_blocked',
        'certificate_issuance_block_reason',
        'certificate_workload_minutes',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'access_status' => EnrollmentAccessStatus::class,
            'access_blocked_at' => 'datetime',
            'manual_override' => 'boolean',
            'manual_override_at' => 'datetime',
            'certificate_issuance_blocked' => 'boolean',
            'certificate_workload_minutes' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    protected function resolveSystemSettingIdForNewRecord(): ?int
    {
        if ($this->course_id) {
            return Course::withoutGlobalScopes()
                ->whereKey($this->course_id)
                ->value('system_setting_id');
        }

        if ($this->user_id) {
            return User::withoutGlobalScopes()
                ->whereKey($this->user_id)
                ->value('system_setting_id');
        }

        return SystemSetting::currentId();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manualOverrideByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_override_by');
    }

    public function effectiveCertificateWorkloadMinutes(?Course $course = null): ?int
    {
        if ($this->certificate_workload_minutes !== null && (int) $this->certificate_workload_minutes > 0) {
            return (int) $this->certificate_workload_minutes;
        }

        $course ??= $this->relationLoaded('course') ? $this->course : $this->course()->first();
        $minutes = $course?->duration_minutes;

        return $minutes !== null && (int) $minutes > 0 ? (int) $minutes : null;
    }

    public function certificateWorkloadHoursForInput(): ?string
    {
        if ($this->certificate_workload_minutes === null) {
            return null;
        }

        return self::formatWorkloadHours((int) $this->certificate_workload_minutes, '.');
    }

    public static function formatWorkloadHours(?int $minutes, string $decimalSeparator = ','): ?string
    {
        if ($minutes === null || $minutes <= 0) {
            return null;
        }

        $hours = $minutes / 60;
        $label = rtrim(rtrim(number_format($hours, 2, $decimalSeparator, ''), '0'), $decimalSeparator);

        return $label !== '' ? $label : null;
    }

    public function scopeAccessible(Builder $query): Builder
    {
        return $query->where(function (Builder $subQuery): void {
            $subQuery->where('access_status', EnrollmentAccessStatus::ACTIVE->value)
                ->orWhere('manual_override', true);
        });
    }

    public function recalculateProgress(): void
    {
        $this->loadMissing(['course', 'user']);

        $totalLessons = $this->course->lessons()->count();

        if ($totalLessons === 0) {
            $this->forceFill([
                'progress_percent' => 0,
                'completed_at' => null,
            ])->save();

            return;
        }

        $completedLessons = $this->user->lessonCompletions()
            ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $this->course_id))
            ->count();

        $progress = (int) round(($completedLessons / $totalLessons) * 100);

        $this->forceFill([
            'progress_percent' => min(100, $progress),
            'completed_at' => $progress >= 100 ? now() : null,
        ])->save();
    }
}
