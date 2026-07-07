<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
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
        'shift_preset_group_id'
    ];

    public function timeline(): HasMany
    {
        return $this->hasMany(ShiftPresetTimeline::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(PresetShift::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    public function scopeByNameLike(Builder $builder, string $name): Builder
    {
        return $builder->where('name', 'like', $name . '%');
    }
}
