<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SingleShiftPreset extends Model
{
    /** @use HasFactory<\Database\Factories\SingleShiftPresetFactory> */
    use HasFactory;


    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_duration',
        'craft_id',
        'description',
    ];

    public function craft(): BelongsTo
    {
        return $this->belongsTo(
            Craft::class,
            'craft_id',
            'id',
            'crafts'
        )->without(['users']);
    }

    // map shift qualifications relation
    public function shiftsQualifications(): BelongsToMany
    {
        return $this->belongsToMany(
            ShiftQualification::class,
            'single_shift_preset_qualifications',
            'single_shift_preset_id',
            'shift_qualification_id',
            'id',
            'id',
            'shiftQualifications'
        )->withPivot(['quantity']);
    }
}
