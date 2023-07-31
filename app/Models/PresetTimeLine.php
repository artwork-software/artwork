<?php

namespace App\Models;

use App\Casts\TimeWithoutSeconds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function shift_preset(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ShiftPreset::class);
    }

}
