<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_ads_pixel',
        'carta_estagio',
        'favicon_path',
        'default_logo_path',
        'default_logo_dark_path',
        'default_course_cover_path',
        'default_module_cover_path',
        'default_lesson_cover_path',
        'certificate_title_size',
        'certificate_subtitle_size',
        'certificate_body_size',
        'certificate_front_line1',
        'certificate_front_line3',
        'certificate_front_line6',
    ];

    public static function current(): self
    {
        return static::first() ?? static::create();
    }

    public function assetUrl(?string $column): ?string
    {
        if (! $column) {
            return null;
        }

        $path = $this->{$column};

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
