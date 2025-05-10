<?php

namespace Artwork\Modules\Budget\Services\CollectiveBookings;

use Artwork\Modules\Budget\Models\CollectiveBookings\CollectiveBooking;
use Illuminate\Database\Eloquent\Collection;

trait HandlesCollectiveBookings
{
    public function deleteChildData(CollectiveBooking $collectiveBooking): void
    {
        foreach ($collectiveBooking->findChildren()->get() as $child) {
            $child->delete();
        }
    }

    public function findChildrenForCollectiveBooking(CollectiveBooking $collectiveBooking): Collection
    {
        return $collectiveBooking->findChildren()->get();
    }

    public function findParentByCollectiveBooking(CollectiveBooking $collectiveBooking): CollectiveBooking|null
    {
        return $collectiveBooking->findParent()->get()->first();
    }
}
