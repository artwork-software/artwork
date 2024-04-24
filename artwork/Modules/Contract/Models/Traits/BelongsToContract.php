<?php

namespace Artwork\Modules\Contract\Models\Traits;

use Artwork\Modules\Contract\Models\Contract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToContract
{
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id', 'contract');
    }
}
