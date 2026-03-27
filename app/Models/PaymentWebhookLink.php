<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSystemSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentWebhookLink extends Model
{
    use BelongsToSystemSetting;
    use HasFactory;

    public const ACTION_REGISTER = 'register';

    public const ACTION_BLOCK = 'block';

    protected $fillable = [
        'system_setting_id',
        'name',
        'endpoint_uuid',
        'is_active',
        'action_mode',
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

    /**
     * @return array<string, string>
     */
    public static function actionModes(): array
    {
        return [
            self::ACTION_REGISTER => 'Cadastrar',
            self::ACTION_BLOCK => 'Bloquear',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function resolveSystemSettingIdForNewRecord(): ?int
    {
        if ($this->created_by) {
            return User::withoutGlobalScopes()
                ->whereKey($this->created_by)
                ->value('system_setting_id');
        }

        return SystemSetting::currentId();
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
