<?php

namespace Artwork\Modules\Budget\Models\CollectiveBookings;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait IsCollectiveBooking
{
    public function findChildren(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_booking_id', 'id')
            ->where('is_collective_booking', false);
    }

    public function findParent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_booking_id', 'id')
            ->where('is_collective_booking', true);
    }
}
