<?php

namespace Artwork\Modules\Availability\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
* @property int $id
 * @property int $availability_id
 * @property int $shift_id
 * @property string $user_name
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 * @property string $created_at
 * @property string $updated_at
 */
class AvailabilitiesConflict extends Model
{
    use HasFactory;

    protected $fillable = [
        'availability_id',
        'shift_id',
        'user_name',
        'date',
        'start_time',
        'end_time'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $appends = [
        'date_casted'
    ];

    public function availability(): BelongsTo
    {
        return $this->belongsTo(Availability::class, 'availability_id', 'id', 'availabilities');
    }


    public function getDateCastedAttribute(): string
    {
        Carbon::setLocale('de');
        return Carbon::parse($this->date)->translatedFormat('d.m.Y');
    }

}
