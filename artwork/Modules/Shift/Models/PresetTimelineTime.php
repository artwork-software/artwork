<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $preset_timeline_id
 * @property string $start
 * @property string $end
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property-read PresetTimelineTime $times
 */
class PresetTimelineTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'preset_timeline_id',
        'start',
        'end',
        'description',
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
    ];
}
