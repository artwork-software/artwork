<?php

namespace Artwork\Modules\Availability\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
* @property int $id
 * @property string $available_type
 * @property int $available_id
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property Carbon $date
 * @property boolean $full_day
 * @property string $comment
 * @property boolean $is_series
 * @property int $series_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Availability extends Model
{
    use HasFactory;
    use HasChangesHistory;

    protected $table = 'availabilities';

    protected $fillable = [
        'start_time',
        'end_time',
        'date',
        'full_day',
        'comment',
        'is_series',
        'series_id',
        'available_type',
        'available_id',
    ];

    protected $casts = [
        'full_day' => 'boolean',
        'is_series' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $appends = [
        'date_casted',
    ];

    public function availabilities(): MorphTo
    {
        return $this->morphTo();
    }

    public function getDateCastedAttribute()
    {
        Carbon::setLocale('de');
        return Carbon::parse($this->date)->translatedFormat('D, d.m.Y');
    }

    protected $with = [
        'series'
    ];

    public function series(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AvailabilitySeries::class, 'id', 'series_id');
    }
}
