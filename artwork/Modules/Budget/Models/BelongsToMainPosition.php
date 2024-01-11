<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToMainPosition
{
    public function mainPosition(): BelongsTo
    {
        return $this->belongsTo(MainPosition::class, 'main_position_id', 'id', 'mainPosition');
    }

    public function main_position(): BelongsTo
    {
        logger()->debug(sprintf('Inconsistent method call %s in %s', __METHOD__, __FILE__));
        return $this->mainPosition();
    }
}
