<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentWebhookLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'endpoint_uuid',
        'is_active',
        'security_mode',
        'secret',
        'signature_header',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(PaymentFieldMapping::class);
    }

    public function eventMappings(): HasMany
    {
        return $this->hasMany(PaymentEventMapping::class);
    }

    public function productMappings(): HasMany
    {
        return $this->hasMany(PaymentProductMapping::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }
}
