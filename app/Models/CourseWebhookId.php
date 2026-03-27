<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseWebhookId extends Model
{
    use HasFactory;

    protected $table = 'curso_webhook_ids';

    protected $fillable = [
        'course_id',
        'webhook_id',
        'platform',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
