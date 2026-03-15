<?php

namespace App\Models;

use App\Enums\PaymentEntitlementState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEntitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'payment_webhook_link_id',
        'external_tx_id',
        'external_product_id',
        'state',
        'last_event_at',
        'last_payment_event_id',
    ];

    protected function casts(): array
    {
        return [
            'state' => PaymentEntitlementState::class,
            'last_event_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function webhookLink(): BelongsTo
    {
        return $this->belongsTo(PaymentWebhookLink::class, 'payment_webhook_link_id');
    }

    public function lastPaymentEvent(): BelongsTo
    {
        return $this->belongsTo(PaymentEvent::class, 'last_payment_event_id');
    }
}
