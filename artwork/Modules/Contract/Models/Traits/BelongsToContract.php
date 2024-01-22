<?php

namespace Artwork\Modules\Contract\Models\Traits;

use App\Models\Contract;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToContract
{
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id', 'contract');
    }
}
