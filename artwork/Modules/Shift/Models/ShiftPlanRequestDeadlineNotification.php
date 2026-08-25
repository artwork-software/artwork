<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Merkt sich pro Gewerk/KW, dass die Gewerkeleitung bereits über eine
 * überschrittene Einreichungsfrist benachrichtigt wurde (einmalige Erinnerung).
 *
 * @property int $id
 * @property int $craft_id
 * @property int $week_number
 * @property int $year
 */
class ShiftPlanRequestDeadlineNotification extends Model
{
    protected $fillable = [
        'craft_id',
        'week_number',
        'year',
    ];

    public function craft(): BelongsTo
    {
        return $this->belongsTo(Craft::class);
    }
}
