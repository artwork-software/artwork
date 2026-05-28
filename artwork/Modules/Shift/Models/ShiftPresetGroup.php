<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ShiftPresetGroup extends Model
{
    use HasFactory;

    public const SHIFT_PLAN_CACHE_KEY = 'shift_plan:group_presets';

    protected $fillable = [
        'name'
    ];

    protected static function booted(): void
    {
        $flush = static function (): void {
            Cache::forget(self::SHIFT_PLAN_CACHE_KEY);
        };
        static::saved($flush);
        static::deleted($flush);
    }

    public function presets(): BelongsToMany
    {
        return $this->belongsToMany(
            SingleShiftPreset::class,
            'shift_preset_group_assignments',
            'shift_preset_group_id',
            'single_shift_preset_id'
        );
    }
}

