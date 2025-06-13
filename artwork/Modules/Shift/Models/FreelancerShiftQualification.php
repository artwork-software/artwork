<?php

namespace Artwork\Modules\Shift\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Pivot;

class FreelancerShiftQualification extends Pivot
{
    use HasFactory;

    protected $table = 'freelancer_shift_qualifications';

    protected $fillable = [
        'freelancer_id',
        'shift_qualification_id'
    ];

    public function scopeByFreelancerIdAndShiftQualificationId(
        Builder $builder,
        int $freelancerId,
        int $shiftQualificationId
    ): Builder {
        return $builder
            ->where('freelancer_id', $freelancerId)
            ->where('shift_qualification_id', $shiftQualificationId);
    }
}
