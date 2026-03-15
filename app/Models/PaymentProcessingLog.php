<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_event_id',
        'step',
        'level',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function paymentEvent(): BelongsTo
    {
        return $this->belongsTo(PaymentEvent::class);
    }
}
