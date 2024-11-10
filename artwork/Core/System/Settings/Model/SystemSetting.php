<?php

namespace Artwork\Core\System\Settings\Model;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\System\Settings\SystemSettingKeys;

class SystemSetting extends Model
{
    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (!SystemSettingKeys::tryFrom($model->key)) {
                throw new \InvalidArgumentException('Invalid system setting key ' . $model->key);
            }
        });
    }

    protected $table = 'system_settings';

    protected $casts = [
        'value' => 'json',
    ];
}