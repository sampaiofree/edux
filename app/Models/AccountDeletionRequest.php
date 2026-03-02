<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeletionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'requested_name',
        'requested_email',
        'requested_whatsapp',
        'reason',
        'status',
        'requested_at',
        'resolved_at',
        'resolved_by',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
