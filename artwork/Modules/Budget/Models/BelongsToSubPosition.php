<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSubPosition
{
    /**
     * @return BelongsTo<SubPosition, $this>
     */
    public function subPosition(): BelongsTo
    {
        return $this->belongsTo(SubPosition::class, 'sub_position_id', 'id', 'subPosition');
    }
}
