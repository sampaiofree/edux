<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_checkout_id',
        'nome',
        'descricao',
        'preco',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
        ];
    }

    public function courseCheckout(): BelongsTo
    {
        return $this->belongsTo(CourseCheckout::class);
    }
}
