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
        'start',
        'end',
        'description',
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
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
}
