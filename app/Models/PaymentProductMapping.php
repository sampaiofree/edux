<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProductMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_webhook_link_id',
        'external_product_id',
        'course_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function webhookLink(): BelongsTo
    {
        return $this->belongsTo(PaymentWebhookLink::class, 'payment_webhook_link_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
