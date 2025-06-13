<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $shift_preset_id
 * @property string $start
 * @property string $end
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 */
class ShiftPresetTimeline extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
    ];

    public function times(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PresetTimelineTime::class, 'preset_timeline_id', 'id');
    }

    public function searchableAs(): string
    {
        return 'shift_preset_timelines';
    }

    public function toSearchableArray(): array
    {
        return $this->toArray();
    }
}
