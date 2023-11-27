<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTable
{
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id', 'id', 'table');
    }
}
