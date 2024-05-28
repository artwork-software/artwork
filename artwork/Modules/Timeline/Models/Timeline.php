<?php

namespace Artwork\Modules\Timeline\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $event_id
 * @property string $start
 * @property string $end
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $description
 * @property Event $event
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class Timeline extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'start_date',
        'end_date',
        'start',
        'end',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
    ];

    protected $appends = [
        'time_line_height',
        'formatted_dates',
    ];
    public function event(): BelongsTo
    {
        return $this->belongsTo(
            Event::class,
            'event_id',
            'id',
            'events'
        );
    }

    /**
    * @return array<string, mixed>
     */
    public function getFormattedDatesAttribute(): array
    {
        return [
            'start_date' => Carbon::parse($this->start_date)->format('d.m.Y'),
            'end_date' => Carbon::parse($this->end_date)->format('d.m.Y'),
        ];
    }

    public function getTimeLineHeightAttribute(): float|int
    {
        $startDate = Carbon::parse($this->start_date);
        $startTime = Carbon::parse($this->start);
        $endDate = Carbon::parse($this->end_date);
        $endTime = Carbon::parse($this->end);

        $shiftStartDateTime = Carbon::parse($startDate->toDateString() . ' ' . $startTime->toTimeString());
        $shiftEndDateTime = Carbon::parse($endDate->toDateString() . ' ' . $endTime->toTimeString());


        // Berechne die Differenz in Minuten
        $diff = $shiftStartDateTime->diffInMinutes($shiftEndDateTime);

        // Lade Konfigurationswerte
        $maxShiftHeight = (int) config('shift.max_shift_height');
        $shiftHeightFactor = (float) config('shift.shift_height_factor');
        $shiftHeightOffset = (int) config('shift.shift_height_offset');

        // Berechne die Schichthöhe
        $shiftHeight = ($diff / 60) * $shiftHeightFactor;

        // Gib die minimale Schichthöhe zurück
        return min($shiftHeight, $maxShiftHeight - $shiftHeightOffset);
    }
}
