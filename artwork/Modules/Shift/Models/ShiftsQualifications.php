<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $shift_id
 * @property int $shift_qualification_id
 * @property int $value
 */
class ShiftsQualifications extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shift_id',
        'shift_qualification_id',
        'value'
    ];

    public function shift(): HasOne
    {
        return $this->hasOne(Shift::class);
    }

    public function shiftQualification(): HasOne
    {
        return $this->hasOne(ShiftQualification::class);
    }

    public function scopeByShiftIdAndShiftQualificationId(
        Builder $builder,
        int $shiftId,
        int $shiftQualificationId
    ): Builder {
        return $builder
            ->where('shift_id', $shiftId)
            ->where('shift_qualification_id', $shiftQualificationId);
    }
}
