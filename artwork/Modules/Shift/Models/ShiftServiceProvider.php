<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Pivot;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftServiceProvider extends Pivot
{
    use SoftDeletes;

    protected $table = 'shifts_service_providers';

    protected $fillable = [
        'shift_id',
        'service_provider_id',
        'shift_qualification_id',
        'shift_count'
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
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
