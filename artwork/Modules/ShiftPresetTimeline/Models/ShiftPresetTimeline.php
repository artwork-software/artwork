<?php

namespace Artwork\Modules\ShiftPresetTimeline\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $fillable = [
        'shift_preset_id',
        'start',
        'end',
        'start_date',
        'end_date',
        'description',
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function shiftPreset(): BelongsTo
    {
        return $this->belongsTo(
            ShiftPreset::class,
            'shift_preset_id',
            'id',
            'shift_presets'
        );
    }
}
