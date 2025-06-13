<?php

namespace Artwork\Modules\Shift\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Pivot;
use Illuminate\Database\Eloquent\Builder;

class UserShiftQualification extends Pivot
{
    use HasFactory;

    protected $table = 'user_shift_qualifications';

    protected $fillable = [
        'user_id',
        'shift_qualification_id'
    ];

    public function scopeByUserIdAndShiftQualificationId(
        Builder $builder,
        int $userId,
        int $shiftQualificationId
    ): Builder {
        return $builder
            ->where('user_id', $userId)
            ->where('shift_qualification_id', $shiftQualificationId);
    }
}
