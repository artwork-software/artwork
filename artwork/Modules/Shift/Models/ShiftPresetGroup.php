<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftPresetGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function presets(): BelongsToMany
    {
        return $this->belongsToMany(
            SingleShiftPreset::class,
            'shift_preset_group_assignments',
            'shift_preset_group_id',
            'single_shift_preset_id'
        );
    }
}

