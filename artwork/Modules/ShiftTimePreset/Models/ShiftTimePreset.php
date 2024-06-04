<?php

namespace Artwork\Modules\ShiftTimePreset\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string name
 * @property string start_time
 * @property string end_time
 * @property string break_time
 */
class ShiftTimePreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_time',
    ];

    protected $casts = [
        'start_time' => TimeWithoutSeconds::class,
        'end_time' => TimeWithoutSeconds::class,
    ];
}
