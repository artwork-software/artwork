<?php

namespace Artwork\Modules\Availability\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $frequency
 * @property Carbon $end_date
 * */
class AvailabilitySeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'frequency',
        'end_date',
    ];

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class, 'series_id', 'id')->without('series');
    }
}
