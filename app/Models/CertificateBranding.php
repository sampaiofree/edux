<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSystemSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CertificateBranding extends Model
{
    use BelongsToSystemSetting;
    use HasFactory;

    protected $fillable = [
        'system_setting_id',
        'course_id',
        'front_background_path',
        'back_background_path',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public static function resolveForCourse(Course $course): CertificateBranding
    {
        $course->loadMissing('certificateBranding');
        $courseBranding = $course->certificateBranding;
        $globalBranding = self::query()->firstOrCreate([
            'course_id' => null,
            'system_setting_id' => $course->system_setting_id,
        ]);

        if (! $courseBranding) {
            return $globalBranding;
        }

        if (! $courseBranding->front_background_path) {
            $courseBranding->setAttribute('front_background_path', $globalBranding->front_background_path);
        }

        if (! $courseBranding->back_background_path) {
            $courseBranding->setAttribute('back_background_path', $globalBranding->back_background_path);
        }

        return $courseBranding;
    }

    protected function resolveSystemSettingIdForNewRecord(): ?int
    {
        if ($this->course_id) {
            return Course::withoutGlobalScopes()
                ->whereKey($this->course_id)
                ->value('system_setting_id');
        }

        return SystemSetting::currentId();
    }

    public function getFrontBackgroundUrlAttribute(): ?string
    {
        return $this->front_background_path
            ? Storage::disk('public')->url($this->front_background_path)
            : null;
    }

    public function getBackBackgroundUrlAttribute(): ?string
    {
        return $this->back_background_path
            ? Storage::disk('public')->url($this->back_background_path)
            : null;
    }
}
