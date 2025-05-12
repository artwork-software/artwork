<?php

namespace Artwork\Modules\Sage100\Helpers;

class SageDataBookingTypeSplitter
{
    public function splitDataIntoRegularAndCollectiveBookings(array $items): array
    {
        $grouped = collect($items)->groupBy(fn($item) => $item['ID'].'-'.$item['KtoSoll'].'-'.$item['KtoHaben']);

        $collectiveBookings = $grouped->filter(function ($group) {
            return $group->count() > 1;
        });

        $singleBookings = $grouped->filter(function ($group) {
            return $group->count() === 1;
        })->flatten(1);

        return [
            $singleBookings->toArray(),
            $collectiveBookings->toArray(),
        ];
    }
}
