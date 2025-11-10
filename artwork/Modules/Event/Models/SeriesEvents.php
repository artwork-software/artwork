<?php

namespace Artwork\Modules\Event\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    use Prunable;

    protected $fillable = [
        'frequency_id',
        'end_date'
    ];

    protected $casts = [
        'end_date' => 'date:Y-m-d',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'series_id', 'id');
    }

    public function prunable(): Builder
    {
        // Prune series that have no associated events
        return static::doesntHave('events');
    }
}
