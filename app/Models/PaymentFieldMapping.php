<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentFieldMapping extends Model
{
    use HasFactory;

    public const FIELD_BUYER_NAME = 'buyer_name';

    public const FIELD_BUYER_EMAIL = 'buyer_email';

    public const FIELD_COURSE_ID = 'course_id';

    public const FIELD_BUYER_WHATSAPP = 'buyer_whatsapp';

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

    /**
     * @return array<string, string>
     */
    public static function configurableFields(): array
    {
        return [
            self::FIELD_BUYER_NAME => 'Nome',
            self::FIELD_BUYER_EMAIL => 'Email',
            self::FIELD_COURSE_ID => 'curso_id',
            self::FIELD_BUYER_WHATSAPP => 'WhatsApp',
        ];
    }

    public function webhookLink(): BelongsTo
    {
        return $this->belongsTo(PaymentWebhookLink::class, 'payment_webhook_link_id');
    }
}
