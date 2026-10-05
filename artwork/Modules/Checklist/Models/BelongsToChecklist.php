<?php

namespace Artwork\Modules\Checklist\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToChecklist
{
    /**
     * @return BelongsTo<Checklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class, 'checklist_id', 'id', 'checklist');
    }
}
