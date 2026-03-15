<?php

namespace App\Models;

use App\Enums\PaymentInternalAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEventMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_webhook_link_id',
        'external_event_code',
        'internal_action',
    ];

    protected function casts(): array
    {
        return [
            'internal_action' => PaymentInternalAction::class,
        ];
    }

    public function webhookLink(): BelongsTo
    {
        return $this->belongsTo(PaymentWebhookLink::class, 'payment_webhook_link_id');
    }
}
