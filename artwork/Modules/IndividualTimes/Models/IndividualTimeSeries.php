<?php

namespace Artwork\Modules\IndividualTimes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndividualTimeSeries extends Model
{
    use HasFactory;

    protected $table = 'individual_time_series';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'title',
        'start_date',
        'end_date',
        'frequency',
        'interval',
        'weekdays',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'weekdays'   => 'array',
    ];

    public function individualTimes(): HasMany
    {
        return $this->hasMany(IndividualTime::class, 'series_uuid', 'uuid');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
