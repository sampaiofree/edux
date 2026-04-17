<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseWebhookId extends Model
{
    use HasFactory;

    protected $table = 'curso_webhook_ids';

    protected static function booted(): void
    {
        static::creating(function (self $courseWebhookId): void {
            if (filled($courseWebhookId->system_setting_id) || ! $courseWebhookId->course_id) {
                return;
            }

            $courseWebhookId->system_setting_id = Course::withoutGlobalScopes()
                ->whereKey($courseWebhookId->course_id)
                ->value('system_setting_id');
        });
    }

    protected $fillable = [
        'course_id',
        'system_setting_id',
        'webhook_id',
        'platform',
    ];

    public function systemSetting(): BelongsTo
    {
        return $this->belongsTo(SystemSetting::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
