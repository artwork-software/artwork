<?php

namespace Artwork\Modules\PresetShift\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $shift_preset_id
 * @property string $start
 * @property string $end
 * @property int $break_minutes
 * @property int $craft_id
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property Collection<PresetShiftShiftsQualifications> $shiftsQualifications
 */
class PresetShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_preset_id',
        'start',
        'end',
        'break_minutes',
        'craft_id',
        'description',
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
    ];

    protected $appends = ['break_formatted'];

    public function shiftPreset(): BelongsTo
    {
        return $this->belongsTo(
            ShiftPreset::class,
            'shift_preset_id',
            'id',
            'shift_preset'
        );
    }

    public function craft(): BelongsTo
    {
        return $this->belongsTo(
            Craft::class,
            'craft_id',
            'id',
            'crafts'
        )->without(['users']);
    }

    public function shiftsQualifications(): HasMany
    {
        return $this->hasMany(PresetShiftShiftsQualifications::class);
    }

    public function getBreakFormattedAttribute(): string
    {
        $hours = intdiv($this->break_minutes, 60) . ':' . ($this->break_minutes % 60);
        return Carbon::parse($hours)->format('H:i');
    }
}
