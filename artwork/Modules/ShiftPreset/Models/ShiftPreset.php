<?php

namespace Artwork\Modules\ShiftPreset\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\ShiftPresetTimeline\Models\ShiftPresetTimeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property int $event_type_id
 * @property string $created_at
 * @property string $updated_at
 * @property Collection<ShiftPresetTimeline> $timeline
 * @property Collection<PresetShift> $shifts
 */
class ShiftPreset extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'event_type_id'
    ];

    public function timeline(): HasMany
    {
        return $this->hasMany(ShiftPresetTimeline::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(PresetShift::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(
            EventType::class,
            'event_type_id',
            'id',
            'event_types'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'event_type_id' => $this->event_type_id
        ];
    }

    public function scopeByNameLike(Builder $builder, string $name): Builder
    {
        return $builder->where('name', 'like', $name . '%');
    }

    public function scopeByEventTypeId(Builder $builder, int $eventTypeId): Builder
    {
        return $builder->where('event_type_id', $eventTypeId);
    }
}
