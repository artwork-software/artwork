<?php

namespace Artwork\Modules\Shift\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Pivot;

class ServiceProviderShiftQualification extends Pivot
{
    use HasFactory;

    protected $table = 'service_provider_shift_qualifications';

    protected $fillable = [
        'service_provider_id',
        'shift_qualification_id'
    ];

    public function scopeByServiceProviderIdAndShiftQualificationId(
        Builder $builder,
        int $serviceProviderId,
        int $shiftQualificationId
    ): Builder {
        return $builder
            ->where('service_provider_id', $serviceProviderId)
            ->where('shift_qualification_id', $shiftQualificationId);
    }
}
