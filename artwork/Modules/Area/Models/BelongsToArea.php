<?php

namespace Artwork\Modules\Area\Models;

use Artwork\Modules\Budget\Models\Column;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToArea
{
    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id', 'id', 'area');
    }
}
