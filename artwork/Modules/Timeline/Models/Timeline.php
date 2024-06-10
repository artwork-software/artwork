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
}
