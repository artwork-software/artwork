<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Pivot;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftFreelancer extends Pivot
{
    use SoftDeletes;

    protected $table = 'shifts_freelancers';

    protected $fillable = [
        'shift_id',
        'freelancer_id',
        'shift_qualification_id',
        'shift_count'
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function shiftQualification(): BelongsTo
    {
        return $this->belongsTo(ShiftQualification::class);
    }

    public function scopeAllByShiftIdAndShiftQualificationId(
        Builder $builder,
        int $shiftId,
        int $shiftQualificationId
    ): Builder {
        return $builder
            ->where('shift_id', $shiftId)
            ->where('shift_qualification_id', $shiftQualificationId);
    }

    public function scopeByUserIdAndShiftId(Builder $builder, int $userId, int $shiftId): Builder
    {
        return $builder->where('user_id', $userId)->where('shift_id', $shiftId);
    }
}
