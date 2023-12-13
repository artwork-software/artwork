<?php

namespace App\Models;

use App\Casts\TimeWithoutSeconds;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $shift_preset_id
 * @property string $start
 * @property string $end
 * @property int $break_minutes
 * @property int $craft_id
 * @property int $number_employees
 * @property int $number_masters
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
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
        'number_employees',
        'number_masters',
        'description',
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
    ];

    protected $appends = ['break_formatted'];

    protected $with = ['craft'];

    public function shiftPreset(): BelongsTo
    {
        return $this->belongsTo(ShiftPreset::class);
    }

    public function getBreakFormattedAttribute(): string
    {
        $hours = intdiv($this->break_minutes, 60) . ':' . ($this->break_minutes % 60);
        return Carbon::parse($hours)->format('H:i');
    }

    public function craft(): BelongsTo
    {
        return $this->belongsTo(Craft::class)->without(['users']);
    }
}
