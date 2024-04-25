<?php

namespace Artwork\Modules\SeriesEvents\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;

/**
 * @property int $id
 * @property int $frequency_id
 * @property Carbon $end_date
 * @property string $created_at
 * @property string $updated_at
 */
class SeriesEvents extends Model
{
    use HasFactory;

    protected $fillable = [
        'frequency_id',
        'end_date'
    ];

    protected $casts = [
        'end_date' => 'date:Y-m-d',
    ];
}
