<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property int $event_type_id
 * @property string $created_at
 * @property string $updated_at
 */
class ShiftPreset extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'event_type_id'
    ];

    public function timeLine(): HasMany
    {
        return $this->hasMany(PresetTimeLine::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(PresetShift::class);
    }

    public function event_type(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
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
}
