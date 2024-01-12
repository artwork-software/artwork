<?php

namespace Artwork\Modules\Vacation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
* @property int $id
 * @property string $frequency
 * @property Carbon $end_date
 */
class VacationSeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'frequency',
        'end_date',
    ];
}
