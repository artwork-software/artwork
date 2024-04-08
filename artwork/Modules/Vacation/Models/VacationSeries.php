<?php

namespace Artwork\Modules\Vacation\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $frequency
 * @property Carbon $end_date
 *
 * @property-read Vacation[]|Collection $vacations
 */
class VacationSeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'frequency',
        'end_date',
    ];

    public function vacations(): HasMany
    {
        return $this->hasMany(Vacation::class, 'series_id', 'id');
    }
}
