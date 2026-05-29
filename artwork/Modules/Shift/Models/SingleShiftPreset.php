<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class SingleShiftPreset extends Model
{
    /** @use HasFactory<\Database\Factories\SingleShiftPresetFactory> */
    use HasFactory;

    public const SHIFT_PLAN_CACHE_KEY = 'shift_plan:single_presets';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_duration',
        'craft_id',
        'description',
    ];

    protected static function booted(): void
    {
        // A single preset also appears embedded in the group-presets payload,
        // so changes here must invalidate both cached lists.
        $flush = static function (): void {
            Cache::forget(self::SHIFT_PLAN_CACHE_KEY);
            Cache::forget(ShiftPresetGroup::SHIFT_PLAN_CACHE_KEY);
        };
        static::saved($flush);
        static::deleted($flush);
    }

    public function craft(): BelongsTo
    {
        return $this->belongsTo(
            Craft::class,
            'craft_id',
            'id',
            'crafts'
        )->without(['users']);
    }

    // map shift qualifications relation
    public function shiftsQualifications(): BelongsToMany
    {
        return $this->belongsToMany(
            ShiftQualification::class,
            'single_shift_preset_qualifications',
            'single_shift_preset_id',
            'shift_qualification_id',
            'id',
            'id',
            'shiftQualifications'
        )->withPivot(['quantity']);
    }
}
