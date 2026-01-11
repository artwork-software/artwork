<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Contracts\Employable;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftWorker extends MorphPivot
{
    use SoftDeletes;

    protected $table = 'shift_workers';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function shiftQualification(): BelongsTo
    {
        return $this->belongsTo(ShiftQualification::class);
    }

    public function getEmployableAttribute(): ?Employable
    {
        $employableType = $this->employable_type;
        $employableId = $this->employable_id;

        if (!$employableType || !$employableId) {
            return null;
        }

        return $employableType::find($employableId);
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

    public function scopeByEmployableIdAndShiftId(
        Builder $builder,
        string $employableType,
        int $employableId,
        int $shiftId
    ): Builder {
        return $builder
            ->where('employable_type', $employableType)
            ->where('employable_id', $employableId)
            ->where('shift_id', $shiftId);
    }

    public function scopeByEmployableType(
        Builder $builder,
        string $employableType
    ): Builder {
        return $builder->where('employable_type', $employableType);
    }
}
