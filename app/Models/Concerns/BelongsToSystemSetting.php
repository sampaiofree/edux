<?php

namespace App\Models\Concerns;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

trait BelongsToSystemSetting
{
    public static function bootBelongsToSystemSetting(): void
    {
        static::addGlobalScope('system_setting', function (Builder $builder): void {
            $model = $builder->getModel();

            if (! self::supportsSystemSettingColumn($model)) {
                return;
            }

            $systemSettingId = SystemSetting::currentId();

            if ($systemSettingId === null) {
                return;
            }

            $builder->where($model->qualifyColumn('system_setting_id'), $systemSettingId);
        });

        static::creating(function (Model $model): void {
            if (! self::supportsSystemSettingColumn($model)) {
                return;
            }

            if (filled($model->getAttribute('system_setting_id'))) {
                return;
            }

            $systemSettingId = method_exists($model, 'resolveSystemSettingIdForNewRecord')
                ? $model->resolveSystemSettingIdForNewRecord()
                : null;

            if ($systemSettingId !== null) {
                $model->setAttribute('system_setting_id', $systemSettingId);
            }
        });
    }

    public function systemSetting(): BelongsTo
    {
        return $this->belongsTo(SystemSetting::class);
    }

    protected function resolveSystemSettingIdForNewRecord(): ?int
    {
        return SystemSetting::currentId();
    }

    private static function supportsSystemSettingColumn(Model $model): bool
    {
        try {
            return Schema::hasTable($model->getTable())
                && Schema::hasColumn($model->getTable(), 'system_setting_id');
        } catch (\Throwable) {
            return false;
        }
    }
}
