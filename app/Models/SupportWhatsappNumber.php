<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportWhatsappNumber extends Model
{
    use BelongsToSystemSetting;
    use HasFactory;

    protected $fillable = [
        'system_setting_id',
        'label',
        'whatsapp',
        'description',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function whatsappLink(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp) ?: '';

        return 'https://wa.me/'.$digits;
    }
}
