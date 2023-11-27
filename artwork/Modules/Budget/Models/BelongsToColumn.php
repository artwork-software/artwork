<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToColumn
{
    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class, 'column_id', 'id', 'column');
    }
}
