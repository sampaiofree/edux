<?php

namespace App\Models;

use App\Models\Kavoo;
use App\Support\CityCampaignCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(static fn () => CityCampaignCache::bumpCourses());
        static::deleted(static fn () => CityCampaignCache::bumpCourses());
    }

    protected $fillable = [
        'owner_id',
        'title',
        'slug',
        'summary',
        'description',
        'cover_image_path',
        'promo_video_url',
        'status',
        'duration_minutes',
        'published_at',
        'kavoo_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'certificate_price' => 'decimal:2',
            'kavoo_id' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function finalTest(): HasOne
    {
        return $this->hasOne(FinalTest::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function certificateBranding(): HasOne
    {
        return $this->hasOne(CertificateBranding::class);
    }

    public function certificatePayments(): HasMany
    {
        return $this->hasMany(CertificatePayment::class);
    }

    public function kavooRecords(): HasMany
    {
        return $this->hasMany(Kavoo::class, 'item_product_id', 'kavoo_id');
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(CourseCheckout::class);
    }

    public function nextLessonFor(User $user): ?Lesson
    {
        $completedLessonIds = $user->lessonCompletions()
            ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $this->id))
            ->pluck('lesson_id')
            ->all();

        return $this->lessons()
            ->with('module')
            ->orderBy('modules.position')
            ->orderBy('lessons.position')
            ->get()
            ->first(fn (Lesson $lesson) => ! in_array($lesson->id, $completedLessonIds, true));
    }

    public function completionPercentageFor(User $user): int
    {
        $totalLessons = $this->lessons()->count();

        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = $user->lessonCompletions()
            ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $this->id))
            ->count();

        return (int) round(($completedLessons / $totalLessons) * 100);
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path
            ? asset('storage/'.$this->cover_image_path)
            : null;
    }
}
