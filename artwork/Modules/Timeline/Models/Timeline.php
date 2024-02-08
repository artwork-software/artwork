<?php

namespace Artwork\Modules\Timeline\Models;

use App\Casts\TimeWithoutSeconds;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_id
 * @property string $start
 * @property string $end
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 */
class Timeline extends Model
{
    use HasFactory;

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
