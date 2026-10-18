<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToColumn
{
    /**
     * @return BelongsTo<Column, $this>
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class, 'column_id', 'id', 'columns');
    }
}
