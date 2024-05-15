<?php

namespace Artwork\Modules\Timeline\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $event_id
 * @property string $start
 * @property string $end
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

    public function getTimeLineHeightAttribute(): float|int
    {
        $start = strtotime($this->start);
        $end = strtotime($this->end);
        $diff = $end - $start;
        $minutes = $diff / 60;
        return $minutes / 60 * 180;
    }
}
