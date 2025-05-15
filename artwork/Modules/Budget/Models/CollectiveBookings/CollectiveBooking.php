<?php

namespace Artwork\Modules\Budget\Models\CollectiveBookings;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface CollectiveBooking
{
    public function findChildren(): HasMany;

    public function findParent(): BelongsTo;
}
