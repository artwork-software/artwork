<?php

namespace Artwork\Modules\Budget\Services\CollectiveBookings;

use Artwork\Modules\Budget\Models\CollectiveBookings\CollectiveBooking;
use Illuminate\Database\Eloquent\Collection;

interface CollectiveBookingService
{
    public function deleteChildData(CollectiveBooking $collectiveBooking): void;

    public function findParentBookingByIdentifiers(
        ...$identifiers
    ): CollectiveBooking|null;

    public function findChildrenForCollectiveBooking(CollectiveBooking $collectiveBooking): Collection;

    public function findParentByCollectiveBooking(CollectiveBooking $collectiveBooking): CollectiveBooking|null;
}
