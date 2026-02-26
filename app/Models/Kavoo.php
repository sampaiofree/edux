<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class Kavoo extends Model
{
    use HasFactory;

    public const DISPLAY_TIMEZONE = 'America/Sao_Paulo';

    protected $table = 'kavoo';

    protected $fillable = [
        'customer_name',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'customer_phone',
        'item_product_id',
        'item_product_name',
        'transaction_code',
        'status_code',
        'customer',
        'address',
        'items',
        'affiliate',
        'transaction',
        'payment',
        'commissions',
        'shipping',
        'links',
        'tracking',
        'status',
        'recurrence',
    ];

    protected $casts = [
        'customer' => 'array',
        'address' => 'array',
        'items' => 'array',
        'affiliate' => 'array',
        'transaction' => 'array',
        'payment' => 'array',
        'commissions' => 'array',
        'shipping' => 'array',
        'links' => 'array',
        'tracking' => 'array',
        'status' => 'array',
        'recurrence' => 'array',
    ];

    public function customerEmailOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_email', 'email');
    }

    public function customerPhoneOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_phone', 'whatsapp');
    }

    public function getCustomerUserAttribute(): ?User
    {
        return $this->customerEmailOwner ?? $this->customerPhoneOwner;
    }

    public function scopeWithCustomerRelations(Builder $query): Builder
    {
        return $query->with(['customerEmailOwner', 'customerPhoneOwner']);
    }

    public function occurredAtUtc(): ?CarbonImmutable
    {
        $candidates = [
            Arr::get($this->transaction, 'approved_at'),
            Arr::get($this->payment, 'approved_at'),
            Arr::get($this->status, 'updated_at'),
            Arr::get($this->transaction, 'updated_at'),
            Arr::get($this->transaction, 'created_at'),
            optional($this->updated_at)?->toIso8601String(),
            optional($this->created_at)?->toIso8601String(),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            try {
                return CarbonImmutable::parse($candidate)->utc();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    public function occurredAtSaoPaulo(): ?CarbonImmutable
    {
        return $this->occurredAtUtc()?->setTimezone(self::DISPLAY_TIMEZONE);
    }

    public function createdAtSaoPaulo(): ?CarbonImmutable
    {
        if (! $this->created_at) {
            return null;
        }

        try {
            return CarbonImmutable::parse($this->created_at)->setTimezone(self::DISPLAY_TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }
}
