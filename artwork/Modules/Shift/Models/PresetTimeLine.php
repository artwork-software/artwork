<?php

namespace Artwork\Modules\Shift\Models;

use App\Casts\TimeWithoutSeconds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
class PresetTimeLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_preset_id',
        'start',
        'end',
        'description',
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
    ];

    public function shiftPreset(): BelongsTo
    {
        return $this->belongsTo(ShiftPreset::class);
    }
}
