<?php

namespace App\Models;

use App\Enums\EnrollmentAccessStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'access_status' => EnrollmentAccessStatus::class,
            'access_blocked_at' => 'datetime',
            'manual_override' => 'boolean',
            'manual_override_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manualOverrideByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_override_by');
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
