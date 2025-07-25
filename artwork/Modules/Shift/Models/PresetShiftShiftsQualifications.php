<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $preset_shift_id
 * @property int $shift_qualification_id
 * @property int $value
 */
class PresetShiftShiftsQualifications extends Model
{
    protected $fillable = [
        'preset_shift_id',
        'shift_qualification_id',
        'value'
    ];

    public function presetShift(): HasOne
    {
        return $this->hasOne(PresetShift::class);
    }

    public function shiftQualification(): HasOne
    {
        return $this->hasOne(ShiftQualification::class);
    }

    public function scopeByShiftIdAndShiftQualificationId(
        Builder $builder,
        int $presetShiftId,
        int $shiftQualificationId
    ): Builder {
        return $builder
            ->where('preset_shift_id', $presetShiftId)
            ->where('shift_qualification_id', $shiftQualificationId);
    }
}
