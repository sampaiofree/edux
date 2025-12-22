<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp',
        'session_token',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(LeadCourse::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LeadLesson::class);
    }
}
