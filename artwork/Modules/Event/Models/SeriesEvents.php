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
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property array<int>|null $weekdays
 * @property int|null $occurrence_count
 * @property string $created_at
 * @property string $updated_at
 */
class SeriesEvents extends Model
{
    use HasFactory;
    use Prunable;

    public const FREQUENCY_DAILY = 1;
    public const FREQUENCY_WEEKLY = 2;
    public const FREQUENCY_BIWEEKLY = 3;
    public const FREQUENCY_MONTHLY = 4;

    protected $fillable = [
        'frequency_id',
        'start_date',
        'end_date',
        'weekdays',
        'occurrence_count',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'weekdays' => 'array',
        'occurrence_count' => 'integer',
    ];

    /**
     * Serialisierung für Modal und Serien-Tab.
     *
     * @return array<string, mixed>
     */
    public function toDefinitionArray(): array
    {
        return [
            'id' => $this->id,
            'frequency_id' => (int) $this->frequency_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'weekdays' => $this->weekdays ? array_values(array_map('intval', $this->weekdays)) : null,
            'occurrence_count' => $this->occurrence_count,
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'series_id', 'id');
    }

    public function prunable(): Builder
    {
        // Prune series that have no associated events
        return static::doesntHave('events');
    }

    /**
     * Prepare the model for pruning by handling related events.
     */
    public function prune(): bool
    {
        // Before deleting the series, permanently delete all related events
        // Events use SoftDeletes, so we need to use forceDelete() to actually remove them
        // Use withTrashed() to include soft-deleted events that still hold the FK reference
        $events = $this->events()->withTrashed()->get();
        foreach ($events as $event) {
            $event->forceDelete();
        }

        // Now delete the series
        return $this->delete() ?? false;
    }
}
