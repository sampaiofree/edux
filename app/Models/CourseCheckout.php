<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCheckout extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'hours',
        'price',
        'checkout_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
