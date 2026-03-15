<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentFieldMapping extends Model
{
    use HasFactory;

    public const FIELD_BUYER_EMAIL = 'buyer_email';
    public const FIELD_EVENT_CODE = 'event_code';
    public const FIELD_EXTERNAL_TX_ID = 'external_tx_id';
    public const FIELD_AMOUNT = 'amount';
    public const FIELD_CURRENCY = 'currency';
    public const FIELD_OCCURRED_AT = 'occurred_at';
    public const FIELD_ITEMS = 'items';
    public const FIELD_ITEM_PRODUCT_ID = 'item_product_id';

    protected $fillable = [
        'payment_webhook_link_id',
        'field_key',
        'json_path',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function webhookLink(): BelongsTo
    {
        return $this->belongsTo(PaymentWebhookLink::class, 'payment_webhook_link_id');
    }
}
