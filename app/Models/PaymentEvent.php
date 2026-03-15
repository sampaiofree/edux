<?php

namespace App\Models;

use App\Enums\PaymentInternalAction;
use App\Enums\PaymentProcessingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_webhook_link_id',
        'payload_hash',
        'raw_payload',
        'raw_headers',
        'external_event_code',
        'internal_action',
        'buyer_email',
        'external_tx_id',
        'external_product_id',
        'amount',
        'currency',
        'occurred_at',
        'received_at',
        'processing_status',
        'processing_reason',
        'processed_at',
        'replay_of_payment_event_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'raw_headers' => 'array',
            'internal_action' => PaymentInternalAction::class,
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'amount' => 'decimal:2',
            'processing_status' => PaymentProcessingStatus::class,
        ];
    }

    public function webhookLink(): BelongsTo
    {
        return $this->belongsTo(PaymentWebhookLink::class, 'payment_webhook_link_id');
    }

    public function replayOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replay_of_payment_event_id');
    }

    public function replays(): HasMany
    {
        return $this->hasMany(self::class, 'replay_of_payment_event_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentProcessingLog::class)->orderBy('id');
    }
}
